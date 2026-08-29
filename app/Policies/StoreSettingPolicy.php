<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StoreSetting;
use App\Models\User;

class StoreSettingPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('manageStoreSettings');
    }

    public function view(User $actor, StoreSetting $settings): bool
    {
        return $actor->can('manageStoreSettings');
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, StoreSetting $settings): bool
    {
        return $actor->can('manageStoreSettings');
    }

    public function delete(User $actor, StoreSetting $settings): bool
    {
        return false;
    }

    public function forceDelete(User $actor, StoreSetting $settings): bool
    {
        return false;
    }
}
