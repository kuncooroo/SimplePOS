<?php

declare(strict_types=1);

namespace App\Support\Install;

use App\Enums\UserRole;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

final class InstallState
{
    public const SESSION_KEY = 'simplepos.install.step';

    public const STEP_WELCOME = 1;

    public const STEP_REQUIREMENTS = 2;

    public const STEP_EXTENSIONS = 3;

    public const STEP_PERMISSIONS = 4;

    public const STEP_DATABASE = 5;

    public const STEP_ENVIRONMENT = 6;

    public const STEP_MIGRATE = 7;

    public const STEP_OWNER = 8;

    public const STEP_SETTINGS = 9;

    public const STEP_DEMO = 10;

    public const STEP_COMPLETE = 11;

    public const SESSION_DATABASE_KEY = 'simplepos.install.database';

    public const SESSION_DATABASE_VERIFIED_KEY = 'simplepos.install.database_verified';

    public const SESSION_ENVIRONMENT_WRITTEN_KEY = 'simplepos.install.environment_written';

    public const SESSION_MIGRATIONS_COMPLETED_KEY = 'simplepos.install.migrations_completed';

    public const SESSION_PENDING_OWNER_KEY = 'simplepos.install.pending_owner';

    public static function isInstalled(): bool
    {
        $envInstalled = config('installer.installed');

        if ($envInstalled !== null && $envInstalled !== '' && filter_var($envInstalled, FILTER_VALIDATE_BOOL)) {
            return true;
        }

        return self::hasInstallLock() || self::hasOwnerAccount();
    }

    public static function hasInstallLock(): bool
    {
        return is_file(storage_path('app/installed'));
    }

    public static function hasOwnerAccount(): bool
    {
        try {
            if (! Schema::hasTable('users')) {
                return false;
            }

            return User::query()->where('role', UserRole::Owner)->exists();
        } catch (QueryException) {
            return false;
        }
    }

    public static function currentStep(): int
    {
        return (int) session()->get(self::SESSION_KEY, self::STEP_WELCOME);
    }

    public static function setStep(int $step): void
    {
        session()->put(self::SESSION_KEY, $step);
    }

    public static function advanceToRequirements(): void
    {
        self::setStep(self::STEP_REQUIREMENTS);
    }

    public static function advanceToExtensions(): void
    {
        self::setStep(self::STEP_EXTENSIONS);
    }

    public static function advanceToPermissions(): void
    {
        self::setStep(self::STEP_PERMISSIONS);
    }

    public static function advanceToDatabase(): void
    {
        self::setStep(self::STEP_DATABASE);
    }

    public static function advanceToEnvironment(): void
    {
        self::setStep(self::STEP_ENVIRONMENT);
    }

    public static function advanceToMigrate(): void
    {
        self::setStep(self::STEP_MIGRATE);
    }

    public static function advanceToOwner(): void
    {
        self::setStep(self::STEP_OWNER);
    }

    public static function advanceToSettings(): void
    {
        self::setStep(self::STEP_SETTINGS);
    }

    public static function advanceToDemo(): void
    {
        self::setStep(self::STEP_DEMO);
    }

    public static function advanceToComplete(): void
    {
        self::setStep(self::STEP_COMPLETE);
    }

    public static function markMigrationsCompleted(): void
    {
        session()->put(self::SESSION_MIGRATIONS_COMPLETED_KEY, true);
    }

    public static function migrationsCompleted(): bool
    {
        return session(self::SESSION_MIGRATIONS_COMPLETED_KEY) === true;
    }

    public static function hasStoreSettings(): bool
    {
        try {
            if (! Schema::hasTable('store_settings')) {
                return false;
            }

            return StoreSetting::query()->whereKey(1)->exists();
        } catch (QueryException) {
            return false;
        }
    }

    /**
     * @param  array{name: string, email: string, password_hash: string}  $owner
     */
    public static function storePendingOwner(array $owner): void
    {
        session()->put(self::SESSION_PENDING_OWNER_KEY, $owner);
    }

    /**
     * @return array{name: string, email: string, password_hash: string}|null
     */
    public static function pendingOwner(): ?array
    {
        /** @var array{name: string, email: string, password_hash: string}|null $owner */
        $owner = session(self::SESSION_PENDING_OWNER_KEY);

        return $owner;
    }

    public static function hasPendingOwner(): bool
    {
        return self::pendingOwner() !== null;
    }

    public static function canFinishInstallation(): bool
    {
        if (! self::hasStoreSettings()) {
            return false;
        }

        return self::hasPendingOwner() || self::hasOwnerAccount();
    }

    public static function invalidateWizardSession(): void
    {
        session()->forget([
            self::SESSION_KEY,
            self::SESSION_DATABASE_KEY,
            self::SESSION_DATABASE_VERIFIED_KEY,
            self::SESSION_ENVIRONMENT_WRITTEN_KEY,
            self::SESSION_MIGRATIONS_COMPLETED_KEY,
            self::SESSION_PENDING_OWNER_KEY,
        ]);
    }

    /**
     * @param  array{host: string, port: int, database: string, username: string, password: string|null}  $credentials
     */
    public static function storeVerifiedDatabaseCredentials(array $credentials): void
    {
        session()->put(self::SESSION_DATABASE_KEY, $credentials);
        session()->put(self::SESSION_DATABASE_VERIFIED_KEY, true);
    }

    /**
     * @return array{host: string, port: int, database: string, username: string, password: string|null}|null
     */
    public static function verifiedDatabaseCredentials(): ?array
    {
        if (session(self::SESSION_DATABASE_VERIFIED_KEY) !== true) {
            return null;
        }

        /** @var array{host: string, port: int, database: string, username: string, password: string|null}|null $credentials */
        $credentials = session(self::SESSION_DATABASE_KEY);

        return $credentials;
    }

    public static function clearDatabaseCredentials(): void
    {
        session()->forget([
            self::SESSION_DATABASE_KEY,
            self::SESSION_DATABASE_VERIFIED_KEY,
        ]);
    }

    public static function markEnvironmentWritten(): void
    {
        session()->put(self::SESSION_ENVIRONMENT_WRITTEN_KEY, true);
    }

    public static function environmentWritten(): bool
    {
        return session(self::SESSION_ENVIRONMENT_WRITTEN_KEY) === true;
    }
}
