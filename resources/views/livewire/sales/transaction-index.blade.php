<div>
    <x-page-header title="Transactions">
        <x-slot:description>
            Completed sales only. Historical line amounts use sale-time snapshots.
        </x-slot:description>
    </x-page-header>

    <div class="mb-4 grid gap-3 lg:grid-cols-4">
        <div class="lg:col-span-2">
            <x-input
                label="Invoice number"
                name="invoice"
                placeholder="Search invoice"
                maxlength="64"
                wire:model.live.debounce.400ms="invoice"
            />
            @error('invoice')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
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

        @if ($canFilterByCashier)
            <div class="lg:col-span-2">
                <label for="cashierId" class="text-sm font-medium text-ink">Cashier</label>
                <select
                    id="cashierId"
                    wire:model.live="cashierId"
                    class="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink"
                >
                    <option value="">All cashiers</option>
                    @foreach ($cashiers as $cashier)
                        <option value="{{ $cashier->id }}">{{ $cashier->name }}</option>
                    @endforeach
                </select>
                @error('cashierId')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>
        @endif

        @if ($hasFilters)
            <div class="flex items-end lg:col-span-2">
                <button type="button" wire:click="clearFilters" class="text-sm font-medium text-brand hover:text-brand-hover">
                    Clear filters
                </button>
            </div>
        @endif
    </div>

    @if ($transactions->isEmpty() && ! $hasFilters)
        <x-empty-state title="No transactions yet">
            Completed sales will appear here after checkout.
        </x-empty-state>
    @elseif ($transactions->isEmpty())
        <x-empty-state title="No matching transactions">
            No completed sales match the current search or filters.
        </x-empty-state>
    @else
        <x-table>
            <thead class="bg-canvas text-xs font-medium uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-3 py-2">Invoice</th>
                    <th class="px-3 py-2">Completed</th>
                    <th class="px-3 py-2">Cashier</th>
                    <th class="px-3 py-2">Total</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($transactions as $transaction)
                    <tr wire:key="transaction-{{ $transaction->id }}">
                        <td class="px-3 py-2 font-medium text-ink">{{ $transaction->invoice_number }}</td>
                        <td class="px-3 py-2 text-muted">{{ $transaction->completed_at?->format('d M Y H:i') }}</td>
                        <td class="px-3 py-2">{{ $transaction->cashier?->name }}</td>
                        <td class="px-3 py-2">
                            <x-money :amount="$transaction->total" />
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex justify-end gap-2">
                                @can('view', $transaction)
                                    <a href="{{ route('transactions.show', $transaction) }}" class="text-sm font-medium text-brand hover:text-brand-hover">
                                        View
                                    </a>
                                    <a href="{{ route('transactions.receipt', $transaction) }}" class="text-sm font-medium text-brand hover:text-brand-hover">
                                        Receipt
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>

        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
