<?php

use App\Http\Controllers\Api\Games\GameController;
use App\Http\Controllers\Api\Games\SkillGameController;
use Illuminate\Support\Facades\Route;

Route::prefix('games')
    ->group(function ()
    {
        Route::get('all', [GameController::class, 'index']);
        Route::get('recent-wins', [GameController::class, 'recentWins'])->middleware('throttle:60,1');
        Route::get('single/{id}', [GameController::class, 'show']);
        Route::post('favorite/{id}', [GameController::class, 'toggleFavorite']);
        Route::post('like/{id}', [GameController::class, 'toggleLike']);
    });

Route::prefix('featured')
    ->group(function ()
    {
        Route::any('/games', [GameController::class, 'featured']);
    });

Route::prefix('vgames')
    ->group(function ()
    {
        Route::any('/{token}/{action}', [GameController::class, 'sourceProvider']);
    });

Route::prefix('casinos')
    ->group(function ()
    {
        Route::get('games', [GameController::class, 'allGames']);
    });

// Jogos de habilidade locais (candy, subway, dino, fruitninja)
Route::prefix('skillgames')
    ->middleware('throttle:60,1')
    ->group(function ()
    {
        Route::post('start', [SkillGameController::class, 'start']);
        Route::post('finish', [SkillGameController::class, 'finish']);
    });

// Jogos originais (Mines, Dice, Plinko, Crash, Double)
Route::prefix('originals')
    ->middleware('throttle:240,1')
    ->group(function ()
    {
        Route::post('{game}/info', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'info'])
            ->whereIn('game', ['mines', 'dice', 'plinko', 'crash', 'double', 'tower', 'hilo', 'wheel']);

        Route::post('dice/play', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'dice']);
        Route::post('double/play', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'double']);
        Route::post('plinko/play', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'plinko']);
        Route::post('wheel/play', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'wheel']);

        Route::post('tower/start', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'towerStart']);
        Route::post('tower/reveal', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'towerReveal']);
        Route::post('tower/cashout', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'towerCashout']);

        Route::post('hilo/start', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'hiloStart']);
        Route::post('hilo/guess', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'hiloGuess']);
        Route::post('hilo/cashout', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'hiloCashout']);

        Route::post('mines/start', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'minesStart']);
        Route::post('mines/reveal', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'minesReveal']);
        Route::post('mines/cashout', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'minesCashout']);

        Route::post('crash/start', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'crashStart']);
        Route::post('crash/status', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'crashStatus']);
        Route::post('crash/cashout', [\App\Http\Controllers\Api\Games\OriginalsController::class, 'crashCashout']);
    });
