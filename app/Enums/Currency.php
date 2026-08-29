<?php

declare(strict_types=1);

namespace App\Enums;

enum Currency: string
{
    case Idr = 'IDR';
    case Usd = 'USD';

    public function symbol(): string
    {
        return match ($this) {
            self::Idr => 'Rp',
            self::Usd => '$',
        };
    }

    public function decimalPlaces(): int
    {
        return match ($this) {
            self::Idr => 0,
            self::Usd => 2,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Idr => 'IDR (Rp)',
            self::Usd => 'USD ($)',
        };
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_map(
            static fn (self $currency): string => $currency->value,
            self::cases(),
        );
    }
}
