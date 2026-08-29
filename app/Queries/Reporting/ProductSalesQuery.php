<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

use App\Enums\TransactionStatus;
use App\Models\TransactionItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductSalesQuery
{
    public const GROUP_KEY_EXPRESSION = <<<'SQL'
CASE WHEN transaction_items.product_id IS NOT NULL THEN transaction_items.product_id END,
CASE WHEN transaction_items.product_id IS NULL THEN transaction_items.sku_snapshot END,
CASE WHEN transaction_items.product_id IS NULL THEN transaction_items.product_name_snapshot END
SQL;

    /**
     * @return LengthAwarePaginator<int, object{
     *     product_id: int|null,
     *     product_name_snapshot: string,
     *     sku_snapshot: string|null,
     *     current_product_name: string|null,
     *     quantity_sold: string,
     *     sales_amount: string
     * }>
     */
    public function paginate(string $dateFrom, string $dateTo, int $perPage): LengthAwarePaginator
    {
        return $this->baseQuery($dateFrom, $dateTo)
            ->paginate($perPage);
    }

    /**
     * @return array{quantitySold: string, salesAmount: string}
     */
    public function periodTotals(string $dateFrom, string $dateTo): array
    {
        $period = new ReportingPeriod($dateFrom, $dateTo);

        $totals = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', TransactionStatus::Completed)
            ->whereBetween('transactions.completed_at', [$period->startsAt, $period->endsAt])
            ->selectRaw('COALESCE(SUM(transaction_items.quantity), 0) as quantity_sold')
            ->selectRaw('COALESCE(SUM(transaction_items.line_total), 0) as sales_amount')
            ->first();

        return [
            'quantitySold' => bcadd((string) ($totals->quantity_sold ?? '0'), '0', 3),
            'salesAmount' => bcadd((string) ($totals->sales_amount ?? '0'), '0', 2),
        ];
    }

    /**
     * @return Builder<TransactionItem>
     */
    private function baseQuery(string $dateFrom, string $dateTo): Builder
    {
        $period = new ReportingPeriod($dateFrom, $dateTo);

        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->leftJoin('products', 'products.id', '=', 'transaction_items.product_id')
            ->where('transactions.status', TransactionStatus::Completed)
            ->whereBetween('transactions.completed_at', [$period->startsAt, $period->endsAt])
            ->selectRaw('MAX(transaction_items.product_id) as product_id')
            ->selectRaw('MAX(transaction_items.product_name_snapshot) as product_name_snapshot')
            ->selectRaw('MAX(transaction_items.sku_snapshot) as sku_snapshot')
            ->selectRaw('MAX(products.name) as current_product_name')
            ->selectRaw('SUM(transaction_items.quantity) as quantity_sold')
            ->selectRaw('SUM(transaction_items.line_total) as sales_amount')
            ->groupByRaw(self::GROUP_KEY_EXPRESSION)
            ->orderByDesc('quantity_sold')
            ->orderBy('product_name_snapshot');
    }
}
