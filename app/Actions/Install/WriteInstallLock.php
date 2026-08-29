<?php



declare(strict_types=1);



namespace App\Actions\Install;



use App\Support\Install\InstallState;

use Illuminate\Validation\ValidationException;



final class WriteInstallLock

{

    public function execute(?string $envPath = null): void

    {

        if (InstallState::hasInstallLock()) {

            return;

        }



        $appKey = (string) config('app.key');



        if ($appKey === '') {

            throw ValidationException::withMessages([

                'install' => 'The application key must be set before finishing installation.',

            ]);

        }



        $lockPath = storage_path('app/installed');

        $directory = dirname($lockPath);



        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {

            throw ValidationException::withMessages([

                'install' => 'The installer lock file could not be created. Check storage permissions and try again.',

            ]);

        }



        $payload = now()->toIso8601String().PHP_EOL.hash('sha256', $appKey);



        if (@file_put_contents($lockPath, $payload) === false) {

            throw ValidationException::withMessages([

                'install' => 'The installer lock file could not be written. Check storage permissions and try again.',

            ]);

        }



        @chmod($lockPath, 0640);



        $this->markApplicationInstalled($envPath);

    }



    private function markApplicationInstalled(?string $envPath): void

    {

        $envPath ??= (string) config('installer.env_path', base_path('.env'));



        if (is_file($envPath) && is_writable($envPath)) {

            $content = str_replace("\r\n", "\n", (string) file_get_contents($envPath));

            $line = 'APP_INSTALLED=true';

            $pattern = '/^APP_INSTALLED=.*/m';



            if (preg_match($pattern, $content) === 1) {

                $content = (string) preg_replace($pattern, $line, $content);

            } else {

                $content = rtrim($content).PHP_EOL.$line.PHP_EOL;

            }



            if (@file_put_contents($envPath, str_replace("\n", PHP_EOL, $content)) === false) {

                throw ValidationException::withMessages([

                    'install' => 'The .env file could not be updated with APP_INSTALLED=true.',

                ]);

            }

        }



        config(['installer.installed' => true]);

    }

}

