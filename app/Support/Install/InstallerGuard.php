<?php



declare(strict_types=1);



namespace App\Support\Install;



final class InstallerGuard

{

    public static function ensureUninstalled(): void

    {

        if (InstallState::isInstalled()) {

            abort(404);

        }

    }

}

