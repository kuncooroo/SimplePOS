<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Email', false)
            ->assertSee('Password', false)
            ->assertSee('Sign in', false);
    }

    public function test_active_user_can_authenticate(): void
    {
        $user = User::factory()->owner()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_cashier_lands_on_pos(): void
    {
        $user = User::factory()->cashier()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('pos'));
    }

    public function test_wrong_password_is_rejected_without_a_session(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'credentials' => LoginRequest::GENERIC_FAILURE,
            ]);

        $this->assertGuest();
    }

    public function test_unknown_email_is_rejected_with_the_same_generic_message(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'nobody@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'credentials' => LoginRequest::GENERIC_FAILURE,
            ]);

        $this->assertGuest();
        $this->assertFalse(session('errors')->has('email'));
    }

    public function test_inactive_user_cannot_authenticate_with_a_valid_password(): void
    {
        $user = User::factory()->inactive()->create();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertSessionHasErrors([
                'credentials' => LoginRequest::GENERIC_FAILURE,
            ]);

        $this->assertGuest();
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_authenticated_routes(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_redirected_away_from_login(): void
    {
        $user = User::factory()->owner()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_deactivated_user_is_signed_out_on_the_next_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $user->update(['active' => false]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $user = User::factory()->create();

        foreach (range(1, LoginRequest::MAX_ATTEMPTS) as $attempt) {
            $this->from(route('login'))->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('credentials');
        $this->assertNotSame(
            LoginRequest::GENERIC_FAILURE,
            session('errors')->first('credentials'),
        );
        $this->assertGuest();
    }

    public function test_empty_fields_show_validation_errors_without_credential_message(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => '',
                'password' => '',
            ])
            ->assertSessionHasErrors(['email', 'password'])
            ->assertSessionMissing('errors.credentials');

        $this->assertGuest();
    }
}
