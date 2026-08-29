<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Actions\Audit\RecordActivity;
use App\Enums\ActivityAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class ChangeUserStatus
{
    public function __construct(
        private RecordActivity $recordActivity,
    ) {}

    public function execute(User $actor, User $target, bool $active): User
    {
        Gate::forUser($actor)->authorize('changeStatus', $target);

        if ($actor->is($target)) {
            throw new RuntimeException('You cannot change your own status.');
        }

        if ($target->active === $active) {
            return $target;
        }

        return DB::transaction(function () use ($actor, $target, $active): User {
            $oldActive = $target->active;

            $target->update([
                'active' => $active,
            ]);

            $this->recordActivity->execute(
                actor: $actor,
                action: ActivityAction::UserStatusChanged,
                subject: $target,
                oldValues: [
                    'active' => $oldActive,
                ],
                newValues: [
                    'active' => $active,
                ],
            );

            return $target->refresh();
        });
    }
}
