<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyReferer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
     public function handle($request, Closure $next)
    {
        // Verificar se é uma solicitação POST
        if ($request->isMethod('post')) {
            // Obter o agente do usuário da solicitação
            $userAgent = $request->header('User-Agent');

            // Verificar se o agente do usuário é do Insomnia
            if (strpos($userAgent, 'insomnia') !== false) {
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

        // Se não for uma solicitação POST do Insomnia, permitir a solicitação
        return $next($request);
    }
}