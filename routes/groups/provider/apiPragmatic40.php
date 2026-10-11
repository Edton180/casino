<?php

/**
* Criado por @thigasdev -> https://t.me/thigasdev
 */
use App\Http\Controllers\Api\Games\GameController;
use Illuminate\Support\Facades\Route;

Route::post('/callback', [GameController::class, 'webhookApiPragmatic40']);