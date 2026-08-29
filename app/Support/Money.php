<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    public static function format(mixed $amount): string
    {
        $settings = StoreSettings::current();
        $decimals = $settings->currency()->decimalPlaces();
        $number = is_numeric($amount) ? (float) $amount : 0.0;

        return $settings->currency_symbol.' '.number_format($number, $decimals, ',', '.');
    }
}
