<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

final readonly class SalesPeriodSummary
{
    public function __construct(
        public string $salesTotal,
        public int $transactionCount,
        public string $dateFrom,
        public string $dateTo,
    ) {}
}
