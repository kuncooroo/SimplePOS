<div>
    <x-modal :open="$showModal" title="Adjust stock">
        @if ($productId !== null)
            <dl class="mb-4 space-y-2 text-sm">
                <div>
                    <dt class="text-muted">Product</dt>
                    <dd class="font-medium text-ink">{{ $productName }}</dd>
                </div>
                <div>
                    <dt class="text-muted">SKU</dt>
                    <dd>{{ $productSku }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Current stock</dt>
                    <dd class="tabular-nums">{{ $currentStock }}</dd>
                </div>
            </dl>

            <form wire:submit="confirm" class="space-y-4">
                <div>
                    <x-input
                        label="Quantity change"
                        name="quantityChange"
                        type="text"
                        inputmode="decimal"
                        placeholder="Use + or -, e.g. 5 or -2.5"
                        wire:model="quantityChange"
                    />
                    <p class="mt-1 text-xs text-muted">Enter a positive or negative amount. Stock is updated by this value.</p>
                    @error('quantityChange')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="adjust-reason" class="text-sm font-medium text-ink">Reason</label>
                    <textarea
                        id="adjust-reason"
                        wire:model="reason"
                        rows="3"
                        maxlength="1000"
                        class="rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-muted"
                        placeholder="Why is stock being changed?"
                    ></textarea>
                    @error('reason')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <x-button type="button" variant="secondary" wire:click="cancel" :disabled="$isProcessing">
                        Cancel
                    </x-button>
                    <x-button type="submit" :disabled="$isProcessing">
                        Confirm adjustment
                    </x-button>
                </div>
            </form>
        @endif
    </x-modal>
</div>
