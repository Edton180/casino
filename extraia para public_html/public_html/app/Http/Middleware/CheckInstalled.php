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
        // o instalador é um script PHP independente em public/install/
        if (! $installed) {
            return redirect('/install/');
        }

        return $next($request);
    }
}
