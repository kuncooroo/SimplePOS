<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UpdateProfile
{
    public function execute(User $user, string $name, ?string $password = null): User
    {
        $payload = [
            'name' => trim($name),
        ];

        $passwordChanged = is_string($password) && $password !== '';

        if ($passwordChanged) {
            $payload['password'] = $password;
        }

        $user->update($payload);
        $fresh = $user->refresh();

        if ($passwordChanged) {
            Auth::login($fresh);
            session()->regenerate();
        }

        return $fresh;
    }
}
