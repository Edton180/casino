<?php

/**
* Criado por @thigasdev -> https://t.me/thigasdev
 */
use App\Http\Controllers\Api\Games\GameController;
use Illuminate\Support\Facades\Route;

Route::post('/gold_api/user_balance', [GameController::class, 'webhookApiPg12UserBalance']);
Route::post('/gold_api/game_callback', [GameController::class, 'webhookApiPg12GameCallback']);