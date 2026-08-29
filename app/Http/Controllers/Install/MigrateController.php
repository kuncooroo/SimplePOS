<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Actions\Install\EnsureStorageLink;
use App\Actions\Install\RunMigrations;
use App\Support\Install\InstallState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MigrateController extends InstallStepController
{
    public function __construct(
        private RunMigrations $runMigrations,
        private EnsureStorageLink $ensureStorageLink,
    ) {}

    public function show(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfPreflightIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfEnvironmentIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_MIGRATE)) {
            return $redirect;
        }

        return view('install.migrate', [
            'migrationsCompleted' => InstallState::migrationsCompleted(),
        ]);
    }

    public function store(): RedirectResponse
    {
        if ($redirect = $this->redirectIfPreflightIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfEnvironmentIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_MIGRATE)) {
            return $redirect;
        }

        $this->runMigrations->execute();
        $this->ensureStorageLink->execute();

        InstallState::markMigrationsCompleted();
        InstallState::advanceToOwner();

        return redirect()
            ->route('install.owner')
            ->with('status', 'migrate-complete');
    }
}
