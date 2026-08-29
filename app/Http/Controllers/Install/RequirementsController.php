<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Support\Install\InstallState;
use App\Support\Install\RequirementChecker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RequirementsController extends InstallStepController
{
    public function __construct(
        private RequirementChecker $checker,
    ) {}

    public function show(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_REQUIREMENTS)) {
            return $redirect;
        }

        $report = $this->checker->run();

        return view('install.requirements', [
            'report' => $report,
            'canContinue' => $report->allRequiredPassed(),
        ]);
    }

    public function continue(): RedirectResponse
    {
        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_REQUIREMENTS)) {
            return $redirect;
        }

        $report = $this->checker->run();

        if ($redirect = $this->redirectUnlessChecksPass($report)) {
            return $redirect;
        }

        InstallState::advanceToExtensions();

        return redirect()->route('install.extensions');
    }
}
