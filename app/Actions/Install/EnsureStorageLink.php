<?php

declare(strict_types=1);

namespace App\Actions\Install;

use Illuminate\Support\Facades\Artisan;

final class EnsureStorageLink
{
    public function execute(): void
    {
        if (is_link(public_path('storage')) || is_dir(public_path('storage'))) {
            return;
        }

        Artisan::call('storage:link');
    }
}
