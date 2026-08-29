<div>
    <div class="rounded-md border border-line bg-surface">
        <div class="border-b border-line px-4 py-3">
            <h2 class="text-sm font-semibold text-ink">Payment</h2>
            <p class="text-xs text-muted">Cash only. Totals are recalculated on the server at checkout.</p>
        </div>

        <div class="space-y-4 p-4 text-sm">
            @error('checkout')
                <x-alert type="error">{{ $message }}</x-alert>
            @enderror

            @error('payment')
                <x-alert type="error">{{ $message }}</x-alert>
            @enderror

            @error('cart')
                <x-alert type="error">{{ $message }}</x-alert>
            @enderror

            @error('stock')
                <x-alert type="error">{{ $message }}</x-alert>
            @enderror

            <div class="space-y-2">
                <div class="flex justify-between text-muted">
                    <span>Subtotal</span>
                    <x-money :amount="$preview->subtotal" />
                </div>
                <div class="flex justify-between text-muted">
                    <span>Discount</span>
                    <x-money :amount="$preview->discount" />
                </div>
                <div class="flex justify-between font-medium text-ink">
                    <span>Amount due</span>
                    <x-money :amount="$preview->total" />
                </div>
            </div>

            <div class="grid gap-3">
                <div>
                    <x-input
                        label="Discount"
                        type="number"
                        min="0"
                        step="1"
                        wire:model.live.debounce.300ms="discount"
                        :disabled="! $canCheckout"
                        inputmode="decimal"
                    />
                    @error('discount')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input
                        label="Cash received"
                        type="number"
                        min="0"
                        step="1"
                        wire:model.live.debounce.300ms="cashReceived"
                        :disabled="! $canCheckout"
                        inputmode="decimal"
                    />
                    @error('cashReceived')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="rounded-md border border-line bg-canvas px-3 py-2">
                <div class="flex justify-between font-medium text-ink">
                    <span>Change</span>
                    <x-money :amount="$preview->change" />
                </div>
                @if (! $preview->paymentSufficient && $canCheckout && is_numeric($cashReceived) && (float) $cashReceived > 0)
                    <p class="mt-1 text-xs text-warning">Cash received is less than the amount due.</p>
                @endif
            </div>

            @if ($canCheckout)
                <x-button type="button" class="w-full" wire:click="requestCheckout">
                    Confirm checkout
                </x-button>
            @else
                <x-button type="button" class="w-full" disabled>
                    Confirm checkout
                </x-button>
            @endif
        </div>
    </div>

    <x-modal :open="$showConfirmDialog" title="Complete sale?">
        <p class="text-sm text-muted">
            Review the payment summary before completing this cash sale.
        </p>

        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-muted">Total</dt>
                <dd class="font-medium text-ink"><x-money :amount="$preview->total" /></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-muted">Cash received</dt>
                <dd class="font-medium text-ink"><x-money :amount="$preview->cashReceived" /></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-muted">Change</dt>
                <dd class="font-medium text-ink"><x-money :amount="$preview->change" /></dd>
            </div>
        </dl>

        <div class="mt-4 flex justify-end gap-2">
            <x-button type="button" variant="secondary" wire:click="cancelCheckout" wire:loading.attr="disabled" wire:target="confirmCheckout">
                Back
            </x-button>
            <x-button
                type="button"
                wire:click="confirmCheckout"
                wire:loading.attr="disabled"
                wire:target="confirmCheckout"
            >
                <span wire:loading.remove wire:target="confirmCheckout">Complete Sale</span>
                <span wire:loading wire:target="confirmCheckout">Processing...</span>
            </x-button>
        </div>
    </x-modal>
</div>
