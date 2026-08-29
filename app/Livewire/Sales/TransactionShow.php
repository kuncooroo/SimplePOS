<?php

declare(strict_types=1);

namespace App\Livewire\Sales;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TransactionShow extends Component
{
    public Transaction $transaction;

    public function mount(Transaction $transaction): void
    {
        $this->authorize('view', $transaction);

        $this->transaction = $transaction->load(['items', 'cashier']);
    }

    public function render(): View
    {
        return view('livewire.sales.transaction-show', [
            'transaction' => $this->transaction,
        ])->extends('layouts.app', [
            'heading' => 'Transaction '.$this->transaction->invoice_number,
            'title' => $this->transaction->invoice_number.' — '.config('app.name'),
        ])->section('content');
    }
}
