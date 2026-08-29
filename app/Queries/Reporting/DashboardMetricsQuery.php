<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

use App\Enums\TransactionStatus;
use App\Models\Product;
use App\Models\TransactionItem;
use App\Support\StoreSettings;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DashboardMetricsQuery
{
    public function __construct(
        private DateRangeSalesQuery $dateRangeSalesQuery,
    ) {}

    /**
     * Metrics use the application timezone (`config('app.timezone')`) for the business day.
     * Best seller uses completed sales from the same day only.
     * Ties break on highest quantity sold, then product name ascending.
     */
    public function execute(?CarbonInterface $asOf = null): DashboardMetrics
    {
        $timezone = (string) config('app.timezone');
        $moment = Carbon::parse($asOf ?? now())->timezone($timezone);
        $today = $moment->toDateString();

        $salesSummary = $this->dateRangeSalesQuery->summary($today, $today);

        $dayStart = $moment->copy()->startOfDay();
        $dayEnd = $moment->copy()->endOfDay();

        $threshold = (string) StoreSettings::current()->low_stock_threshold;
        $lowStockCount = Product::query()
            ->where('stock_quantity', '<=', $threshold)
            ->count();

        $bestSeller = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', TransactionStatus::Completed)
            ->whereBetween('transactions.completed_at', [$dayStart, $dayEnd])
            ->selectRaw('transaction_items.product_id')
            ->selectRaw('MAX(transaction_items.product_name_snapshot) as product_name')
            ->selectRaw('SUM(transaction_items.quantity) as quantity_sold')
            ->groupBy('transaction_items.product_id')
            ->orderByDesc('quantity_sold')
            ->orderBy('product_name')
            ->first();

        return new DashboardMetrics(
            salesToday: $salesSummary->salesTotal,
            transactionsToday: $salesSummary->transactionCount,
            lowStockCount: $lowStockCount,
            bestSellerName: $bestSeller?->product_name,
            bestSellerQuantity: $bestSeller === null
                ? null
                : bcadd((string) $bestSeller->quantity_sold, '0', 3),
        );
    }
}
