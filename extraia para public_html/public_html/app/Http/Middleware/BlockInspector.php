<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockInspector
{
public function handle($request, Closure $next)
{
    // Desativa o menu de contexto do botão direito do mouse
    $response = $next($request);
    $response->header('Content-Security-Policy', 'script-src none');

    // Impede a execução do JavaScript do navegador quando a tecla de atalho é pressionada
    $response->setContent($response->getContent() . '<script>window.addEventListener("keydown", function(e) {
        if (e.key === "F12" || (e.key === "U" && e.ctrlKey && e.shiftKey)) {
            e.preventDefault();
        }
    });</script>');

    return $response;
}

}
