<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Support\Install\InstallCheckReport;
use App\Support\Install\InstallState;
use Illuminate\Http\RedirectResponse;

abstract class InstallStepController extends Controller
{
    protected function redirectIfStepLocked(int $minimumStep): ?RedirectResponse
    {
        if (InstallState::currentStep() < $minimumStep) {
            return redirect()
                ->route('install.welcome')
                ->with('error', 'Complete the previous setup steps first.');
        }

        return null;
    }

    protected function redirectUnlessChecksPass(InstallCheckReport $report): ?RedirectResponse
    {
        if ($report->allRequiredPassed()) {
            return null;
        }

        return back()->withErrors([
            'checks' => 'Fix every required check before continuing.',
        ]);
    }

    protected function redirectIfPreflightIncomplete(): ?RedirectResponse
    {
        if (InstallState::currentStep() < InstallState::STEP_DATABASE) {
            return redirect()
                ->route('install.requirements')
                ->with('error', 'Complete the system checks before continuing.');
        }

        return null;
    }

    protected function redirectIfEnvironmentIncomplete(): ?RedirectResponse
    {
        if (! InstallState::environmentWritten()) {
            return redirect()
                ->route('install.environment')
                ->with('error', 'Save the environment configuration before continuing.');
        }

        return null;
    }
}
