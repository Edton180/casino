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
// webhooks que existiam mas não eram carregados (o saldo desses provedores nunca era atualizado)
include_once(__DIR__ . '/groups/provider/kagaming.php');
include_once(__DIR__ . '/groups/provider/salsa.php');
include_once(__DIR__ . '/groups/provider/venix.php');
include_once(__DIR__ . '/groups/provider/vibra.php');
include_once(__DIR__ . '/groups/provider/aggregators.php');





// GATEWAYS (Mercado Pago, PagSeguro, Pagar.me, Asaas e Stripe)
include_once(__DIR__ . '/groups/gateways/payments.php');

/// SOCIAL
include_once(__DIR__ . '/groups/auth/social.php');

// APP
include_once(__DIR__ . '/groups/layouts/app.php');


