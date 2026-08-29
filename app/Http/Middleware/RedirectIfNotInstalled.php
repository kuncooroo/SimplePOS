<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Install\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotInstalled
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallState::isInstalled() || $this->shouldBypass($request)) {
            return $next($request);
        }

        return redirect()->route('install.welcome');
    }

    private function shouldBypass(Request $request): bool
    {
        return $request->is('install', 'install/*')
            || $request->is('up')
            || $request->is('build/*');
    }
}
