<?php

declare(strict_types=1);

namespace App\Actions\Install;

use App\Support\Install\InstallerGuard;
use App\Support\Install\MigrationRunner;
use Illuminate\Support\Facades\Artisan;

final class RunMigrations
{
    public function __construct(
        private MigrationRunner $migrationRunner,
    ) {}

    public function execute(): void
    {
        InstallerGuard::ensureUninstalled();

        $this->migrationRunner->migrate();
    }
}
