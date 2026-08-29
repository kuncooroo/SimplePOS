<?php

declare(strict_types=1);

namespace App\Actions\Configuration;

use App\Actions\Audit\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\Currency;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\StoreSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UpdateStoreSettings
{
    public const LOGO_DISK = 'public';

    public const LOGO_DIRECTORY = 'logos';

    public const LOGO_MAX_KILOBYTES = 2048;

    public function __construct(
        private RecordActivity $recordActivity,
    ) {}

    /**
     * @param  array{
     *     store_name: string,
     *     address?: string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     currency_code: string,
     *     receipt_footer?: string|null,
     *     low_stock_threshold: mixed
     * }  $data
     */
    public function execute(User $actor, StoreSetting $settings, array $data, ?UploadedFile $logo = null): StoreSetting
    {
        Gate::forUser($actor)->authorize('update', $settings);

        $currency = Currency::from($data['currency_code']);
        $oldSnapshot = $this->snapshot($settings);
        $previousLogoPath = $settings->logo_path;
        $storedNewPath = null;

        if ($logo !== null) {
            $storedNewPath = $logo->store(self::LOGO_DIRECTORY, self::LOGO_DISK);

            if (! is_string($storedNewPath) || $storedNewPath === '') {
                throw new RuntimeException('The logo could not be stored.');
            }
        }

        try {
            $updated = DB::transaction(function () use ($actor, $settings, $data, $currency, $storedNewPath, $previousLogoPath, $oldSnapshot): StoreSetting {
                $settings->update([
                    'store_name' => $data['store_name'],
                    'address' => $this->nullableString($data['address'] ?? null),
                    'phone' => $this->nullableString($data['phone'] ?? null),
                    'email' => $this->nullableString($data['email'] ?? null),
                    'currency_code' => $currency->value,
                    'currency_symbol' => $currency->symbol(),
                    'receipt_footer' => $this->nullableString($data['receipt_footer'] ?? null),
                    'logo_path' => $storedNewPath ?? $previousLogoPath,
                    'low_stock_threshold' => $data['low_stock_threshold'],
                ]);

                $fresh = $settings->refresh();

                $this->recordActivity->execute(
                    actor: $actor,
                    action: ActivityAction::StoreSettingsUpdated,
                    subject: $fresh,
                    oldValues: $oldSnapshot,
                    newValues: $this->snapshot($fresh),
                );

                return $fresh;
            });
        } catch (Throwable $exception) {
            if (is_string($storedNewPath) && $storedNewPath !== '') {
                Storage::disk(self::LOGO_DISK)->delete($storedNewPath);
            }

            throw $exception;
        }

        if (is_string($storedNewPath) && $previousLogoPath !== null && $previousLogoPath !== $storedNewPath) {
            Storage::disk(self::LOGO_DISK)->delete($previousLogoPath);
        }

        StoreSettings::clearCache();

        return $updated;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(StoreSetting $settings): array
    {
        return [
            'store_name' => $settings->store_name,
            'address' => $settings->address,
            'phone' => $settings->phone,
            'email' => $settings->email,
            'currency_code' => $settings->currency_code,
            'currency_symbol' => $settings->currency_symbol,
            'receipt_footer' => $settings->receipt_footer,
            'logo_path' => $settings->logo_path,
            'low_stock_threshold' => $settings->low_stock_threshold,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
