<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Actions\Identity\ChangeUserStatus;
use App\Actions\Identity\CreateUser;
use App\Enums\UserRole;
use App\Http\Requests\Auth\LoginRequest;
use App\Livewire\Identity\UserForm;
use App\Livewire\Identity\UserIndex;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_an_administrator_and_a_cashier(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(UserForm::class)
            ->set('name', 'Ada Admin')
            ->set('email', 'ada@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('role', UserRole::Administrator->value)
            ->set('active', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        Livewire::actingAs($owner)
            ->test(UserForm::class)
            ->set('name', 'Casey Cashier')
            ->set('email', 'casey@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('role', UserRole::Cashier->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'ada@example.com',
            'role' => UserRole::Administrator->value,
            'active' => 1,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'casey@example.com',
            'role' => UserRole::Cashier->value,
        ]);

        $this->assertTrue(Hash::check('password', User::query()->where('email', 'ada@example.com')->first()->password));
    }

    public function test_owner_can_assign_the_owner_role(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(UserForm::class)
            ->set('name', 'Second Owner')
            ->set('email', 'second-owner@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('role', UserRole::Owner->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'second-owner@example.com',
            'role' => UserRole::Owner->value,
        ]);
    }

    public function test_administrator_cannot_assign_owner_or_deactivate_owner(): void
    {
        $admin = User::factory()->administrator()->create();
        $owner = User::factory()->owner()->create([
            'email' => 'protected-owner@example.com',
        ]);

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->set('name', 'Should Fail')
            ->set('email', 'new-owner@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('role', UserRole::Owner->value)
            ->call('save')
            ->assertHasErrors(['role']);

        $this->assertDatabaseMissing('users', [
            'email' => 'new-owner@example.com',
        ]);

        $this->expectException(AuthorizationException::class);
        app(CreateUser::class)->execute($admin, [
            'name' => 'Bypassed Owner',
            'email' => 'bypassed-owner@example.com',
            'password' => 'password',
            'role' => UserRole::Owner->value,
        ]);
    }

    public function test_administrator_cannot_edit_or_deactivate_the_protected_owner(): void
    {
        $admin = User::factory()->administrator()->create();
        $owner = User::factory()->owner()->create([
            'email' => 'protected-owner@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('users.edit', $owner))
            ->assertForbidden()
            ->assertDontSee('protected-owner@example.com', false);

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('confirmDeactivate', $owner->id)
            ->assertForbidden();

        $this->expectException(AuthorizationException::class);
        app(ChangeUserStatus::class)->execute($admin, $owner, false);
    }

    public function test_cashier_is_forbidden_on_user_management_urls(): void
    {
        $cashier = User::factory()->cashier()->create();
        $owner = User::factory()->owner()->create();

        $this->actingAs($cashier)->get(route('users.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('users.create'))->assertForbidden();
        $this->actingAs($cashier)->get(route('users.edit', $owner))->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(UserIndex::class)
            ->assertForbidden();
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $owner = User::factory()->owner()->create();
        User::factory()->cashier()->create([
            'email' => 'taken@example.com',
        ]);

        Livewire::actingAs($owner)
            ->test(UserForm::class)
            ->set('name', 'Copy')
            ->set('email', 'taken@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('role', UserRole::Cashier->value)
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_deactivated_user_row_remains_and_cannot_sign_in(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create([
            'email' => 'later@example.com',
        ]);

        Livewire::actingAs($owner)
            ->test(UserIndex::class)
            ->call('confirmDeactivate', $cashier->id)
            ->call('deactivate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $cashier->id,
            'email' => 'later@example.com',
            'active' => 0,
        ]);

        $this->post(route('logout'));

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'later@example.com',
                'password' => 'password',
            ])
            ->assertSessionHasErrors([
                'credentials' => LoginRequest::GENERIC_FAILURE,
            ]);

        $this->assertGuest();
    }

    public function test_password_omitted_on_update_leaves_the_hash_unchanged(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create([
            'name' => 'Casey',
            'email' => 'casey@example.com',
        ]);
        $originalHash = $cashier->password;

        Livewire::actingAs($owner)
            ->test(UserForm::class, ['user' => $cashier])
            ->set('name', 'Casey Updated')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertSame('Casey Updated', $cashier->fresh()->name);
        $this->assertSame($originalHash, $cashier->fresh()->password);
    }

    public function test_user_cannot_deactivate_themselves(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(UserIndex::class)
            ->call('confirmDeactivate', $owner->id)
            ->assertForbidden();

        $this->assertTrue($owner->fresh()->active);
    }

    public function test_search_empty_state_is_shown_when_nothing_matches(): void
    {
        $owner = User::factory()->owner()->create([
            'name' => 'Store Owner',
            'email' => 'owner@example.com',
        ]);

        Livewire::actingAs($owner)
            ->test(UserIndex::class)
            ->set('search', 'zzzz-no-such-user')
            ->assertSee('No matching users', false);
    }

    public function test_owner_index_lists_users_and_hides_users_nav_from_cashier_context(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('New user', false)
            ->assertSee($owner->email, false);
    }
}
