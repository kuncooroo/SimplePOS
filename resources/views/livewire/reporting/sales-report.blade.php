<div>
    <x-page-header title="Sales report">
        <x-slot:description>
            Completed sales for
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

    @if ($summary === null)
        <x-empty-state title="Invalid date range">
            Choose a valid from and to date to load the sales report.
        </x-empty-state>
    @else
        <div wire:loading.class="opacity-50" wire:target="dateFrom,dateTo" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-md border border-line bg-surface p-4">
                    <p class="text-sm text-muted">Total sales</p>
                    <p class="mt-2 text-2xl font-semibold text-ink">
                        <x-money :amount="$summary->salesTotal" />
                    </p>
                </div>

                <div class="rounded-md border border-line bg-surface p-4">
                    <p class="text-sm text-muted">Completed transactions</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-ink">{{ $summary->transactionCount }}</p>
                </div>
            </div>

            @if ($transactions === null || $transactions->isEmpty())
                <x-empty-state title="No sales in this period">
                    Completed sales will appear here when transactions exist for the selected dates.
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
                            <tr wire:key="sales-report-transaction-{{ $transaction->id }}">
                                <td class="px-3 py-2 font-medium text-ink">{{ $transaction->invoice_number }}</td>
                                <td class="px-3 py-2 text-muted">{{ $transaction->completed_at?->format('d M Y H:i') }}</td>
                                <td class="px-3 py-2">{{ $transaction->cashier?->name }}</td>
                                <td class="px-3 py-2">
                                    <x-money :amount="$transaction->total" />
                                </td>
                                <td class="px-3 py-2 text-right">
                                    @can('view', $transaction)
                                        <a href="{{ route('transactions.show', $transaction) }}" class="text-sm font-medium text-brand hover:text-brand-hover">
                                            View
                                        </a>
                                    @endcan
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
    @endif
</div>
