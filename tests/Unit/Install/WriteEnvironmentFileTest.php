<?php

declare(strict_types=1);

namespace Tests\Unit\Install;

use App\Actions\Install\GenerateApplicationKey;
use App\Actions\Install\WriteEnvironmentFile;
use Tests\TestCase;

class WriteEnvironmentFileTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/simplepos-env-'.uniqid('', true);
        mkdir($this->tempDir);

        config([
            'installer.installed' => false,
            'installer.env_path' => $this->tempDir.'/.env',
            'installer.env_example_path' => $this->tempDir.'/.env.example',
        ]);
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

    public function test_it_writes_allowed_keys_without_marking_the_app_installed(): void
    {
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

        app(WriteEnvironmentFile::class)->execute(
            database: [
                'host' => 'db.internal',
                'port' => 3307,
                'database' => 'shop_db',
                'username' => 'shop_user',
                'password' => 's3cret!',
            ],
            application: [
                'app_url' => 'https://pos.example.test',
                'timezone' => 'Asia/Jakarta',
                'app_env' => 'production',
                'app_debug' => false,
            ],
        );

        $contents = (string) file_get_contents($this->tempDir.'/.env');

        $this->assertStringContainsString('APP_ENV=production', $contents);
        $this->assertStringContainsString('APP_DEBUG=false', $contents);
        $this->assertStringContainsString('APP_URL="https://pos.example.test"', $contents);
        $this->assertStringContainsString('APP_TIMEZONE=Asia/Jakarta', $contents);
        $this->assertStringContainsString('DB_HOST=db.internal', $contents);
        $this->assertStringContainsString('DB_PORT=3307', $contents);
        $this->assertStringContainsString('DB_DATABASE=shop_db', $contents);
        $this->assertStringContainsString('DB_USERNAME=shop_user', $contents);
        $this->assertStringContainsString('DB_PASSWORD="s3cret!"', $contents);
        $this->assertStringContainsString('SESSION_DRIVER=file', $contents);
        $this->assertStringContainsString('QUEUE_CONNECTION=sync', $contents);
        $this->assertStringNotContainsString('APP_INSTALLED=', $contents);
        $this->assertStringNotContainsString('STORE_NAME=', $contents);
    }

    public function test_generate_application_key_only_when_missing(): void
    {
        file_put_contents($this->tempDir.'/.env', "APP_KEY=\n");

        $generated = app(GenerateApplicationKey::class)->execute();

        $this->assertTrue($generated);
        $contents = (string) file_get_contents($this->tempDir.'/.env');
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:.+$/m', $contents);

        $this->assertFalse(app(GenerateApplicationKey::class)->execute());
    }
}
