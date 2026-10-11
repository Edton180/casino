<?php

use App\Http\Controllers\Api\Games\GameController;
use App\Http\Controllers\Api\Games\SkillGameController;
use Illuminate\Support\Facades\Route;

Route::prefix('games')
    ->group(function ()
    {
        Route::get('all', [GameController::class, 'index']);
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
