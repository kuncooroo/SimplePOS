<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\StoreSetting;
use RuntimeException;

final class StoreSettings
{
    private static ?StoreSetting $cached = null;

    public static function current(): StoreSetting
    {
        if (self::$cached instanceof StoreSetting) {
            return self::$cached;
        }

        $settings = StoreSetting::query()->orderBy('id')->first();

        if ($settings === null) {
            throw new RuntimeException('Store settings are missing. Run the database seeders.');
        }

        return self::$cached = $settings;
    }

    public static function clearCache(): void
    {
        self::$cached = null;
    }
}
