<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'OWNER';
    case Administrator = 'ADMINISTRATOR';
    case Cashier = 'CASHIER';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Administrator => 'Administrator',
            self::Cashier => 'Cashier',
        };
    }

    public function isOwner(): bool
    {
        return $this === self::Owner;
    }

    public function isAdministrator(): bool
    {
        return $this === self::Administrator;
    }

    public function isCashier(): bool
    {
        return $this === self::Cashier;
    }
}
