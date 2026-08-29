<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Install\CreateOwner;
use App\Actions\Install\EnsureStorageLink;
use App\Actions\Install\GenerateApplicationKey;
use App\Actions\Install\RunMigrations;
use App\Actions\Install\SeedDemoData;
use App\Actions\Install\WriteInitialStoreSettings;
use App\Actions\Install\WriteInstallLock;
use App\Support\Install\InstallState;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'simplepos:install')]
final class SimplePosInstallCommand extends Command
{
    protected $signature = 'simplepos:install
                            {--demo : Seed demo catalog data}
                            {--lock-only : Write the installer lock when an Owner already exists}';

    protected $description = 'Install SimplePOS from the command line';

    public function handle(
        RunMigrations $runMigrations,
        EnsureStorageLink $ensureStorageLink,
        GenerateApplicationKey $generateApplicationKey,
        CreateOwner $createOwner,
        WriteInitialStoreSettings $writeInitialStoreSettings,
        SeedDemoData $seedDemoData,
        WriteInstallLock $writeInstallLock,
    ): int {
        if ($this->option('lock-only')) {
            return $this->writeLockOnly($writeInstallLock);
        }

        if (InstallState::isInstalled()) {
            $this->components->error('SimplePOS is already installed.');

            return self::FAILURE;
        }

        try {
            $generateApplicationKey->execute();
            $runMigrations->execute();
            $ensureStorageLink->execute();

            $writeInitialStoreSettings->execute($this->storeName());

            if ($this->option('demo')) {
                $result = $seedDemoData->execute(true);

                if ($result === SeedDemoData::RESULT_WARNED) {
                    $this->components->warn('Demo data was skipped because completed sales already exist.');
                }
            }

            [$name, $email, $password] = $this->ownerCredentials();

            $createOwner->execute($name, $email, $password);
            $writeInstallLock->execute();
        } catch (ValidationException $exception) {
            $this->components->error(collect($exception->errors())->flatten()->first() ?? 'Installation failed.');

            return self::FAILURE;
        }

        $this->components->info('SimplePOS installed successfully. Sign in as Owner.');

        return self::SUCCESS;
    }

    private function writeLockOnly(WriteInstallLock $writeInstallLock): int
    {
        if (InstallState::hasInstallLock()) {
            $this->components->info('Installer lock already exists.');

            return self::SUCCESS;
        }

        if (! InstallState::hasOwnerAccount()) {
            $this->components->error('Cannot write the installer lock because no Owner account exists.');

            return self::FAILURE;
        }

        try {
            $writeInstallLock->execute();
        } catch (ValidationException $exception) {
            $this->components->error(collect($exception->errors())->flatten()->first() ?? 'Lock write failed.');

            return self::FAILURE;
        }

        $this->components->info('Installer lock written.');

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function ownerCredentials(): array
    {
        if ($this->option('no-interaction')) {
            return [
                (string) env('OWNER_NAME', 'Store Owner'),
                (string) env('OWNER_EMAIL', 'owner@example.com'),
                (string) env('OWNER_PASSWORD', 'password'),
            ];
        }

        return [
            (string) $this->ask('Owner full name', (string) env('OWNER_NAME', 'Store Owner')),
            (string) $this->ask('Owner email', (string) env('OWNER_EMAIL', 'owner@example.com')),
            (string) $this->secret('Owner password'),
        ];
    }

    private function storeName(): string
    {
        if ($this->option('no-interaction')) {
            return (string) env('STORE_NAME', 'SimplePOS Store');
        }

        return (string) $this->ask('Store name', (string) env('STORE_NAME', 'SimplePOS Store'));
    }
}
