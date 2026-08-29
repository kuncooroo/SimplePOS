<div>
    <x-page-header title="Stock movements">
        <x-slot:description>
            Read-only history of sale deductions and manual stock adjustments.
        </x-slot:description>
    </x-page-header>

    <div class="mb-4 grid gap-3 lg:grid-cols-4">
        <div class="lg:col-span-2">
            <x-input
                label="Product search"
                name="search"
                placeholder="Product name or SKU"
                maxlength="100"
                wire:model.live.debounce.400ms="search"
            />
            @error('search')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="movementType" class="text-sm font-medium text-ink">Movement type</label>
            <select
                id="movementType"
                wire:model.live="movementType"
                class="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"
            >
                <option value="">All types</option>
                @foreach ($movementTypes as $type)
                    <option value="{{ $type->value }}">
                        {{ $type === \App\Enums\StockMovementType::Sale ? 'Sale' : 'Manual adjustment' }}
                    </option>
                @endforeach
            </select>
            @error('movementType')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-end">
            @if ($hasFilters)
                <button type="button" wire:click="clearFilters" class="text-sm font-medium text-brand hover:text-brand-hover">
                    Clear filters
                </button>
            @endif
        </div>

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
    </div>

    @if ($movements->isEmpty() && ! $hasFilters)
        <x-empty-state title="No stock movements yet">
            Sale checkouts and manual adjustments will appear here.
        </x-empty-state>
    @elseif ($movements->isEmpty())
        <x-empty-state title="No matching movements">
            No stock movements match the current search or filters.
        </x-empty-state>
    @else
        <x-table>
            <thead class="bg-canvas text-xs font-medium uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-3 py-2">Time</th>
                    <th class="px-3 py-2">Product</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2">Before</th>
                    <th class="px-3 py-2">Change</th>
                    <th class="px-3 py-2">After</th>
                    <th class="px-3 py-2">User</th>
                    <th class="px-3 py-2">Invoice</th>
                    <th class="px-3 py-2">Reason</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($movements as $movement)
                    <tr wire:key="movement-{{ $movement->id }}">
                        <td class="px-3 py-2 text-muted whitespace-nowrap">{{ $movement->occurred_at?->format('d M Y H:i') }}</td>
                        <td class="px-3 py-2">
                            <p class="font-medium text-ink">{{ $movement->product?->name ?? 'Unknown product' }}</p>
                            @if ($movement->product?->sku)
                                <p class="text-xs text-muted">{{ $movement->product->sku }}</p>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if ($movement->movement_type === \App\Enums\StockMovementType::Sale)
                                <x-badge tone="neutral">Sale</x-badge>
                            @else
                                <x-badge tone="warning">Manual adjustment</x-badge>
                            @endif
                        </td>
                        <td class="px-3 py-2 tabular-nums">{{ $movement->quantity_before }}</td>
                        <td class="px-3 py-2 tabular-nums">{{ $movement->quantity_change }}</td>
                        <td class="px-3 py-2 tabular-nums">{{ $movement->quantity_after }}</td>
                        <td class="px-3 py-2">{{ $movement->user?->name }}</td>
                        <td class="px-3 py-2">
                            @if ($movement->transaction)
                                @can('view', $movement->transaction)
                                    <a href="{{ route('transactions.show', $movement->transaction) }}" class="text-sm font-medium text-brand hover:text-brand-hover">
                                        {{ $movement->transaction->invoice_number }}
                                    </a>
                                @else
                                    {{ $movement->transaction->invoice_number }}
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-muted">
                            @if ($movement->reason)
                                {{ $movement->reason }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>

        <div class="mt-4">
            {{ $movements->links() }}
        </div>
    @endif
</div>
