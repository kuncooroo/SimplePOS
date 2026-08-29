<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('manageCategories');
    }

    public function view(User $actor, Category $category): bool
    {
        return $actor->can('manageCategories');
    }

    public function create(User $actor): bool
    {
        return $actor->can('manageCategories');
    }

    public function update(User $actor, Category $category): bool
    {
        return $actor->can('manageCategories');
    }

    public function changeStatus(User $actor, Category $category): bool
    {
        return $this->update($actor, $category);
    }

    public function delete(User $actor, Category $category): bool
    {
        return false;
    }

    public function forceDelete(User $actor, Category $category): bool
    {
        return false;
    }
}
