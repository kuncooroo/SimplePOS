<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Actions\Install\SeedDemoData;
use App\Http\Requests\Install\DemoDataRequest;
use App\Support\Install\InstallState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DemoDataController extends InstallStepController
{
    public function __construct(
        private SeedDemoData $seedDemoData,
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
                ->with('error', 'Enter Owner account details before demo data.');
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_DEMO)) {
            return $redirect;
        }

        return view('install.demo');
    }

    public function store(DemoDataRequest $request): RedirectResponse
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
                ->with('error', 'Enter Owner account details before demo data.');
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_DEMO)) {
            return $redirect;
        }

        $result = $this->seedDemoData->execute($request->boolean('demo'));

        InstallState::advanceToComplete();

        return redirect()
            ->route('install.complete')
            ->with('status', match ($result) {
                SeedDemoData::RESULT_SEEDED => 'demo-seeded',
                SeedDemoData::RESULT_WARNED => 'demo-skipped-transactions',
                default => 'demo-skipped',
            });
    }
}
