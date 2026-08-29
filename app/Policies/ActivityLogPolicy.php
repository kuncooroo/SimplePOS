<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('viewAuditLog');
    }

    public function view(User $actor, ActivityLog $activityLog): bool
    {
        return $actor->can('viewAuditLog');
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function delete(User $actor, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function forceDelete(User $actor, ActivityLog $activityLog): bool
    {
        return false;
    }
}
