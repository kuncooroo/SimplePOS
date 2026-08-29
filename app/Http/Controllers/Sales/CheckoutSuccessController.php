<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sales;

use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CheckoutSuccessController
{
    public function __invoke(Transaction $transaction): RedirectResponse
    {
        Gate::authorize('view', $transaction);

        return redirect()->route('transactions.receipt', $transaction);
    }
}
