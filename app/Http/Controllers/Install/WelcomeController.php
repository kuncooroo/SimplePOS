<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Support\Install\InstallState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class WelcomeController extends Controller
{
    public function show(): View
    {
        InstallState::setStep(InstallState::STEP_WELCOME);

        return view('install.welcome');
    }

    public function continue(): RedirectResponse
    {
        InstallState::advanceToRequirements();

        return redirect()->route('install.requirements');
    }
}
