<div class="flex h-full min-h-[28rem] flex-col rounded-md border border-line bg-surface">
    <div class="border-b border-line px-4 py-3">
        <h2 class="text-sm font-semibold text-ink">Cart</h2>
        <p class="text-xs text-muted">Tap a search result to add items.</p>
    </div>

    @error('cart')
        <x-alert type="error" class="mx-4 mt-4">{{ $message }}</x-alert>
    @enderror

    <div class="flex flex-1 flex-col justify-between p-4">
        @if ($isEmpty)
            <x-empty-state title="Cart is empty">
                Search for a product and select it to add a line. Payment is not available until items are added.
            </x-empty-state>
        @else
            <ul class="divide-y divide-line overflow-y-auto rounded-md border border-line">
                @foreach ($lines as $line)
                    @php
                        $product = $line['product'];
                        $quantity = $line['quantity'];
                    @endphp
                    <li wire:key="cart-line-{{ $product->id }}" class="px-3 py-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ $product->name }}</p>
                                <p class="mt-0.5 text-xs text-muted">SKU {{ $product->sku }}</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="decrementQuantity({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="decrementQuantity({{ $product->id }})"
                                        aria-label="Decrease quantity for {{ $product->name }}"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-line bg-surface text-sm text-ink hover:bg-canvas disabled:opacity-50"
                                    >
                                        −
                                    </button>
                                    <span class="min-w-[1.5rem] text-center text-sm tabular-nums text-ink">
                                        {{ (int) (float) $quantity }}
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="incrementQuantity({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="incrementQuantity({{ $product->id }})"
                                        aria-label="Increase quantity for {{ $product->name }}"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-line bg-surface text-sm text-ink hover:bg-canvas disabled:opacity-50"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-medium text-ink">
                                    <x-money :amount="$line['line_total']" />
                                </p>
                                <p class="mt-0.5 text-xs text-muted">
                                    <x-money :amount="$product->selling_price" /> each
                                </p>
                                <button
                                    type="button"
                                    wire:click="removeLine({{ $product->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="removeLine({{ $product->id }})"
                                    class="mt-2 text-xs text-danger hover:underline"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
