<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Support\Install\ExtensionChecker;
use App\Support\Install\InstallState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ExtensionsController extends InstallStepController
{
    public function __construct(
        private ExtensionChecker $checker,
    ) {}

    public function show(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_EXTENSIONS)) {
            return $redirect;
        }

        $report = $this->checker->run();

        return view('install.extensions', [
            'report' => $report,
            'canContinue' => $report->allRequiredPassed(),
        ]);
    }

    public function continue(): RedirectResponse
    {
        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_EXTENSIONS)) {
            return $redirect;
        }

        $report = $this->checker->run();

        if ($redirect = $this->redirectUnlessChecksPass($report)) {
            return $redirect;
        }

        InstallState::advanceToPermissions();

        return redirect()->route('install.permissions');
    }
}
