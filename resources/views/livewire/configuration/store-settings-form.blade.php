<div>
    <x-page-header title="Settings">
        <x-slot:description>One store only. Changes apply to new receipts and screens; they do not rewrite past sales.</x-slot:description>
    </x-page-header>

    <form wire:submit="save" class="max-w-2xl space-y-6" enctype="multipart/form-data">
        <section class="space-y-4 rounded-md border border-line bg-surface p-4">
            <h2 class="text-sm font-semibold text-ink">Store information</h2>

            <x-input
                label="Store name"
                name="store_name"
                wire:model="store_name"
                required
                maxlength="200"
            />
            @error('store_name')
                <p class="text-sm text-danger">{{ $message }}</p>
            @enderror

            <div class="flex flex-col gap-1">
                <label for="address" class="text-sm font-medium text-ink">Address</label>
                <textarea
                    id="address"
                    wire:model="address"
                    rows="3"
                    class="rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"
                ></textarea>
                @error('address')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input
                        label="Phone"
                        name="phone"
                        wire:model="phone"
                        maxlength="50"
                    />
                    @error('phone')
                        <p class="text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <x-input
                        label="Email"
                        name="email"
                        type="email"
                        wire:model="email"
                        maxlength="191"
                    />
                    @error('email')
                        <p class="text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label for="logo" class="text-sm font-medium text-ink">Logo</label>
                @if ($settings->logoUrl())
                    <img
                        src="{{ $settings->logoUrl() }}"
                        alt="Store logo"
                        class="h-16 w-16 rounded-md border border-line object-contain bg-canvas"
                    >
                @endif
                <input
                    id="logo"
                    type="file"
                    wire:model="logo"
                    accept="image/png,image/jpeg,image/webp"
                    class="text-sm text-ink"
                >
                <p class="text-xs text-muted">PNG, JPEG, or WebP. Maximum {{ $logoMaxMegabytes }} MB. SVG is not allowed. A failed upload keeps the current logo.</p>
                @error('logo')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>
        </section>

        <section class="space-y-4 rounded-md border border-line bg-surface p-4">
            <h2 class="text-sm font-semibold text-ink">Receipt</h2>
            <div class="flex flex-col gap-1">
                <label for="receipt_footer" class="text-sm font-medium text-ink">Receipt footer</label>
                <textarea
                    id="receipt_footer"
                    wire:model="receipt_footer"
                    rows="3"
                    class="rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"
                ></textarea>
                @error('receipt_footer')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>
        </section>

        <section class="space-y-4 rounded-md border border-line bg-surface p-4">
            <h2 class="text-sm font-semibold text-ink">Localization</h2>

            <div class="flex flex-col gap-1">
                <label for="currency_code" class="text-sm font-medium text-ink">Currency</label>
                <select
                    id="currency_code"
                    wire:model.live="currency_code"
                    class="rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"
                >
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency->value }}">{{ $currency->label() }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-muted">Symbol used on screen: {{ $currency_symbol }}</p>
                @error('currency_code')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <x-input
                label="Low-stock threshold"
                name="low_stock_threshold"
                inputmode="decimal"
                wire:model="low_stock_threshold"
                required
            />
            <p class="text-xs text-muted">Products at or below this quantity are treated as low stock. Zero is allowed.</p>
            @error('low_stock_threshold')
                <p class="text-sm text-danger">{{ $message }}</p>
            @enderror
        </section>

        <x-button type="submit" wire:loading.attr="disabled">Save settings</x-button>
    </form>
</div>
