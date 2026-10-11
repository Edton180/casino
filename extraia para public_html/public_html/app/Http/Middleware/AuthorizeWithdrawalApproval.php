<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Só administradores podem aprovar saques.
 */
class AuthorizeWithdrawalApproval
{
    public function handle($request, Closure $next)
    {
        if ($request->user() && $request->user()->hasRole('admin')) {
            return $next($request);
        }

        abort(403, 'Acesso não autorizado.');
    }
}
