<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Actions\Audit\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ChangeUserRole
{
    public function __construct(
        private RecordActivity $recordActivity,
    ) {}

    public function execute(User $actor, User $target, UserRole $role): User
    {
        Gate::forUser($actor)->authorize('update', $target);

        $currentRole = $target->resolvedRole();

        if ($role === UserRole::Owner && $currentRole !== UserRole::Owner) {
            Gate::forUser($actor)->authorize('assignOwnerRole');
        }

        if ($actor->is($target) && $role !== $currentRole) {
            throw ValidationException::withMessages([
                'role' => 'You cannot change your own role or status here.',
            ]);
        }

        if ($role === $currentRole) {
            return $target;
        }

        return DB::transaction(function () use ($actor, $target, $role, $currentRole): User {
            $target->update([
                'role' => $role,
            ]);

            $this->recordActivity->execute(
                actor: $actor,
                action: ActivityAction::UserRoleChanged,
                subject: $target,
                oldValues: [
                    'role' => $currentRole?->value,
                ],
                newValues: [
                    'role' => $role->value,
                ],
            );

            return $target->refresh();
        });
    }
}
