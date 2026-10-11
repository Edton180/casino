<?php

use App\Http\Controllers\Api\Games\GameController;
use Illuminate\Support\Facades\Route;

Route::post('playigaming_api', [GameController::class, 'webhookPlayIGamingMethod']);
Route::post('playgaming', [GameController::class, 'webhookPlayGamingMethod']);
Route::post('cron/playconnect', [GameController::class, 'webhookPlayConnectMethod']);
