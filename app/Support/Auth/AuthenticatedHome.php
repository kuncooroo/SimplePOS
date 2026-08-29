<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Route;

final class AuthenticatedHome
{
    public static function routeName(?User $user): string
    {
        if ($user?->isCashier() && Route::has('pos')) {
            return 'pos';
        }

        return 'dashboard';
    }

    public static function url(?User $user): string
    {
        if ($user === null) {
            return route('login');
        }

        return route(self::routeName($user));
    }
}
