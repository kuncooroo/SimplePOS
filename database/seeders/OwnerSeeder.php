<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class OwnerSeeder extends Seeder
{
    /**
     * Create the protected Owner for local development.
     */
    public function run(): void
    {
        $email = (string) env('OWNER_EMAIL', 'owner@example.com');
        $name = (string) env('OWNER_NAME', 'Store Owner');
        $password = (string) env('OWNER_PASSWORD', '');

        if ($password === '') {
            if (! app()->environment('local', 'testing')) {
                $this->command?->warn('OWNER_PASSWORD is not set; skipping Owner seeder.');

                return;
            }

            $password = 'password';
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => UserRole::Owner,
                'active' => true,
            ],
        );
    }
}
