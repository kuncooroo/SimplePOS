<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('viewOwnTransactions');
    }

    public function view(User $actor, Transaction $transaction): bool
    {
        if ($actor->can('viewAllTransactions')) {
            return true;
        }

        return $actor->can('viewOwnTransactions')
            && $transaction->cashier_id === $actor->id;
    }

    public function create(User $actor): bool
    {
        return $actor->can('accessPos');
    }

    public function update(User $actor, Transaction $transaction): bool
    {
        return false;
    }

    public function delete(User $actor, Transaction $transaction): bool
    {
        return false;
    }

    public function forceDelete(User $actor, Transaction $transaction): bool
    {
        return false;
    }
}
