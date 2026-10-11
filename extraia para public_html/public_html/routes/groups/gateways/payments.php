<?php

use App\Http\Controllers\Gateway\PaymentWebhookController;
use App\Http\Controllers\Gateway\WithdrawalPaymentController;
use Illuminate\Support\Facades\Route;

/*
 * Webhooks: Mercado Pago, PagSeguro, Pagar.me, Asaas e Stripe
 * Configure no painel de cada gateway: https://seu-dominio/webhooks/{gateway}
 */
Route::post('webhooks/{gateway}', PaymentWebhookController::class)
    ->whereIn('gateway', ['mercadopago', 'pagseguro', 'pagarme', 'asaas', 'stripe', 'paypal'])
    ->name('payments.webhook');

// Retorno do checkout PayPal: captura o pagamento e volta para o site
Route::get('paypal/return', [PaymentWebhookController::class, 'paypalReturn'])->name('payments.paypal.return');

// URLs antigas mantidas para quem já configurou o webhook no gateway
Route::post('mercadopago/callback', PaymentWebhookController::class)->defaults('gateway', 'mercadopago');
Route::post('asaas/callback', PaymentWebhookController::class)->defaults('gateway', 'asaas');

/*
 * Saques (painel administrativo)
 */
Route::prefix('payments')
    ->middleware(['admin.filament', 'authorize.withdrawal'])
    ->group(function () {
        Route::get('withdrawal/{id}/{action}', [WithdrawalPaymentController::class, 'approve'])
            ->whereIn('action', ['user', 'affiliate'])
            ->name('payments.withdrawal');

        Route::get('cancelwithdrawal/{id}/{action}', [WithdrawalPaymentController::class, 'cancel'])
            ->whereIn('action', ['user', 'affiliate'])
            ->name('payments.cancelwithdrawal');
    });
