<?php

declare(strict_types=1);

namespace App\Actions\Install;

use App\Support\Install\InstallerGuard;
use Illuminate\Validation\ValidationException;

final class WriteEnvironmentFile
{
    /**
     * @param  array{host: string, port: int|string, database: string, username: string, password?: string|null}  $database
     * @param  array{app_url: string, timezone: string, app_env: string, app_debug: bool}  $application
     */
    public function execute(array $database, array $application, ?string $envPath = null, ?string $examplePath = null): void
    {
        InstallerGuard::ensureUninstalled();

        $envPath ??= (string) config('installer.env_path', base_path('.env'));
        $examplePath ??= (string) config('installer.env_example_path', base_path('.env.example'));

        if (! is_file($envPath)) {
            if (! is_file($examplePath)) {
                throw ValidationException::withMessages([
                    'environment' => 'The environment template file is missing.',
                ]);
            }

            if (@copy($examplePath, $envPath) === false) {
                throw ValidationException::withMessages([
                    'environment' => 'The .env file could not be created. Check folder permissions and try again.',
                ]);
            }
        }

        if (! is_writable($envPath)) {
            throw ValidationException::withMessages([
                'environment' => 'The .env file is not writable. Check folder permissions and try again.',
            ]);
        }

        $content = str_replace("\r\n", "\n", (string) file_get_contents($envPath));

        $updates = [
            'APP_NAME' => 'SimplePOS',
            'APP_ENV' => $application['app_env'],
            'APP_DEBUG' => $application['app_debug'] ? 'true' : 'false',
            'APP_URL' => $application['app_url'],
            'APP_TIMEZONE' => $application['timezone'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $database['host'],
            'DB_PORT' => (string) $database['port'],
            'DB_DATABASE' => $database['database'],
            'DB_USERNAME' => $database['username'],
            'DB_PASSWORD' => (string) ($database['password'] ?? ''),
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
            'FILESYSTEM_DISK' => 'local',
        ];

        foreach ($updates as $key => $value) {
            $content = $this->setEnvValue($content, $key, $value);
        }

        if (@file_put_contents($envPath, str_replace("\n", PHP_EOL, $content)) === false) {
            throw ValidationException::withMessages([
                'environment' => 'The .env file could not be saved. Check folder permissions and try again.',
            ]);
        }

        @chmod($envPath, 0600);
    }

    private function setEnvValue(string $content, string $key, string $value): string
    {
        $line = $key.'='.$this->formatEnvValue($value);
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $content) === 1) {
            return (string) preg_replace($pattern, $line, $content);
        }

        return rtrim($content).PHP_EOL.$line.PHP_EOL;
    }

    private function formatEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z0-9_.@+\/-]*$/', $value) === 1 && $value !== '') {
            return $value;
        }

        return '"'.str_replace('"', '\\"', $value).'"';
    }
}
