<?php

use App\Models\Game;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|Sme
*/
Route::get('clear', function() {
    Artisan::command('clear', function () {
        Artisan::call('optimize:clear');
       return back();
    });

    return back();
});

// GAMES PROVIDER
include_once(__DIR__ . '/groups/provider/playFiver.php'); // Adiciona somente essa linha no seu arquivo. 
include_once(__DIR__ . '/groups/provider/games.php');
include_once(__DIR__ . '/groups/provider/systemserver.php');
include_once(__DIR__ . '/groups/provider/apiPragmatic40.php');
include_once(__DIR__ . '/groups/provider/apiPg12.php');



Route::middleware(['authorize.withdrawal'])->get('/admin/roles', function () {
    // Lógica da rota "/admin/roles" aqui
});
Route::middleware(['authorize.withdrawal'])->get('/admin/permissions', function () {
    // Lógica da rota "/admin/roles" aqui
});


// GATEWAYS

include_once(__DIR__ . '/groups/gateways/bspay.php');  // Adicionar isso

/// SOCIAL
include_once(__DIR__ . '/groups/auth/social.php');

// APP
include_once(__DIR__ . '/groups/layouts/app.php');



