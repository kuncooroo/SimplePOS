<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Currency;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $currency = Currency::Idr;

        StoreSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'store_name' => 'SimplePOS Store',
                'address' => null,
                'phone' => null,
                'email' => null,
                'currency_code' => $currency->value,
                'currency_symbol' => $currency->symbol(),
                'receipt_footer' => null,
                'logo_path' => null,
                'low_stock_threshold' => '5.000',
            ],
        );
    }
}
