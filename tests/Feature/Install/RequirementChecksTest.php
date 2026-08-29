<?php

declare(strict_types=1);

namespace Tests\Feature\Install;

use App\Support\Install\ExtensionChecker;
use App\Support\Install\ExtensionChecker as ExtensionCheckerClass;
use App\Support\Install\InstallState;
use App\Support\Install\PermissionChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Install\FakeFilesystemProbe;
use Tests\Unit\Install\FakePhpExtensionProbe;

class RequirementChecksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['installer.installed' => false]);
    }

    public function test_requirements_page_lists_system_checks(): void
    {
        $this->post(route('install.welcome.continue'));

        $this->get(route('install.requirements'))
            ->assertOk()
            ->assertSee('PHP version', false)
            ->assertSee('Public front controller', false)
            ->assertSee('Continue', false);
    }

    public function test_happy_path_reaches_database_stub_after_all_preflight_steps(): void
    {
        $this->post(route('install.welcome.continue'));

        $this->post(route('install.requirements.continue'))
            ->assertRedirect(route('install.extensions'));

        $this->post(route('install.extensions.continue'))
            ->assertRedirect(route('install.permissions'));

        $this->post(route('install.permissions.continue'))
            ->assertRedirect(route('install.database'));

        $this->get(route('install.database'))
            ->assertOk()
            ->assertSee('Database connection', false);

        $this->assertSame(InstallState::STEP_DATABASE, InstallState::currentStep());
    }

    public function test_missing_required_extension_blocks_continue(): void
    {
        $loaded = array_fill_keys(ExtensionCheckerClass::REQUIRED, true);
        $loaded['pdo_mysql'] = false;

        $this->app->instance(ExtensionChecker::class, new ExtensionChecker(
            new FakePhpExtensionProbe($loaded),
        ));

        $this->post(route('install.welcome.continue'));
        $this->post(route('install.requirements.continue'));

        $this->get(route('install.extensions'))
            ->assertOk()
            ->assertSee('pdo_mysql', false)
            ->assertSee('fail', false);

        $this->from(route('install.extensions'))
            ->post(route('install.extensions.continue'))
            ->assertRedirect(route('install.extensions'))
            ->assertSessionHasErrors('checks');

        $this->get(route('install.permissions'))->assertRedirect(route('install.welcome'));
    }

    public function test_unwritable_folder_blocks_permissions_continue(): void
    {
        $blocked = storage_path('logs');

        $this->app->instance(PermissionChecker::class, new PermissionChecker(
            new FakeFilesystemProbe(writable: [$blocked => false]),
            paths: ['storage/logs' => $blocked],
        ));

        $this->post(route('install.welcome.continue'));
        $this->post(route('install.requirements.continue'));
        $this->post(route('install.extensions.continue'));

        $this->get(route('install.permissions'))
            ->assertOk()
            ->assertSee('storage/logs', false)
            ->assertSee('fail', false);

        $this->from(route('install.permissions'))
            ->post(route('install.permissions.continue'))
            ->assertRedirect(route('install.permissions'))
            ->assertSessionHasErrors('checks');

        $this->get(route('install.database'))->assertRedirect(route('install.requirements'));
    }

    public function test_optional_extensions_are_warnings_not_failures(): void
    {
        $loaded = array_fill_keys(ExtensionCheckerClass::REQUIRED, true);

        $this->app->instance(ExtensionChecker::class, new ExtensionChecker(
            new FakePhpExtensionProbe($loaded),
        ));

        $this->post(route('install.welcome.continue'));
        $this->post(route('install.requirements.continue'));

        $this->get(route('install.extensions'))
            ->assertOk()
            ->assertSee('optional', false)
            ->assertSee('warn', false);

        $this->post(route('install.extensions.continue'))
            ->assertRedirect(route('install.permissions'));
    }
}
