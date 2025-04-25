<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',
        'suitpay/*',
        'bspay/*', // Adicionar isso
        'playfiver/*', // <--- Adicione essa linha
        'pixup/*',
        'webhooks/*',
        'cron/*',
        'playgaming',
        'playgaming/*',
        'playigaming_api',
        'playigaming_api/*',
        'systemserver/*',
        'callback',
        'callback/*',
        'gold_api',
        'gold_api/game_callback',
        'gold_api/user_balance',
        'gold_api/*'

    ];
}
