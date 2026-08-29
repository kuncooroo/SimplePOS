<?php

declare(strict_types=1);

namespace App\Services\Sales;

final class CheckoutCalculator
{
    /**
     * @param  array<int, string>  $lineTotals
     */
    public function calculateFromLines(array $lineTotals, string $discount, string $cashReceived): CheckoutCalculation
    {
        $subtotal = array_reduce(
            $lineTotals,
            fn (string $carry, string $lineTotal): string => bcadd($carry, $this->normalizeMoney($lineTotal), 2),
            '0.00',
        );

        return $this->calculate($subtotal, $discount, $cashReceived);
    }

    public function calculate(string $subtotal, string $discount, string $cashReceived): CheckoutCalculation
    {
        $subtotal = $this->normalizeMoney($subtotal);
        $discount = $this->normalizeMoney($discount);
        $cashReceived = $this->normalizeMoney($cashReceived);

        $effectiveDiscount = bccomp($discount, $subtotal, 2) > 0 ? $subtotal : $discount;
        $total = bcsub($subtotal, $effectiveDiscount, 2);

        if (bccomp($total, '0', 2) < 0) {
            $total = '0.00';
        }

        $paymentSufficient = bccomp($cashReceived, $total, 2) >= 0;
        $change = $paymentSufficient ? bcsub($cashReceived, $total, 2) : '0.00';

        return new CheckoutCalculation(
            subtotal: $subtotal,
            discount: $effectiveDiscount,
            total: $total,
            cashReceived: $cashReceived,
            change: $change,
            paymentSufficient: $paymentSufficient,
        );
    }

    private function normalizeMoney(string $amount): string
    {
        $trimmed = trim($amount);

        if ($trimmed === '' || ! is_numeric($trimmed)) {
            return '0.00';
        }

        return bcadd($trimmed, '0', 2);
    }
}
