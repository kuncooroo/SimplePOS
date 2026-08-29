<?php

declare(strict_types=1);

namespace App\Livewire\Identity;

use App\Actions\Identity\CreateUser;
use App\Actions\Identity\UpdateUser;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class UserForm extends Component
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = '';

    public bool $active = true;

    public function mount(?User $user = null): void
    {
        if ($user !== null && $user->exists) {
            $this->authorize('update', $user);

            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->resolvedRole()?->value ?? UserRole::Cashier->value;
            $this->active = $user->active;
        } else {
            $this->authorize('create', User::class);
            $this->role = UserRole::Cashier->value;
            $this->active = true;
        }
    }

    public function save(CreateUser $createUser, UpdateUser $updateUser): void
    {
        $data = $this->validate($this->rules());

        if ($this->userId !== null) {
            $target = User::query()->findOrFail($this->userId);
            $this->authorize('update', $target);

            if ($this->isEditingSelf()) {
                $data['role'] = $target->resolvedRole()?->value ?? $this->role;
                $data['active'] = $target->active;
            }

            if (! isset($data['password']) || $data['password'] === '') {
                unset($data['password']);
            }

            unset($data['password_confirmation']);

            $updateUser->execute(auth()->user(), $target, $data);
            session()->flash('success', 'User updated.');
        } else {
            $this->authorize('create', User::class);
            unset($data['password_confirmation']);
            $createUser->execute(auth()->user(), $data);
            session()->flash('success', 'User created.');
        }

        $this->redirect(route('users.index'));
    }

    /**
     * @return list<UserRole>
     */
    public function assignableRoles(): array
    {
        if (auth()->user()?->can('assignOwnerRole')) {
            return UserRole::cases();
        }

        return [UserRole::Administrator, UserRole::Cashier];
    }

    public function isEditingSelf(): bool
    {
        return $this->userId !== null && $this->userId === auth()->id();
    }

    public function isEditing(): bool
    {
        return $this->userId !== null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $roleValues = array_map(
            static fn (UserRole $role): string => $role->value,
            $this->assignableRoles(),
        );

        $passwordRules = $this->isEditing()
            ? ['nullable', 'confirmed', Password::defaults()]
            : ['required', 'confirmed', Password::defaults()];

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'password' => $passwordRules,
            'password_confirmation' => ['nullable', 'required_with:password'],
            'role' => ['required', Rule::in($roleValues)],
            'active' => ['boolean'],
        ];
    }

    public function render(): View
    {
        $heading = $this->isEditing() ? 'Edit user' : 'New user';

        return view('livewire.identity.user-form')->extends('layouts.app', [
            'heading' => $heading,
            'title' => $heading.' — '.config('app.name'),
        ])->section('content');
    }
}
