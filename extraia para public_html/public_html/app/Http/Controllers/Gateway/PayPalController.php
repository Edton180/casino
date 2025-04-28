<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Traits\Gateways\PayPalTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalController extends Controller
{
    use PayPalTrait;

    /**
     * Callback do PayPal
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function callbackMethod(Request $request)
    {
        Log::info('PayPal Callback', $request->all());

        $gateway = \App\Models\Gateway::first();
        
        // Verifica se a chave do webhook está correta
        if ($request->header('paypal-transmission-sig') !== $gateway->paypal_webhook_key) {
            Log::error('PayPal Callback - Assinatura inválida', [
                'received' => $request->header('paypal-transmission-sig'),
                'expected' => $gateway->paypal_webhook_key
            ]);
            return response()->json(['status' => false, 'message' => 'Assinatura inválida'], 401);
        }

        if ($request->event_type === 'PAYMENT.SALE.COMPLETED') {
            $payment = $request->resource;
            if (self::finalizePaymentPayPal($payment['id'])) {
                Log::info('PayPal Callback - Pagamento finalizado com sucesso', ['payment_id' => $payment['id']]);
                return response()->json(['status' => true]);
            }
        }

        Log::info('PayPal Callback - Evento não processado', ['event_type' => $request->event_type]);
        return response()->json(['status' => false]);
    }

    /**
     * Callback de pagamento
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function callbackMethodPayment(Request $request)
    {
        Log::info('PayPal Payment Callback', $request->all());
        return self::consultStatusTransactionPayPal($request);
    }
} 