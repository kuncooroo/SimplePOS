<div>
    <div
        class="mb-3"
        x-data="{
            timer: null,
            scheduleSearch(value) {
                clearTimeout(this.timer)
                this.timer = setTimeout(() => {
                    $wire.set('search', value)
                }, 400)
            },
            submitScan(value) {
                clearTimeout(this.timer)
                this.timer = null
                $wire.confirmScan(value).then(() => {
                    this.$refs.input?.focus()
                })
            },
        }"
    >
        <label for="pos-search" class="text-sm font-medium text-ink">Search products</label>
        <input
            id="pos-search"
            x-ref="input"
            type="search"
            value="{{ $search }}"
            x-on:input="scheduleSearch($event.target.value)"
            x-on:keydown.enter.prevent="submitScan($event.target.value)"
            autofocus
            autocomplete="off"
            placeholder="Name, SKU, or barcode — Enter to add on exact barcode"
            maxlength="100"
            class="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-muted"
        >
        @error('search')
            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
        @enderror
    </div>

    @if (! $hasQuery)
        <x-empty-state title="Search to sell">
            Type a product name, SKU, or scan a barcode. The full catalog is not shown until you search.
        </x-empty-state>
    @elseif ($products->isEmpty())
        <x-empty-state title="No matching products">
            No active products match “{{ $search }}”.
        </x-empty-state>
    @else
        <div class="overflow-hidden rounded-md border border-line bg-surface">
            <ul class="divide-y divide-line">
                @foreach ($products as $product)
                    <li wire:key="pos-product-{{ $product->id }}">
                        <button
                            type="button"
                            wire:click="selectProduct({{ $product->id }})"
                            wire:loading.attr="disabled"
                            wire:target="selectProduct({{ $product->id }})"
                            class="flex w-full items-start justify-between gap-3 px-3 py-2 text-left transition disabled:opacity-50 {{ $selectedProductId === $product->id ? 'bg-canvas' : 'hover:bg-canvas' }}"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-ink">{{ $product->name }}</p>
                                <p class="mt-0.5 text-xs text-muted">
                                    SKU {{ $product->sku }}
                                    @if ($product->barcode)
                                        · {{ $product->barcode }}
                                    @endif
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-medium text-ink">
                                    <x-money :amount="$product->selling_price" />
                                </p>
                                <p class="mt-0.5 text-xs text-muted">Stock {{ $product->stock_quantity }}</p>
                            </div>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
        <p class="mt-2 text-xs text-muted">Showing up to {{ \App\Livewire\Pos\ProductSearch::RESULT_LIMIT }} active products.</p>
    @endif
</div>
