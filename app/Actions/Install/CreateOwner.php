<?php

declare(strict_types=1);

namespace App\Actions\Install;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Install\InstallerGuard;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class CreateOwner
{
    public function execute(string $name, string $email, string $password): User
    {
        if (! User::query()->where('role', UserRole::Owner)->exists()) {
            InstallerGuard::ensureUninstalled();
        }

        $existingOwner = User::query()->where('role', UserRole::Owner)->first();

        if ($existingOwner !== null) {
            if ($existingOwner->email === $email) {
                return $existingOwner;
            }

            throw ValidationException::withMessages([
                'email' => 'An Owner account already exists for this store.',
            ]);
        }

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email address is already in use.',
            ]);
        }

        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Owner,
            'active' => true,
        ]);
    }

    public function executeWithPasswordHash(string $name, string $email, string $passwordHash): User
    {
        if (! User::query()->where('role', UserRole::Owner)->exists()) {
            InstallerGuard::ensureUninstalled();
        }

        $existingOwner = User::query()->where('role', UserRole::Owner)->first();

        if ($existingOwner !== null) {
            if ($existingOwner->email === $email) {
                return $existingOwner;
            }

            throw ValidationException::withMessages([
                'email' => 'An Owner account already exists for this store.',
            ]);
        }

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email address is already in use.',
            ]);
        }

        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'role' => UserRole::Owner,
            'active' => true,
        ]);
    }
}
