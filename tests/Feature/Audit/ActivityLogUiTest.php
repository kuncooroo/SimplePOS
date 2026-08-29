<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Actions\Identity\ChangeUserRole;
use App\Actions\Inventory\AdjustStock;
use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Livewire\Audit\ActivityLogIndex;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_stock_adjustment_and_role_change_logs(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '8.000',
        ]);

        app(AdjustStock::class)->execute(
            actor: $owner,
            product: $product,
            quantityChange: '-1.000',
            reason: 'Damaged packaging',
        );

        app(ChangeUserRole::class)->execute($owner, $cashier, UserRole::Administrator);

        $stockLog = ActivityLog::query()
            ->where('action', ActivityAction::StockManualAdjusted)
            ->firstOrFail();
        $roleLog = ActivityLog::query()
            ->where('action', ActivityAction::UserRoleChanged)
            ->firstOrFail();

        Livewire::actingAs($owner)
            ->test(ActivityLogIndex::class)
            ->assertSee('Stock manually adjusted', false)
            ->assertSee('User role changed', false)
            ->call('toggleDetails', $stockLog->id)
            ->assertSee('Damaged packaging', false)
            ->call('toggleDetails', $stockLog->id)
            ->call('toggleDetails', $roleLog->id)
            ->assertSee(UserRole::Cashier->value, false)
            ->assertSee(UserRole::Administrator->value, false);
    }

    public function test_owner_can_filter_logs_by_action(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
        ]);

        app(AdjustStock::class)->execute(
            actor: $owner,
            product: $product,
            quantityChange: '1.000',
            reason: 'Count correction',
        );

        app(ChangeUserRole::class)->execute($owner, $cashier, UserRole::Administrator);

        $stockLog = ActivityLog::query()
            ->where('action', ActivityAction::StockManualAdjusted)
            ->firstOrFail();

        Livewire::actingAs($owner)
            ->test(ActivityLogIndex::class)
            ->set('action', ActivityAction::StockManualAdjusted->value)
            ->call('toggleDetails', $stockLog->id)
            ->assertSee('Count correction', false)
            ->assertDontSee('User #'.$cashier->id, false);
    }

    public function test_deactivated_actor_name_is_still_shown(): void
    {
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->administrator()->create([
            'name' => 'Former Admin',
        ]);

        ActivityLog::factory()->create([
            'user_id' => $admin->id,
            'action' => ActivityAction::StoreSettingsUpdated,
            'subject_type' => 'StoreSetting',
            'subject_id' => 1,
            'old_values' => ['store_name' => 'Old Store'],
            'new_values' => ['store_name' => 'New Store'],
        ]);

        $admin->update(['active' => false]);

        Livewire::actingAs($owner)
            ->test(ActivityLogIndex::class)
            ->assertSee('Former Admin', false)
            ->assertSee('Store settings updated', false);
    }

    public function test_cashier_is_denied_activity_log_access(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('audit-log.index'))
            ->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(ActivityLogIndex::class)
            ->assertForbidden();
    }

    public function test_administrator_is_denied_activity_log_access(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->get(route('audit-log.index'))
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(ActivityLogIndex::class)
            ->assertForbidden();
    }

    public function test_activity_log_ui_is_read_only_without_delete_endpoints(): void
    {
        $reflection = new \ReflectionClass(ActivityLogIndex::class);

        $this->assertFalse($reflection->hasMethod('delete'));
        $this->assertFalse($reflection->hasMethod('update'));
        $this->assertFalse($reflection->hasMethod('save'));

        $this->assertCount(0, collect(Route::getRoutes())->filter(function ($route): bool {
            $uri = $route->uri();

            return str_contains($uri, 'audit-log')
                && (in_array('DELETE', $route->methods(), true) || in_array('PUT', $route->methods(), true));
        }));
    }

    public function test_owner_sidebar_includes_activity_log_link(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('audit-log.index'), false)
            ->assertSee('Activity log', false);
    }

    public function test_administrator_sidebar_does_not_include_activity_log_link(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('audit-log.index'), false)
            ->assertDontSee('Activity log', false);
    }
}
