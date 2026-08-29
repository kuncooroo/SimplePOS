<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Actions\Install\TestDatabaseConnection;
use App\Http\Requests\Install\DatabaseConnectionRequest;
use App\Support\Install\InstallState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DatabaseController extends InstallStepController
{
    public function __construct(
        private TestDatabaseConnection $testDatabaseConnection,
    ) {}

    public function show(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfPreflightIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_DATABASE)) {
            return $redirect;
        }

        return view('install.database', [
            'connectionVerified' => InstallState::verifiedDatabaseCredentials() !== null,
        ]);
    }

    public function store(DatabaseConnectionRequest $request): RedirectResponse
    {
        if ($redirect = $this->redirectIfPreflightIncomplete()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_DATABASE)) {
            return $redirect;
        }

        $credentials = $request->credentials();

        $this->testDatabaseConnection->execute(
            host: $credentials['host'],
            port: $credentials['port'],
            database: $credentials['database'],
            username: $credentials['username'],
            password: $credentials['password'],
        );

        InstallState::storeVerifiedDatabaseCredentials($credentials);
        InstallState::advanceToEnvironment();

        return redirect()
            ->route('install.environment')
            ->with('status', 'database-verified');
    }
}
