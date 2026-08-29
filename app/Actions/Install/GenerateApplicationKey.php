<?php

declare(strict_types=1);

namespace App\Actions\Install;

use App\Support\Install\InstallerGuard;
use Illuminate\Validation\ValidationException;

final class GenerateApplicationKey
{
    public function execute(?string $envPath = null): bool
    {
        InstallerGuard::ensureUninstalled();

        $envPath ??= (string) config('installer.env_path', base_path('.env'));

        if (! is_file($envPath) || ! is_readable($envPath)) {
            throw ValidationException::withMessages([
                'environment' => 'The .env file is missing and the application key could not be generated.',
            ]);
        }

        $content = str_replace("\r\n", "\n", (string) file_get_contents($envPath));

        if ($this->existingKey($content) !== '') {
            return false;
        }

        $key = 'base64:'.base64_encode(random_bytes(32));
        $updated = preg_replace('/^APP_KEY=.*/m', 'APP_KEY='.$key, $content);

        if (! is_string($updated)) {
            $updated = rtrim($content).PHP_EOL.'APP_KEY='.$key.PHP_EOL;
        }

        if (@file_put_contents($envPath, str_replace("\n", PHP_EOL, $updated)) === false) {
            throw ValidationException::withMessages([
                'environment' => 'The application key could not be saved to the .env file.',
            ]);
        }

        $this->reloadApplicationKey($key);

        return true;
    }

    private function existingKey(string $content): string
    {
        if (preg_match('/^APP_KEY=(.*)$/m', $content, $matches) !== 1) {
            return '';
        }

        return trim($matches[1], " \t\"'\r\n");
    }

    private function reloadApplicationKey(string $key): void
    {
        putenv('APP_KEY='.$key);
        $_ENV['APP_KEY'] = $key;
        $_SERVER['APP_KEY'] = $key;

        config(['app.key' => $key]);
    }
}
