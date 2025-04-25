<?php

namespace App\Http\Middleware;

use Closure;

class AuthorizeWithdrawalApproval
{
    public function handle($request, Closure $next)
    {
        // Verifica se o usuário está autenticado e tem permissão para aprovar depósitos
        if ($request->user() && $request->user()->hasRole('admin')) {
            return $next($request);
        }

        // Obtém o endereço IP do invasor
        $ip = $request->ip();

        // Mensagem para o invasor
        $message = "Ops! Parece que voce tentou acessar algo que nao deveria! ️ \n";
        $message .= "Seu IP ($ip) foi registrado BOA SORTE! ️\n";
        $message .= "Tenha um otimo dia! ";

        // Retorna a resposta com a mensagem informativa
        $redirectUrl = 'https://youtu.be/KsHvEQoLxgo?si=FooO47vcX9RCGM_1';

        // Retorna a resposta com o cabeçalho de redirecionamento e o tempo de espera
        return response()->json(['message' => $message])
            ->header('Refresh', '10;url=' . $redirectUrl);
    }
}
