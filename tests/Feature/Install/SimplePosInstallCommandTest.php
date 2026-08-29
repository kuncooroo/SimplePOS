<?php

declare(strict_types=1);

namespace Tests\Feature\Install;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Install\InstallState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SimplePosInstallCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        config(['installer.installed' => false]);

        $this->tempDir = sys_get_temp_dir().'/simplepos-cli-'.uniqid('', true);
        mkdir($this->tempDir);

        file_put_contents($this->tempDir.'/.env', implode(PHP_EOL, [
            'APP_NAME=SimplePOS',
            'APP_ENV=testing',
            'APP_KEY=base64:'.base64_encode(random_bytes(32)),
            'APP_DEBUG=false',
            'APP_URL=http://localhost',
            'DB_CONNECTION=sqlite',
            'DB_DATABASE=:memory:',
        ]).PHP_EOL);

        config(['installer.env_path' => $this->tempDir.'/.env']);
    }

    protected function tearDown(): void
    {
        if (is_file($path = storage_path('app/installed'))) {
            unlink($path);
        }

        if (is_file($this->tempDir.'/.env')) {
            unlink($this->tempDir.'/.env');
        }

        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    public function test_command_installs_application_in_no_interaction_mode(): void
    {
        $this->artisan('simplepos:install', ['--no-interaction' => true])
            ->assertSuccessful();

        $this->assertTrue(InstallState::isInstalled());
        $this->assertTrue(InstallState::hasInstallLock());
        $this->assertSame('owner@example.com', User::query()->where('role', UserRole::Owner)->value('email'));
    }

    public function test_command_refuses_when_application_is_already_installed(): void
    {
        User::factory()->owner()->create();

        $this->artisan('simplepos:install', ['--no-interaction' => true])
            ->assertFailed();
    }

    public function test_lock_only_writes_lock_when_owner_exists(): void
    {
        User::factory()->owner()->create();

        $this->artisan('simplepos:install', ['--lock-only' => true])
            ->assertSuccessful();

        $this->assertTrue(InstallState::hasInstallLock());
    }

    public function test_lock_only_fails_without_owner(): void
    {
        $this->artisan('simplepos:install', ['--lock-only' => true])
            ->assertFailed();
    }

    public function test_command_does_not_invoke_migrate_fresh(): void
    {
        Artisan::spy();

        $this->artisan('simplepos:install', ['--no-interaction' => true])->assertSuccessful();

        Artisan::shouldNotHaveReceived('call', ['migrate:fresh', \Mockery::any()]);
        Artisan::shouldNotHaveReceived('call', ['db:wipe', \Mockery::any()]);
    }
}
