@extends('layouts.receipt')

@section('content')
    <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm font-medium text-ink">Sale completed</p>
        <div class="flex flex-wrap gap-2">
            <x-button type="button" onclick="window.print()">
                Print receipt
            </x-button>
            <x-button type="button" variant="secondary" onclick="window.location.href='{{ route('pos') }}'">
                New sale
            </x-button>
        </div>
    </div>

    <article class="receipt-shell rounded-md border border-line bg-surface p-4 text-sm shadow-sm print:rounded-none print:shadow-none">
        <header class="border-b border-dashed border-line pb-4 text-center">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $settings->store_name }}" class="mx-auto mb-3 max-h-16 max-w-[12rem] object-contain">
            @endif

            <h1 class="text-base font-semibold text-ink">{{ $settings->store_name }}</h1>

            @if ($settings->address)
                <p class="mt-1 whitespace-pre-line text-xs text-muted">{{ $settings->address }}</p>
            @endif

            @if ($settings->phone || $settings->email)
                <p class="mt-1 text-xs text-muted">
                    @if ($settings->phone)
                        {{ $settings->phone }}
                    @endif
                    @if ($settings->phone && $settings->email)
                        ·
                    @endif
                    @if ($settings->email)
                        {{ $settings->email }}
                    @endif
                </p>
            @endif
        </header>

        <section class="border-b border-dashed border-line py-4">
            <dl class="space-y-1 text-xs">
                <div class="flex justify-between gap-3">
                    <dt class="text-muted">Invoice</dt>
                    <dd class="font-medium text-ink">{{ $transaction->invoice_number }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-muted">Date</dt>
                    <dd class="text-ink">{{ $transaction->completed_at?->format('d M Y H:i') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-muted">Cashier</dt>
                    <dd class="text-ink">{{ $transaction->cashier?->name }}</dd>
                </div>
            </dl>
        </section>

        <section class="border-b border-dashed border-line py-4">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-muted">
                        <th class="pb-2 font-medium">Item</th>
                        <th class="pb-2 text-center font-medium">Qty</th>
                        <th class="pb-2 text-right font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($transaction->items as $item)
                        <tr>
                            <td class="py-2 pr-2 align-top">
                                <p class="break-words font-medium text-ink">{{ $item->product_name_snapshot }}</p>
                                <p class="mt-0.5 text-muted">SKU {{ $item->sku_snapshot }}</p>
                                <p class="text-muted"><x-money :amount="$item->unit_price" /> each</p>
                            </td>
                            <td class="py-2 text-center align-top tabular-nums text-ink">
                                {{ (int) (float) $item->quantity }}
                            </td>
                            <td class="py-2 text-right align-top font-medium text-ink">
                                <x-money :amount="$item->line_total" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="space-y-1 py-4 text-xs">
            <div class="flex justify-between text-muted">
                <span>Subtotal</span>
                <x-money :amount="$transaction->subtotal" />
            </div>
            <div class="flex justify-between text-muted">
                <span>Discount</span>
                <x-money :amount="$transaction->discount" />
            </div>
            <div class="flex justify-between font-semibold text-ink">
                <span>Total</span>
                <x-money :amount="$transaction->total" />
            </div>
            <div class="flex justify-between text-muted">
                <span>Cash received</span>
                <x-money :amount="$transaction->cash_received" />
            </div>
            <div class="flex justify-between text-muted">
                <span>Change</span>
                <x-money :amount="$transaction->change_amount" />
            </div>
        </section>

        @if ($settings->receipt_footer)
            <footer class="border-t border-dashed border-line pt-4 text-center text-xs whitespace-pre-line text-muted">
                {{ $settings->receipt_footer }}
            </footer>
        @endif
    </article>
@endsection
