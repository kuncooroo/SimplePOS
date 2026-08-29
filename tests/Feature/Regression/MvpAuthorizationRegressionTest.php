<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use App\Livewire\Audit\ActivityLogIndex;
use App\Livewire\Configuration\StoreSettingsForm;
use App\Livewire\Identity\UserIndex;
use App\Livewire\Inventory\AdjustStockForm;
use App\Livewire\Reporting\Dashboard;
use App\Livewire\Reporting\ProductSalesReport;
use App\Livewire\Reporting\SalesReport;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Consolidated Version 1.0 authorization regression (PRD AC-11).
 */
class MvpAuthorizationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_is_denied_management_and_reporting_modules(): void
    {
        $cashier = User::factory()->cashier()->create();

        foreach ([
            route('dashboard'),
            route('users.index'),
            route('settings.edit'),
            route('reports.sales'),
            route('reports.product-sales'),
            route('audit-log.index'),
            route('inventory.index'),
        ] as $url) {
            $this->actingAs($cashier)->get($url)->assertForbidden();
        }

        Livewire::actingAs($cashier)
            ->test(Dashboard::class)
            ->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(UserIndex::class)
            ->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(StoreSettingsForm::class)
            ->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(SalesReport::class)
            ->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(ProductSalesReport::class)
            ->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(ActivityLogIndex::class)
            ->assertForbidden();

        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock_quantity' => '5.000',
        ]);

        Livewire::actingAs($cashier)
            ->test(AdjustStockForm::class)
            ->dispatch('open-adjust-stock', productId: $product->id)
            ->assertForbidden();
    }

    public function test_administrator_is_denied_audit_log_but_can_access_reports_and_inventory(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->get(route('audit-log.index'))
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(ActivityLogIndex::class)
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('reports.sales'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('inventory.index'))
            ->assertOk();
    }

    public function test_cashier_can_access_pos_transactions_and_profile(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('pos'))
            ->assertOk();

        $this->actingAs($cashier)
            ->get(route('transactions.index'))
            ->assertOk();

        $this->actingAs($cashier)
            ->get(route('profile.edit'))
            ->assertOk();
    }
}
