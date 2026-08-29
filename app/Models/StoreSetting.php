<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use Database\Factories\StoreSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'store_name',
    'address',
    'phone',
    'email',
    'currency_code',
    'currency_symbol',
    'receipt_footer',
    'logo_path',
    'low_stock_threshold',
])]
class StoreSetting extends Model
{
    /** @use HasFactory<StoreSettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'low_stock_threshold' => 'decimal:3',
        ];
    }

    public function currency(): Currency
    {
        return Currency::tryFrom((string) $this->currency_code) ?? Currency::Idr;
    }

    public function logoUrl(): ?string
    {
        if ($this->logo_path === null || $this->logo_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
