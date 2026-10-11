<?php

use App\Http\Controllers\Api\Games\GameController;
use Illuminate\Support\Facades\Route;

Route::post('/systemserver/webhook', [GameController::class, 'webhookSystemServer']);
