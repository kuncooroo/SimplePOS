<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <x-page-header title="Products">
            <x-slot:description>Catalog items for POS. Products are deactivated, never deleted.</x-slot:description>
        </x-page-header>
        <a
            href="{{ route('products.create') }}"
            class="inline-flex items-center justify-center rounded-md bg-brand px-3 py-2 text-sm font-medium text-white hover:bg-brand-hover"
        >
            New product
        </a>
    </div>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end">
        <div class="max-w-sm flex-1">
            <x-input
                label="Search"
                name="search"
                placeholder="Name, SKU, or barcode"
                wire:model.live.debounce.400ms="search"
            />
        </div>
        <div class="flex flex-col gap-1">
            <label for="categoryId" class="text-sm font-medium text-ink">Category</label>
            <select
                id="categoryId"
                wire:model.live="categoryId"
                class="rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"
            >
                <option value="">All</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label for="status" class="text-sm font-medium text-ink">Status</label>
            <select
                id="status"
                wire:model.live="status"
                class="rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"
            >
                <option value="all">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    @if ($products->isEmpty() && ! $hasSearch && ! $hasFilters)
        <x-empty-state title="No products yet">
            Create a product after you have at least one active category.
        </x-empty-state>
    @elseif ($products->isEmpty())
        <x-empty-state title="No matching products">
            No products match the current search or filters.
        </x-empty-state>
    @else
        <x-table>
            <thead class="bg-canvas text-xs font-medium uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">SKU</th>
                    <th class="px-3 py-2">Category</th>
                    <th class="px-3 py-2">Price</th>
                    <th class="px-3 py-2">Stock</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($products as $product)
                    <tr wire:key="product-{{ $product->id }}">
                        <td class="px-3 py-2 font-medium text-ink">{{ $product->name }}</td>
                        <td class="px-3 py-2 text-muted">{{ $product->sku }}</td>
                        <td class="px-3 py-2">{{ $product->category?->name }}</td>
                        <td class="px-3 py-2">
                            <x-money :amount="$product->selling_price" />
                        </td>
                        <td class="px-3 py-2 tabular-nums">{{ $product->stock_quantity }}</td>
                        <td class="px-3 py-2">
                            <x-badge :tone="$product->active ? 'success' : 'danger'">
                                {{ $product->active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex justify-end gap-2">
                                @can('update', $product)
                                    <a href="{{ route('products.edit', $product) }}" class="text-sm font-medium text-brand hover:text-brand-hover">
                                        Edit
                                    </a>
                                @endcan
                                @can('changeStatus', $product)
                                    @if ($product->active)
                                        <button
                                            type="button"
                                            class="text-sm font-medium text-danger hover:underline"
                                            wire:click="confirmDeactivate({{ $product->id }})"
                                        >
                                            Deactivate
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="text-sm font-medium text-brand hover:text-brand-hover"
                                            wire:click="activate({{ $product->id }})"
                                        >
                                            Activate
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    @endif

    <x-modal :open="$pendingDeactivateId !== null" title="Deactivate product">
        <p class="text-sm text-muted">
            Deactivate
            <span class="font-medium text-ink">{{ $pendingDeactivateName }}</span>?
            It will stay on file for history and will not be sellable on POS.
        </p>
        <div class="mt-4 flex justify-end gap-2">
            <x-button variant="secondary" wire:click="cancelDeactivate">Cancel</x-button>
            <x-button variant="danger" wire:click="deactivate" wire:loading.attr="disabled">
                Deactivate
            </x-button>
        </div>
    </x-modal>
</div>
