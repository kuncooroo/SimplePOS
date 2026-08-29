<?php

declare(strict_types=1);

namespace App\Livewire\Identity;

use App\Actions\Identity\UpdateProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class ProfileForm extends Component
{
    public string $name = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = Auth::user();

        abort_if($user === null, 403);

        $this->name = $user->name;
    }

    public function save(UpdateProfile $updateProfile): void
    {
        $user = Auth::user();

        abort_if($user === null, 403);

        $data = $this->validate($this->rules());

        $updateProfile->execute(
            user: $user,
            name: $data['name'],
            password: $data['password'] ?? null,
        );

        $this->name = $user->fresh()->name;
        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->resetErrorBag();

        session()->flash('success', 'Profile updated.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'password_confirmation' => ['nullable', 'required_with:password'],
        ];
    }

    public function render(): View
    {
        $user = Auth::user();

        return view('livewire.identity.profile-form', [
            'email' => $user?->email,
            'roleLabel' => $user?->resolvedRole()?->label() ?? 'Unknown',
        ])->extends('layouts.app', [
            'heading' => 'Profile',
            'title' => 'Profile — '.config('app.name'),
        ])->section('content');
    }
}
