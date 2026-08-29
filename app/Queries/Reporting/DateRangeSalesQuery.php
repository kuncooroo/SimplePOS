<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;

class DateRangeSalesQuery
{
    /**
     * Completed sales in the inclusive date range using the application timezone.
     */
    public function summary(string $dateFrom, string $dateTo): SalesPeriodSummary
    {
        $period = new ReportingPeriod($dateFrom, $dateTo);
        $query = $this->completedInPeriod($period);

        $salesTotal = (string) ((clone $query)->sum('total') ?: '0');

        return new SalesPeriodSummary(
            salesTotal: bcadd($salesTotal, '0', 2),
            transactionCount: (clone $query)->count(),
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );
    }

    /**
     * @return Builder<Transaction>
     */
    public function completedTransactionsQuery(string $dateFrom, string $dateTo): Builder
    {
        return $this->completedInPeriod(new ReportingPeriod($dateFrom, $dateTo))
            ->with('cashier')
            ->orderByDesc('completed_at')
            ->orderByDesc('id');
    }

    /**
     * @return Builder<Transaction>
     */
    private function completedInPeriod(ReportingPeriod $period): Builder
    {
        return Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('completed_at', [$period->startsAt, $period->endsAt]);
    }
}
