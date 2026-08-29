<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'active' => 'boolean',
        ];
    }

    public function resolvedRole(): ?UserRole
    {
        $raw = $this->attributes['role'] ?? null;

        if ($raw instanceof UserRole) {
            return $raw;
        }

        if (is_string($raw) && $raw !== '') {
            return UserRole::tryFrom($raw);
        }

        return null;
    }

    public function isOwner(): bool
    {
        return $this->resolvedRole()?->isOwner() === true;
    }

    public function isAdministrator(): bool
    {
        return $this->resolvedRole()?->isAdministrator() === true;
    }

    public function isCashier(): bool
    {
        return $this->resolvedRole()?->isCashier() === true;
    }
}
