<?php

declare(strict_types=1);

namespace App\Actions\Install;

use App\Enums\Currency;
use App\Models\StoreSetting;
use App\Support\Install\InstallerGuard;

final class WriteInitialStoreSettings
{
    public function execute(string $storeName, ?string $address = null): StoreSetting
    {
        InstallerGuard::ensureUninstalled();

        $currency = Currency::Idr;

        /** @var StoreSetting $settings */
        $settings = StoreSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'store_name' => $storeName,
                'address' => $address !== '' ? $address : null,
                'phone' => null,
                'email' => null,
                'currency_code' => $currency->value,
                'currency_symbol' => $currency->symbol(),
                'receipt_footer' => null,
                'logo_path' => null,
                'low_stock_threshold' => '5.000',
            ],
        );

        return $settings;
    }
}
