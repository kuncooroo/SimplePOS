<?php

declare(strict_types=1);

namespace Tests\Feature\Install;

use App\Models\User;
use App\Support\Install\InstallState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InstallerGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['installer.installed' => false]);
    }

    protected function tearDown(): void
    {
        if (is_file($path = storage_path('app/installed'))) {
            unlink($path);
        }

        parent::tearDown();
    }

    public function test_install_welcome_renders_when_application_is_not_installed(): void
    {
        $this->get(route('install.welcome'))
            ->assertOk()
            ->assertSee('Welcome', false)
            ->assertSee('one-time setup', false)
            ->assertSee('Continue', false)
            ->assertDontSee('OWNER_PASSWORD', false)
            ->assertDontSee('.env', false);
    }

    public function test_welcome_form_includes_csrf_token(): void
    {
        $this->get(route('install.welcome'))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function test_product_routes_redirect_to_installer_when_not_installed(): void
    {
        $this->assertFalse(InstallState::isInstalled());

        $this->get(route('home'))
            ->assertRedirect(route('install.welcome'));

        $this->get(route('login'))
            ->assertRedirect(route('install.welcome'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('install.welcome'));
    }

    public function test_health_check_remains_available_before_install(): void
    {
        $this->get('/up')->assertSuccessful();
    }

    public function test_welcome_continue_advances_to_requirements(): void
    {
        $this->post(route('install.welcome.continue'))
            ->assertRedirect(route('install.requirements'));

        $this->get(route('install.requirements'))
            ->assertOk()
            ->assertSee('System requirements', false)
            ->assertSee('PHP version', false);
    }

    public function test_installer_post_routes_are_throttled(): void
    {
        $route = Route::getRoutes()->getByName('install.welcome.continue');

        $this->assertNotNull($route);
        $this->assertContains('throttle:10,1', $route->gatherMiddleware());

        foreach (range(1, 10) as $attempt) {
            $this->post(route('install.welcome.continue'));
        }

        $this->post(route('install.welcome.continue'))
            ->assertStatus(429);
    }

    public function test_install_routes_return_not_found_when_application_is_installed(): void
    {
        config(['installer.installed' => true]);

        $this->get(route('install.welcome'))->assertNotFound();
        $this->post(route('install.welcome.continue'))->assertNotFound();
        $this->get(route('install.requirements'))->assertNotFound();
        $this->get(route('install.extensions'))->assertNotFound();
        $this->get(route('install.permissions'))->assertNotFound();
        $this->get(route('install.database'))->assertNotFound();
        $this->get(route('install.environment'))->assertNotFound();
        $this->get(route('install.complete'))->assertNotFound();
        $this->post(route('install.complete.store'))->assertNotFound();
    }

    public function test_install_routes_return_not_found_when_owner_exists_without_lock(): void
    {
        User::factory()->owner()->create();

        $this->get(route('install.welcome'))->assertNotFound();
        $this->post(route('install.welcome.continue'))->assertNotFound();
    }

    public function test_install_state_treats_missing_users_table_as_not_installed(): void
    {
        $this->assertFalse(InstallState::isInstalled());
    }

    public function test_install_state_detects_owner_account_as_installed(): void
    {
        User::factory()->owner()->create();

        $this->assertTrue(InstallState::isInstalled());
    }
}
