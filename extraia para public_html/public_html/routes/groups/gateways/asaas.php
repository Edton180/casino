<?php

use App\Http\Controllers\Gateway\AsaasController;
use Illuminate\Support\Facades\Route;

Route::prefix('asaas')->group(function () {
    Route::post('callback', [AsaasController::class, 'callbackMethod']);
    Route::post('payment', [AsaasController::class, 'callbackMethodPayment']);
}); 