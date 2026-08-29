<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreSetting>
 */
class StoreSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = Currency::Idr;

        return [
            'store_name' => 'SimplePOS Store',
            'address' => null,
            'phone' => null,
            'email' => null,
            'currency_code' => $currency->value,
            'currency_symbol' => $currency->symbol(),
            'receipt_footer' => null,
            'logo_path' => null,
            'low_stock_threshold' => '5.000',
        ];
    }
}
