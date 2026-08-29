<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Actions\Identity\UpdateProfile;
use App\Enums\UserRole;
use App\Livewire\Identity\ProfileForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_their_name(): void
    {
        $cashier = User::factory()->cashier()->create([
            'name' => 'Old Cashier Name',
        ]);

        Livewire::actingAs($cashier)
            ->test(ProfileForm::class)
            ->set('name', 'Updated Cashier Name')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('name', 'Updated Cashier Name');

        $this->assertSame('Updated Cashier Name', $cashier->fresh()->name);
    }

    public function test_password_change_requires_current_password_and_invalidates_the_old_one(): void
    {
        $cashier = User::factory()->cashier()->create([
            'password' => 'old-password',
        ]);

        Livewire::actingAs($cashier)
            ->test(ProfileForm::class)
            ->set('password', 'new-password-1')
            ->set('password_confirmation', 'new-password-1')
            ->set('current_password', 'old-password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password-1', $cashier->fresh()->password));

        $this->post(route('logout'));
        $this->assertGuest();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $cashier->email,
                'password' => 'old-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('credentials');

        $this->post(route('login.store'), [
            'email' => $cashier->email,
            'password' => 'new-password-1',
        ])->assertRedirect(route('pos'));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $owner = User::factory()->owner()->create([
            'password' => 'correct-password',
        ]);

        Livewire::actingAs($owner)
            ->test(ProfileForm::class)
            ->set('password', 'new-password-1')
            ->set('password_confirmation', 'new-password-1')
            ->set('current_password', 'wrong-password')
            ->call('save')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('correct-password', $owner->fresh()->password));
    }

    public function test_profile_update_does_not_change_role(): void
    {
        $cashier = User::factory()->cashier()->create([
            'name' => 'Casey Cashier',
        ]);

        Livewire::actingAs($cashier)
            ->test(ProfileForm::class)
            ->set('name', 'Casey Updated')
            ->call('save')
            ->assertHasNoErrors();

        $cashier->refresh();

        $this->assertSame(UserRole::Cashier, $cashier->role);
        $this->assertSame('Casey Updated', $cashier->name);
    }

    public function test_update_profile_action_only_updates_name_and_password_fields(): void
    {
        $cashier = User::factory()->cashier()->create([
            'name' => 'Original Name',
            'password' => 'old-password',
        ]);

        app(UpdateProfile::class)->execute(
            user: $cashier,
            name: 'Renamed Cashier',
            password: 'new-password-1',
        );

        $cashier->refresh();

        $this->assertSame('Renamed Cashier', $cashier->name);
        $this->assertSame(UserRole::Cashier, $cashier->role);
        $this->assertTrue(Hash::check('new-password-1', $cashier->password));
    }

    public function test_cashier_can_open_their_profile_page(): void
    {
        $cashier = User::factory()->cashier()->create([
            'name' => 'POS Cashier',
            'email' => 'cashier@example.com',
        ]);

        $this->actingAs($cashier)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profile', false)
            ->assertSee('POS Cashier', false)
            ->assertSee('cashier@example.com', false)
            ->assertSee('Cashier', false)
            ->assertSee(route('profile.edit'), false);
    }

    public function test_profile_route_does_not_accept_another_user_id(): void
    {
        $this->assertFalse(collect(Route::getRoutes())->contains(
            fn ($route): bool => str_contains($route->uri(), 'profile/{')
        ));
    }

    public function test_guest_is_redirected_from_profile(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }
}
