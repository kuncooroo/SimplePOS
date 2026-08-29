<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

final readonly class DashboardMetrics
{
    public function __construct(
        public string $salesToday,
        public int $transactionsToday,
        public int $lowStockCount,
        public ?string $bestSellerName,
        public ?string $bestSellerQuantity,
    ) {}
}
