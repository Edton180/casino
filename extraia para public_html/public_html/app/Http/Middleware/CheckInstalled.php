<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = file_exists(storage_path('installed.lock'));
        $isInstallerRoute = $request->is('install') || $request->is('install/*');

        if (! $installed && ! $isInstallerRoute) {
            return redirect('/install');
        }

        if ($installed && $isInstallerRoute) {
            return redirect('/');
        }

        return $next($request);
    }
}
