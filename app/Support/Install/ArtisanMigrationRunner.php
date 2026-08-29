<?php

declare(strict_types=1);

namespace App\Support\Install;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

final class ArtisanMigrationRunner implements MigrationRunner
{
    public function migrate(): void
    {
        $exitCode = Artisan::call('migrate', [
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw ValidationException::withMessages([
                'migrate' => 'Database migrations could not be completed. Check the server logs and try again.',
            ]);
        }
    }
}
