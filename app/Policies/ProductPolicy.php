<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('manageProducts');
    }

    public function view(User $actor, Product $product): bool
    {
        return $actor->can('manageProducts');
    }

    public function create(User $actor): bool
    {
        return $actor->can('manageProducts');
    }

    public function update(User $actor, Product $product): bool
    {
        return $actor->can('manageProducts');
    }

    public function changeStatus(User $actor, Product $product): bool
    {
        return $this->update($actor, $product);
    }

    public function delete(User $actor, Product $product): bool
    {
        return false;
    }

    public function forceDelete(User $actor, Product $product): bool
    {
        return false;
    }
}
