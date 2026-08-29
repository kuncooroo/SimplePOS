<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Actions\Install\GenerateApplicationKey;
use App\Actions\Install\WriteEnvironmentFile;
use App\Http\Requests\Install\EnvironmentRequest;
use App\Support\Install\InstallState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EnvironmentController extends InstallStepController
{
    public function __construct(
        private WriteEnvironmentFile $writeEnvironmentFile,
        private GenerateApplicationKey $generateApplicationKey,
    ) {}

    public function show(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfPreflightIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_ENVIRONMENT)) {
            return $redirect;
        }

        if (InstallState::verifiedDatabaseCredentials() === null) {
            return redirect()
                ->route('install.database')
                ->with('error', 'Verify the database connection before configuring the environment.');
        }

        return view('install.environment', [
            'environmentWritten' => InstallState::environmentWritten(),
        ]);
    }

    public function store(EnvironmentRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectIfPreflightIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_ENVIRONMENT)) {
            return $redirect;
        }

        $database = InstallState::verifiedDatabaseCredentials();

        if ($database === null) {
            return redirect()
                ->route('install.database')
                ->with('error', 'Verify the database connection before configuring the environment.');
        }

        $this->writeEnvironmentFile->execute(
            database: $database,
            application: $request->applicationSettings(),
        );

        $this->generateApplicationKey->execute();

        InstallState::clearDatabaseCredentials();
        InstallState::markEnvironmentWritten();
        InstallState::advanceToMigrate();

        return redirect()
            ->route('install.migrate')
            ->with('status', 'environment-saved');
    }
}
