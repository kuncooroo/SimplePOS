<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('viewStockMovements');
    }

    public function view(User $actor, StockMovement $stockMovement): bool
    {
        return $actor->can('viewStockMovements');
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, StockMovement $stockMovement): bool
    {
        return false;
    }

    public function delete(User $actor, StockMovement $stockMovement): bool
    {
        return false;
    }

    public function forceDelete(User $actor, StockMovement $stockMovement): bool
    {
        return false;
    }
}
