<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Support\Install\InstallState;
use App\Support\Install\PermissionChecker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PermissionsController extends InstallStepController
{
    public function __construct(
        private PermissionChecker $checker,
    ) {}

    public function show(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_PERMISSIONS)) {
            return $redirect;
        }

        $report = $this->checker->run();

        return view('install.permissions', [
            'report' => $report,
            'canContinue' => $report->allRequiredPassed(),
        ]);
    }

    public function continue(): RedirectResponse
    {
        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_PERMISSIONS)) {
            return $redirect;
        }

        $report = $this->checker->run();

        if ($redirect = $this->redirectUnlessChecksPass($report)) {
            return $redirect;
        }

        InstallState::advanceToDatabase();

        return redirect()->route('install.database');
    }
}
