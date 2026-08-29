<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Actions\Install\CreateOwner;
use App\Actions\Install\WriteInstallLock;
use App\Support\Install\InstallState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompleteController extends InstallStepController
{
    public function __construct(
        private CreateOwner $createOwner,
        private WriteInstallLock $writeInstallLock,
    ) {}

    public function show(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfPreflightIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfEnvironmentIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_COMPLETE)) {
            return $redirect;
        }

        if (! InstallState::canFinishInstallation()) {
            return redirect()
                ->route('install.owner')
                ->with('error', 'Complete the setup steps before finishing.');
        }

        return view('install.complete');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->redirectIfPreflightIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfEnvironmentIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_COMPLETE)) {
            return $redirect;
        }

        if (! InstallState::canFinishInstallation()) {
            return redirect()
                ->route('install.owner')
                ->with('error', 'Complete the setup steps before finishing.');
        }

        if (! InstallState::hasOwnerAccount()) {
            $pendingOwner = InstallState::pendingOwner();

            if ($pendingOwner === null) {
                return redirect()
                    ->route('install.owner')
                    ->with('error', 'Enter Owner account details before finishing.');
            }

            $this->createOwner->executeWithPasswordHash(
                name: $pendingOwner['name'],
                email: $pendingOwner['email'],
                passwordHash: $pendingOwner['password_hash'],
            );
        }

        $this->writeInstallLock->execute();

        InstallState::invalidateWizardSession();

        $request->session()->regenerate();

        return redirect()
            ->route('login')
            ->with('installation_complete', true);
    }
}
