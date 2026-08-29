<div>
    <x-page-header title="Product sales report">
        <x-slot:description>
            Quantities and amounts from sale-time line snapshots for
            <span class="font-medium text-ink">{{ $periodLabel }}</span>
            ({{ config('app.timezone') }}).
        </x-slot:description>
    </x-page-header>

    <div class="mb-4 grid gap-3 lg:grid-cols-4">
        <div>
            <x-input
                label="From"
                type="date"
                name="dateFrom"
                wire:model.live="dateFrom"
            />
            @error('dateFrom')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-input
                label="To"
                type="date"
                name="dateTo"
                wire:model.live="dateTo"
            />
            @error('dateTo')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        @if ($hasCustomRange)
            <div class="flex items-end">
                <button type="button" wire:click="clearFilters" class="text-sm font-medium text-brand hover:text-brand-hover">
                    Reset to today
                </button>
            </div>
        @endif
    </div>

    @if ($periodTotals === null)
        <x-empty-state title="Invalid date range">
            Choose a valid from and to date to load the product sales report.
        </x-empty-state>
    @else
        <div wire:loading.class="opacity-50" wire:target="dateFrom,dateTo" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-md border border-line bg-surface p-4">
                    <p class="text-sm text-muted">Total quantity sold</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-ink">{{ $periodTotals['quantitySold'] }}</p>
                </div>

                <div class="rounded-md border border-line bg-surface p-4">
                    <p class="text-sm text-muted">Total line sales</p>
                    <p class="mt-2 text-2xl font-semibold text-ink">
                        <x-money :amount="$periodTotals['salesAmount']" />
                    </p>
                </div>
            </div>

            @if ($rows === null || $rows->isEmpty())
                <x-empty-state title="No product sales in this period">
                    Completed sales line items will appear here when transactions exist for the selected dates.
                </x-empty-state>
            @else
                <x-table>
                    <thead class="bg-canvas text-xs font-medium uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2">SKU</th>
                            <th class="px-3 py-2">Qty sold</th>
                            <th class="px-3 py-2">Sales amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($rows as $row)
                            <tr wire:key="product-sales-row-{{ $row->product_id ?? $row->sku_snapshot }}-{{ $row->product_name_snapshot }}">
                                <td class="px-3 py-2">
                                    <p class="font-medium text-ink">{{ $row->product_name_snapshot }}</p>
                                    @if ($row->current_product_name && $row->current_product_name !== $row->product_name_snapshot)
                                        <p class="text-xs text-muted">Current name: {{ $row->current_product_name }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-muted">{{ $row->sku_snapshot ?: '—' }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $row->quantity_sold }}</td>
                                <td class="px-3 py-2">
                                    <x-money :amount="$row->sales_amount" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>

                <div class="mt-4">
                    {{ $rows->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
