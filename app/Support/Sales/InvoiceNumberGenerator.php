<?php

declare(strict_types=1);

namespace App\Support\Sales;

use App\Models\Transaction;

final class InvoiceNumberGenerator
{
    public function generate(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'INV-'.$date.'-';

        $latest = Transaction::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = 1;

        if (is_string($latest) && $latest !== '') {
            $sequence = (int) substr($latest, -6) + 1;
        }

        return sprintf('%s%06d', $prefix, $sequence);
    }
}
