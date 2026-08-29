<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Actions\Install\WriteInitialStoreSettings;
use App\Http\Requests\Install\InitialSettingsRequest;
use App\Support\Install\InstallState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends InstallStepController
{
    public function __construct(
        private WriteInitialStoreSettings $writeInitialStoreSettings,
    ) {}

    public function show(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfPreflightIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfEnvironmentIncomplete()) {
            return $redirect;
        }

        if (! InstallState::hasOwnerAccount() && ! InstallState::hasPendingOwner()) {
            return redirect()
                ->route('install.owner')
                ->with('error', 'Enter the protected Owner account details before store settings.');
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_SETTINGS)) {
            return $redirect;
        }

        return view('install.settings');
    }

    public function store(InitialSettingsRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectIfPreflightIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfEnvironmentIncomplete()) {
            return $redirect;
        }

        if (! InstallState::hasOwnerAccount() && ! InstallState::hasPendingOwner()) {
            return redirect()
                ->route('install.owner')
                ->with('error', 'Enter the protected Owner account details before store settings.');
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_SETTINGS)) {
            return $redirect;
        }

        $this->writeInitialStoreSettings->execute(
            storeName: (string) $request->input('store_name'),
            address: $request->input('address') !== null ? (string) $request->input('address') : null,
        );

        InstallState::advanceToDemo();

        return redirect()
            ->route('install.demo')
            ->with('status', 'settings-saved');
    }
}
