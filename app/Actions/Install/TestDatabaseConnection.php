<?php

declare(strict_types=1);

namespace App\Actions\Install;

use App\Support\Install\DatabaseConnector;
use App\Support\Install\InstallerGuard;
use Illuminate\Validation\ValidationException;

final class TestDatabaseConnection
{
    public function __construct(
        private DatabaseConnector $connector,
    ) {}

    public function execute(
        string $host,
        int $port,
        string $database,
        string $username,
        ?string $password,
    ): void {
        InstallerGuard::ensureUninstalled();

        try {
            $this->connector->test($host, $port, $database, $username, $password ?? '');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'database' => 'Unable to connect with these database credentials.',
            ]);
        }
    }
}
