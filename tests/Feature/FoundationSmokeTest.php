<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\OwnerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_success(): void
    {
        $this->get('/up')->assertSuccessful();
    }

    public function test_login_page_uses_guest_layout(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in', false)
            ->assertSee(config('app.name'), false);
    }

    public function test_dashboard_uses_app_layout_for_authenticated_users(): void
    {
        $user = User::factory()->owner()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Point of sale', false);
    }

    public function test_user_factory_creates_role_and_active_flags(): void
    {
        $user = User::factory()->cashier()->create();

        $this->assertTrue($user->active);
        $this->assertSame(UserRole::Cashier, $user->role);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_users_table_matches_identity_schema(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'id',
            'name',
            'email',
            'password',
            'role',
            'active',
            'created_at',
            'updated_at',
        ]));
        $this->assertFalse(Schema::hasColumn('users', 'email_verified_at'));
        $this->assertFalse(Schema::hasColumn('users', 'tenant_id'));
    }

    public function test_owner_seeder_creates_one_active_owner(): void
    {
        $this->seed(OwnerSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'role' => UserRole::Owner->value,
            'active' => 1,
        ]);
    }
}
