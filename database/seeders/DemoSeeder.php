<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Optional demo data for local manual QA.
 *
 * Run with: php artisan db:seed --class=DemoSeeder
 *
 * Default passwords come from .env.example (password). Do not use in production.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OwnerSeeder::class,
            StoreSettingsSeeder::class,
            InstallDemoSeeder::class,
        ]);
    }
}
