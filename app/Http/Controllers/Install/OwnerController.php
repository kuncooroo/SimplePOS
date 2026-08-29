<?php



declare(strict_types=1);



namespace App\Http\Controllers\Install;



use App\Http\Requests\Install\CreateOwnerRequest;

use App\Support\Install\InstallState;

use Illuminate\Contracts\View\View;

use Illuminate\Http\RedirectResponse;

use Illuminate\Support\Facades\Hash;



class OwnerController extends InstallStepController

{

    public function show(): View|RedirectResponse

    {

        if ($redirect = $this->redirectIfPreflightIncomplete()) {

            return $redirect;

        }



        if ($redirect = $this->redirectIfEnvironmentIncomplete()) {

            return $redirect;

        }



        if (! InstallState::migrationsCompleted()) {

            return redirect()

                ->route('install.migrate')

                ->with('error', 'Run database migrations before creating the Owner account.');

        }



        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_OWNER)) {

            return $redirect;

        }



        return view('install.owner', [

            'ownerExists' => InstallState::hasOwnerAccount(),

            'ownerSaved' => InstallState::hasPendingOwner() || InstallState::hasOwnerAccount(),

        ]);

    }



    public function store(CreateOwnerRequest $request): RedirectResponse

    {

        if ($redirect = $this->redirectIfPreflightIncomplete()) {

            return $redirect;

        }



        if ($redirect = $this->redirectIfEnvironmentIncomplete()) {

            return $redirect;

        }



        if (! InstallState::migrationsCompleted()) {

            return redirect()

                ->route('install.migrate')

                ->with('error', 'Run database migrations before creating the Owner account.');

        }



        if ($redirect = $this->redirectIfStepLocked(InstallState::STEP_OWNER)) {

            return $redirect;

        }



        if (InstallState::hasOwnerAccount()) {

            InstallState::advanceToSettings();



            return redirect()

                ->route('install.settings')

                ->with('status', 'owner-exists');

        }



        InstallState::storePendingOwner([

            'name' => (string) $request->input('name'),

            'email' => (string) $request->input('email'),

            'password_hash' => Hash::make((string) $request->input('password')),

        ]);



        InstallState::advanceToSettings();



        return redirect()

            ->route('install.settings')

            ->with('status', 'owner-saved');

    }

}

