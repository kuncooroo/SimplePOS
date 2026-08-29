<?php

declare(strict_types=1);

namespace App\Actions\Install;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Support\Install\InstallerGuard;
use Database\Seeders\InstallDemoSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class SeedDemoData
{
    public const RESULT_SKIPPED = 'skipped';

    public const RESULT_SEEDED = 'seeded';

    public const RESULT_REFUSED = 'refused';

    public const RESULT_WARNED = 'warned';

    public function execute(bool $requested): string
    {
        InstallerGuard::ensureUninstalled();

        if (! $requested) {
            return self::RESULT_SKIPPED;
        }

        if (! Schema::hasTable('categories') || ! Schema::hasTable('products')) {
            throw ValidationException::withMessages([
                'demo' => 'Demo catalog data cannot be loaded because product tables are missing.',
            ]);
        }

        if (Schema::hasTable('transactions') && Transaction::query()->where('status', TransactionStatus::Completed)->exists()) {
            return self::RESULT_WARNED;
        }

        (new InstallDemoSeeder)->run();

        return self::RESULT_SEEDED;
    }
}
