<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $this->canManageUsers($actor);
    }

    public function view(User $actor, User $target): bool
    {
        return $this->canManageUsers($actor);
    }

    public function create(User $actor): bool
    {
        return $this->canManageUsers($actor);
    }

    public function update(User $actor, User $target): bool
    {
        if (! $this->canManageUsers($actor)) {
            return false;
        }

        if ($actor->isAdministrator() && $target->isOwner()) {
            return false;
        }

        return true;
    }

    public function changeStatus(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        return $this->update($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return false;
    }

    public function forceDelete(User $actor, User $target): bool
    {
        return false;
    }

    private function canManageUsers(User $actor): bool
    {
        return $actor->isOwner() || $actor->isAdministrator();
    }
}
