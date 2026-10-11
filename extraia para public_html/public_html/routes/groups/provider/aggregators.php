<?php

use App\Http\Controllers\Api\Games\GameController;
use Illuminate\Support\Facades\Route;

/*
 * Webhooks dos agregadores que tinham método no controller mas nenhuma rota.
 * Informe estas URLs no painel de cada provedor.
 */
Route::post('gold_api', [GameController::class, 'webhookGoldApiMethod']);            // Fivers (padrão "gold api")
Route::post('fivers/webhook', [GameController::class, 'webhookGoldApiMethod']);
Route::post('worldslot/webhook', [GameController::class, 'webhookWorldSlotMethod']);
Route::post('games2/webhook', [GameController::class, 'webhookGames2Method']);
Route::post('evergame/webhook', [GameController::class, 'webhookEvergameMethod']);
