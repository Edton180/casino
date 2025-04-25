<?php


use App\Http\Controllers\Gateway\SuitPayController;
use Illuminate\Support\Facades\Route;

Route::prefix('suitpay')->group(function () {
    Route::post('callback', [SuitPayController::class, 'callbackMethod']);
    Route::post('payment', [SuitPayController::class, 'callbackMethodPayment']);

    Route::middleware(['admin.filament'])->group(function () {
        Route::get('withdrawal/{id}/{action}', [SuitPayController::class, 'withdrawalFromModal'])
            ->middleware('authorize.withdrawal') // Aplicando o middleware aqui
            ->name('suitpay.withdrawal');

        Route::get('cancelwithdrawal/{id}/{action}', [SuitPayController::class, 'cancelWithdrawalFromModal'])
            ->middleware('authorize.withdrawal') // Aplicando o middleware aqui
            ->name('suitpay.cancelwithdrawal');
    });
});


