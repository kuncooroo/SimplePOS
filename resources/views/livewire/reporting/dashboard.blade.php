<div>
    <x-page-header title="Dashboard">
        <x-slot:description>
            Store overview for today using the application timezone ({{ config('app.timezone') }}).
        </x-slot:description>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-md border border-line bg-surface p-4">
            <p class="text-sm text-muted">Sales today</p>
            <p class="mt-2 text-2xl font-semibold text-ink">
                <x-money :amount="$metrics->salesToday" />
            </p>
        </div>

        <div class="rounded-md border border-line bg-surface p-4">
            <p class="text-sm text-muted">Transactions today</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums text-ink">{{ $metrics->transactionsToday }}</p>
        </div>

        @can('viewInventory')
            <a
                href="{{ route('inventory.index') }}"
                class="rounded-md border border-line bg-surface p-4 transition hover:border-brand hover:bg-canvas"
            >
                <p class="text-sm text-muted">Low stock</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums text-ink">{{ $metrics->lowStockCount }}</p>
                <p class="mt-1 text-xs text-muted">Products at or below threshold</p>
            </a>
        @else
            <div class="rounded-md border border-line bg-surface p-4">
                <p class="text-sm text-muted">Low stock</p>
                <p class="mt-2 text-2xl font-semibold tabular-nums text-ink">{{ $metrics->lowStockCount }}</p>
            </div>
        @endcan

        <div class="rounded-md border border-line bg-surface p-4">
            <p class="text-sm text-muted">Best seller today</p>
            @if ($metrics->bestSellerName !== null)
                <p class="mt-2 text-lg font-semibold text-ink">{{ $metrics->bestSellerName }}</p>
                <p class="mt-1 text-sm tabular-nums text-muted">{{ $metrics->bestSellerQuantity }} sold</p>
            @else
                <p class="mt-2 text-lg font-medium text-muted">No sales today</p>
                <p class="mt-1 text-sm tabular-nums text-muted">0 sold</p>
            @endif
        </div>
    </div>
</div>
