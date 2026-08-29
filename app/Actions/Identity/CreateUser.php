<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Actions\Audit\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateUser
{
    public function __construct(
        private RecordActivity $recordActivity,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string, role: string, active?: bool}  $data
     */
    public function execute(User $actor, array $data): User
    {
        Gate::forUser($actor)->authorize('create', User::class);

        $role = UserRole::from($data['role']);

        if ($role === UserRole::Owner) {
            Gate::forUser($actor)->authorize('assignOwnerRole');
        }

        return DB::transaction(function () use ($actor, $data, $role): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $role,
                'active' => $data['active'] ?? true,
            ]);

            $this->recordActivity->execute(
                actor: $actor,
                action: ActivityAction::UserCreated,
                subject: $user,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->resolvedRole()?->value,
                    'active' => $user->active,
                ],
            );

            return $user;
        });
    }
}
