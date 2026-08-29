<?php

declare(strict_types=1);

namespace App\Services\Sales;

final readonly class CheckoutCalculation
{
    public function __construct(
        public string $subtotal,
        public string $discount,
        public string $total,
        public string $cashReceived,
        public string $change,
        public bool $paymentSufficient,
    ) {}
}
