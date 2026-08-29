<?php

declare(strict_types=1);

namespace App\Livewire\Configuration;

use App\Actions\Configuration\UpdateStoreSettings;
use App\Enums\Currency;
use App\Support\StoreSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class StoreSettingsForm extends Component
{
    use WithFileUploads;

    public string $store_name = '';

    public ?string $address = null;

    public ?string $phone = null;

    public ?string $email = null;

    public string $currency_code = '';

    public string $currency_symbol = '';

    public ?string $receipt_footer = null;

    public string $low_stock_threshold = '5';

    public mixed $logo = null;

    public function mount(): void
    {
        $settings = StoreSettings::current();
        $this->authorize('update', $settings);

        $this->store_name = $settings->store_name;
        $this->address = $settings->address;
        $this->phone = $settings->phone;
        $this->email = $settings->email;
        $this->currency_code = $settings->currency_code;
        $this->currency_symbol = $settings->currency_symbol;
        $this->receipt_footer = $settings->receipt_footer;
        $this->low_stock_threshold = (string) $settings->low_stock_threshold;
    }

    public function updatedCurrencyCode(string $code): void
    {
        $currency = Currency::tryFrom($code);
        $this->currency_symbol = $currency?->symbol() ?? $this->currency_symbol;
    }

    public function save(UpdateStoreSettings $action): void
    {
        $settings = StoreSettings::current();
        $this->authorize('update', $settings);

        $this->address = $this->normalizeOptionalString($this->address);
        $this->phone = $this->normalizeOptionalString($this->phone);
        $this->email = $this->normalizeOptionalString($this->email);
        $this->receipt_footer = $this->normalizeOptionalString($this->receipt_footer);

        $data = $this->validate();
        unset($data['logo'], $data['currency_symbol']);

        $logo = $this->logo instanceof UploadedFile ? $this->logo : null;

        $action->execute(auth()->user(), $settings, $data, $logo);

        session()->flash('success', 'Settings saved.');

        $this->redirect(route('settings.edit'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:200'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:191'],
            'currency_code' => ['required', Rule::in(Currency::codes())],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'receipt_footer' => ['nullable', 'string'],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:'.UpdateStoreSettings::LOGO_MAX_KILOBYTES,
            ],
            'low_stock_threshold' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
        ];
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    public function render(): View
    {
        return view('livewire.configuration.store-settings-form', [
            'settings' => StoreSettings::current(),
            'currencies' => Currency::cases(),
            'logoMaxMegabytes' => UpdateStoreSettings::LOGO_MAX_KILOBYTES / 1024,
        ])->extends('layouts.app', [
            'heading' => 'Settings',
            'title' => 'Settings — '.config('app.name'),
        ])->section('content');
    }
}
