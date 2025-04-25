<?php

use App\Http\Controllers\Api\Games\GameController;
use Illuminate\Support\Facades\Route;

Route::prefix('callback')
    ->group(function ()
    {
        Route::match(['get', 'post'], 'gitslotpark/{brand}/{verb?}', [GameController::class, 'webhookGitSlotParkMethod']);
    });

