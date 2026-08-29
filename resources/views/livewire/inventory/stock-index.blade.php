<div>
    <x-page-header title="Inventory">
        <x-slot:description>
            Current stock levels across the catalog. Low stock is
            <span class="font-medium text-ink">{{ $threshold }}</span> units or below.
        </x-slot:description>
    </x-page-header>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end">
        <div class="max-w-sm flex-1">
            <x-input
                label="Search"
                name="search"
                placeholder="Product name or SKU"
                maxlength="100"
                wire:model.live.debounce.400ms="search"
            />
            @error('search')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-ink">
            <input
                type="checkbox"
                wire:model.live="lowStockOnly"
                class="rounded border-line text-brand focus:ring-brand"
            >
            Low stock only
        </label>

        @if ($hasFilters)
            <button type="button" wire:click="clearFilters" class="text-sm font-medium text-brand hover:text-brand-hover">
                Clear filters
            </button>
        @endif
    </div>

    @if ($products->isEmpty() && ! $hasFilters)
        <x-empty-state title="No products to show">
            Add products in the catalog before tracking inventory here.
        </x-empty-state>
    @elseif ($products->isEmpty())
        <x-empty-state title="No matching products">
            No products match the current search or low-stock filter.
        </x-empty-state>
    @else
        <x-table>
            <thead class="bg-canvas text-xs font-medium uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-3 py-2">Product</th>
                    <th class="px-3 py-2">SKU</th>
                    <th class="px-3 py-2">Category</th>
                    <th class="px-3 py-2">Current stock</th>
                    <th class="px-3 py-2">Stock status</th>
                    <th class="px-3 py-2">Last updated</th>
                    @can('adjustStock')
                        <th class="px-3 py-2"><span class="sr-only">Actions</span></th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($products as $product)
                    @php
                        $isLowStock = bccomp((string) $product->stock_quantity, $threshold, 3) <= 0;
                    @endphp
                    <tr wire:key="stock-product-{{ $product->id }}">
                        <td class="px-3 py-2 font-medium text-ink">{{ $product->name }}</td>
                        <td class="px-3 py-2 text-muted">{{ $product->sku }}</td>
                        <td class="px-3 py-2">{{ $product->category?->name }}</td>
                        <td class="px-3 py-2 tabular-nums">{{ $product->stock_quantity }}</td>
                        <td class="px-3 py-2">
                            @if ($isLowStock)
                                <x-badge tone="warning">Low stock</x-badge>
                            @else
                                <x-badge tone="success">In stock</x-badge>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-muted">{{ $product->updated_at?->format('d M Y H:i') }}</td>
                        @can('adjustStock')
                            <td class="px-3 py-2 text-right">
                                <button
                                    type="button"
                                    wire:click="openAdjustStock({{ $product->id }})"
                                    class="text-sm font-medium text-brand hover:text-brand-hover"
                                >
                                    Adjust
                                </button>
                            </td>
                        @endcan
                    </tr>
                @endforeach
            </tbody>
        </x-table>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    @endif

    @can('adjustStock')
        <livewire:inventory.adjust-stock-form />
    @endcan
</div>
