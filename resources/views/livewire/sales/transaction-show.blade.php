<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <x-page-header title="{{ $transaction->invoice_number }}">
            <x-slot:description>
                Completed {{ $transaction->completed_at?->format('d M Y H:i') }}
                · Cashier {{ $transaction->cashier?->name }}
            </x-slot:description>
        </x-page-header>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('transactions.index') }}"
                class="inline-flex items-center justify-center rounded-md border border-line bg-surface px-3 py-2 text-sm font-medium text-ink hover:bg-canvas"
            >
                Back to list
            </a>
            <a
                href="{{ route('transactions.receipt', $transaction) }}"
                class="inline-flex items-center justify-center rounded-md bg-brand px-3 py-2 text-sm font-medium text-white hover:bg-brand-hover"
            >
                Open receipt
            </a>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-md border border-line bg-surface p-4 text-sm lg:col-span-1">
            <h2 class="text-sm font-semibold text-ink">Payment summary</h2>
            <dl class="mt-3 space-y-2">
                <div class="flex justify-between">
                    <dt class="text-muted">Subtotal</dt>
                    <dd><x-money :amount="$transaction->subtotal" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-muted">Discount</dt>
                    <dd><x-money :amount="$transaction->discount" /></dd>
                </div>
                <div class="flex justify-between font-medium text-ink">
                    <dt>Total</dt>
                    <dd><x-money :amount="$transaction->total" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-muted">Cash received</dt>
                    <dd><x-money :amount="$transaction->cash_received" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-muted">Change</dt>
                    <dd><x-money :amount="$transaction->change_amount" /></dd>
                </div>
            </dl>
        </div>

        <div class="rounded-md border border-line bg-surface lg:col-span-2">
            <div class="border-b border-line px-4 py-3">
                <h2 class="text-sm font-semibold text-ink">Line items</h2>
                <p class="text-xs text-muted">Snapshot values from checkout time.</p>
            </div>

            @if ($transaction->items->isEmpty())
                <x-empty-state title="No line items">
                    This transaction has no recorded items.
                </x-empty-state>
            @else
                <x-table>
                    <thead class="bg-canvas text-xs font-medium uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2">SKU</th>
                            <th class="px-3 py-2 text-center">Qty</th>
                            <th class="px-3 py-2">Unit price</th>
                            <th class="px-3 py-2 text-right">Line total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($transaction->items as $item)
                            <tr wire:key="transaction-item-{{ $item->id }}">
                                <td class="px-3 py-2 font-medium text-ink">{{ $item->product_name_snapshot }}</td>
                                <td class="px-3 py-2 text-muted">{{ $item->sku_snapshot }}</td>
                                <td class="px-3 py-2 text-center tabular-nums">{{ (int) (float) $item->quantity }}</td>
                                <td class="px-3 py-2">
                                    <x-money :amount="$item->unit_price" />
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <x-money :amount="$item->line_total" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </div>
    </div>
</div>
