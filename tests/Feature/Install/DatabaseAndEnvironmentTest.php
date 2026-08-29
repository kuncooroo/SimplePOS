<?php

declare(strict_types=1);

namespace Tests\Feature\Install;

use App\Support\Install\DatabaseConnector;
use App\Support\Install\InstallState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Install\FakeDatabaseConnector;
use Tests\TestCase;

class DatabaseAndEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        config(['installer.installed' => false]);

        $this->tempDir = sys_get_temp_dir().'/simplepos-env-'.uniqid('', true);
        mkdir($this->tempDir);

        file_put_contents($this->tempDir.'/.env.example', implode(PHP_EOL, [
            'APP_NAME=SimplePOS',
            'APP_ENV=local',
            'APP_KEY=',
            'APP_DEBUG=true',
            'APP_URL=http://localhost:8000',
            'DB_CONNECTION=mysql',
            'DB_HOST=127.0.0.1',
            'DB_PORT=3306',
            'DB_DATABASE=simplepos',
            'DB_USERNAME=root',
            'DB_PASSWORD=',
            'SESSION_DRIVER=file',
            'CACHE_STORE=file',
            'QUEUE_CONNECTION=sync',
            'FILESYSTEM_DISK=local',
        ]).PHP_EOL);

        config([
            'installer.env_path' => $this->tempDir.'/.env',
            'installer.env_example_path' => $this->tempDir.'/.env.example',
        ]);

        $this->app->instance(DatabaseConnector::class, new FakeDatabaseConnector(true));
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempDir.'/.env')) {
            unlink($this->tempDir.'/.env');
        }

        if (is_file($this->tempDir.'/.env.example')) {
            unlink($this->tempDir.'/.env.example');
        }

        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    public function test_database_and_environment_steps_write_env_and_generate_key(): void
    {
        $this->advanceToDatabaseStep();

        $this->get(route('install.database'))
            ->assertOk()
            ->assertSee('Database connection', false)
            ->assertSee('Test connection and continue', false);

        $this->post(route('install.database.store'), [
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_database' => 'simplepos',
            'db_username' => 'root',
            'db_password' => 'db-secret',
        ])->assertRedirect(route('install.environment'))
            ->assertSessionHas('status', 'database-verified');

        $this->get(route('install.environment'))
            ->assertOk()
            ->assertSee('Application environment', false);

        $response = $this->post(route('install.environment.store'), [
            'app_url' => 'https://pos.example.test',
            'timezone' => 'Asia/Jakarta',
            'app_env' => 'production',
            'app_debug' => '0',
        ]);

        $response->assertRedirect(route('install.migrate'))
            ->assertSessionHas('status', 'environment-saved')
            ->assertDontSee('base64:', false);

        $contents = (string) file_get_contents($this->tempDir.'/.env');

        $this->assertStringContainsString('DB_HOST=127.0.0.1', $contents);
        $this->assertStringContainsString('DB_PASSWORD=db-secret', $contents);
        $this->assertStringContainsString('APP_ENV=production', $contents);
        $this->assertStringContainsString('APP_DEBUG=false', $contents);
        $this->assertStringContainsString('QUEUE_CONNECTION=sync', $contents);
        $this->assertStringNotContainsString('APP_INSTALLED=', $contents);
        $normalized = str_replace("\r\n", "\n", $contents);
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:.+$/m', $normalized);
        $this->assertTrue(InstallState::environmentWritten());
    }

    public function test_failed_database_connection_does_not_write_env_file(): void
    {
        $this->app->instance(DatabaseConnector::class, new FakeDatabaseConnector(false));

        $this->advanceToDatabaseStep();

        $this->from(route('install.database'))
            ->post(route('install.database.store'), [
                'db_host' => '127.0.0.1',
                'db_port' => 3306,
                'db_database' => 'simplepos',
                'db_username' => 'root',
                'db_password' => 'super-secret-db-pass',
            ])
            ->assertRedirect(route('install.database'))
            ->assertSessionHasErrors('database');

        $this->assertFileDoesNotExist($this->tempDir.'/.env');
    }

    public function test_environment_step_requires_verified_database_session(): void
    {
        $this->advanceToDatabaseStep();
        InstallState::advanceToEnvironment();

        $this->get(route('install.environment'))
            ->assertRedirect(route('install.database'));
    }

    private function advanceToDatabaseStep(): void
    {
        $this->post(route('install.welcome.continue'));
        $this->post(route('install.requirements.continue'));
        $this->post(route('install.extensions.continue'));
        $this->post(route('install.permissions.continue'));
    }
}
