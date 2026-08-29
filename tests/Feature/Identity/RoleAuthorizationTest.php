<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function managementGates(): array
    {
        return [
            'accessPos',
            'viewDashboard',
            'viewOwnTransactions',
            'viewAllTransactions',
            'manageProducts',
            'manageCategories',
            'viewInventory',
            'adjustStock',
            'viewStockMovements',
            'viewReports',
            'manageUsers',
            'assignOwnerRole',
            'manageStoreSettings',
            'viewAuditLog',
        ];
    }

    public function test_owner_is_allowed_the_management_gates(): void
    {
        $owner = User::factory()->owner()->create();

        foreach ($this->managementGates() as $ability) {
            $this->assertTrue($owner->can($ability), $ability);
        }

        $this->assertFalse($owner->can('deleteCompletedTransaction'));
    }

    public function test_administrator_is_limited_on_owner_and_audit_gates(): void
    {
        $admin = User::factory()->administrator()->create();
        $owner = User::factory()->owner()->create();

        foreach ([
            'accessPos',
            'viewDashboard',
            'viewOwnTransactions',
            'viewAllTransactions',
            'manageProducts',
            'manageCategories',
            'viewInventory',
            'adjustStock',
            'viewStockMovements',
            'viewReports',
            'manageUsers',
            'manageStoreSettings',
        ] as $ability) {
            $this->assertTrue($admin->can($ability), $ability);
        }

        $this->assertTrue($admin->can('create', User::class));
        $this->assertTrue(Gate::forUser($admin)->denies('assignOwnerRole'));
        $this->assertTrue(Gate::forUser($admin)->denies('viewAuditLog'));
        $this->assertTrue(Gate::forUser($admin)->denies('deleteCompletedTransaction'));
        $this->assertFalse($admin->can('update', $owner));
        $this->assertFalse($admin->can('changeStatus', $owner));
        $this->assertFalse($admin->can('delete', $owner));
    }

    public function test_cashier_is_denied_management_gates(): void
    {
        $cashier = User::factory()->cashier()->create();

        foreach ([
            'viewDashboard',
            'manageUsers',
            'adjustStock',
            'viewReports',
            'manageStoreSettings',
            'viewAuditLog',
            'assignOwnerRole',
            'viewAllTransactions',
            'manageProducts',
            'manageCategories',
            'viewInventory',
            'viewStockMovements',
        ] as $ability) {
            $this->assertTrue(Gate::forUser($cashier)->denies($ability), $ability);
        }

        $this->assertTrue($cashier->can('accessPos'));
        $this->assertTrue($cashier->can('viewOwnTransactions'));
        $this->assertTrue(Gate::forUser($cashier)->denies('deleteCompletedTransaction'));
    }

    public function test_all_roles_are_denied_deleting_completed_transactions(): void
    {
        foreach ([
            User::factory()->owner()->create(),
            User::factory()->administrator()->create(),
            User::factory()->cashier()->create(),
        ] as $user) {
            $this->assertTrue(Gate::forUser($user)->denies('deleteCompletedTransaction'));
        }
    }

    public function test_unknown_role_is_denied_without_throwing(): void
    {
        $user = User::factory()->cashier()->create();

        DB::table('users')->where('id', $user->id)->update(['role' => 'NOPE']);

        $user = User::query()->findOrFail($user->id);

        $this->assertNull($user->resolvedRole());
        $this->assertFalse($user->isOwner());
        $this->assertFalse($user->isAdministrator());
        $this->assertFalse($user->isCashier());

        foreach ($this->managementGates() as $ability) {
            $this->assertTrue(Gate::forUser($user)->denies($ability), $ability);
        }
    }

    public function test_cashier_cannot_open_the_dashboard_or_users(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('dashboard'))
            ->assertForbidden()
            ->assertSee('Access denied', false)
            ->assertDontSee($cashier->email, false);

        $this->actingAs($cashier)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_cashier_sidebar_shows_pos_without_management_links(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('pos'))
            ->assertOk()
            ->assertSee('POS', false)
            ->assertSee('Main', false)
            ->assertDontSee('Administration', false)
            ->assertDontSee('Management', false)
            ->assertDontSee(route('users.index'), false)
            ->assertDontSee(route('categories.index'), false)
            ->assertDontSee(route('products.index'), false)
            ->assertDontSee(route('settings.edit'), false)
            ->assertDontSee(route('dashboard'), false);
    }

    public function test_owner_sidebar_includes_dashboard_pos_and_users(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Main', false)
            ->assertSee('Management', false)
            ->assertSee('Administration', false)
            ->assertSee(route('dashboard'), false)
            ->assertSee(route('pos'), false)
            ->assertSee(route('users.index'), false)
            ->assertSee(route('categories.index'), false)
            ->assertSee(route('products.index'), false)
            ->assertSee(route('settings.edit'), false);
    }

    public function test_administrator_sidebar_includes_users_and_settings_but_not_audit(): void
    {
        $admin = User::factory()->administrator()->create();

        $html = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('users.index'), false)
            ->assertSee(route('categories.index'), false)
            ->assertSee(route('products.index'), false)
            ->assertSee(route('settings.edit'), false)
            ->getContent();

        $this->assertStringNotContainsString('Activity Log', $html);
    }

    public function test_nobody_can_force_delete_a_user(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();

        $this->assertFalse($owner->can('delete', $cashier));
        $this->assertFalse($owner->can('forceDelete', $cashier));
    }
}
