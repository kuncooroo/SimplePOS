<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateUser
{
    public function __construct(
        private ChangeUserRole $changeUserRole,
        private ChangeUserStatus $changeUserStatus,
    ) {}

    /**
     * @param  array{name: string, email: string, role: string, active: bool, password?: string|null}  $data
     */
    public function execute(User $actor, User $target, array $data): User
    {
        Gate::forUser($actor)->authorize('update', $target);

        $role = UserRole::from($data['role']);
        $active = (bool) $data['active'];
        $currentRole = $target->resolvedRole();
        $currentActive = $target->active;

        if ($actor->is($target) && ($role !== $currentRole || $active !== $currentActive)) {
            throw ValidationException::withMessages([
                'role' => 'You cannot change your own role or status here.',
            ]);
        }

        return DB::transaction(function () use ($actor, $target, $data, $role, $active, $currentRole, $currentActive): User {
            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            if (isset($data['password']) && is_string($data['password']) && $data['password'] !== '') {
                $payload['password'] = $data['password'];
            }

            $target->update($payload);

            if ($role !== $currentRole) {
                $this->changeUserRole->execute($actor, $target, $role);
            }

            if ($active !== $currentActive) {
                $this->changeUserStatus->execute($actor, $target, $active);
            }

            return $target->refresh();
        });
    }
}
