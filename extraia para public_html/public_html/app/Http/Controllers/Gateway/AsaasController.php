<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Traits\Gateways\AsaasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasController extends Controller
{
    use AsaasTrait;


    private function isValidWebhook(Request $request, ?string $expectedKey): bool
    {
        if (empty($expectedKey)) {
            return true;
        }

        $possibleKeys = [
            $request->header('asaas-signature'),
            $request->header('asaas-access-token'),
            $request->header('x-webhook-key'),
            $request->input('webhook_key'),
        ];

        foreach ($possibleKeys as $key) {
            if (!empty($key) && hash_equals((string) $expectedKey, (string) $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Callback do Asaas
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function callbackMethod(Request $request)
    {
        Log::info('Asaas Callback', $request->all());

        $gateway = \App\Models\Gateway::first();
        
        // Se a chave estiver configurada, valida o webhook por headers/token conhecidos
        if (!$this->isValidWebhook($request, $gateway?->asaas_webhook_key)) {
            Log::error('Asaas Callback - Assinatura inválida', [
                'received' => $request->header('asaas-signature'),
                'expected' => $gateway?->asaas_webhook_key
            ]);
            return response()->json(['status' => false, 'message' => 'Assinatura inválida'], 401);
        }

        if ($request->event === 'PAYMENT_CONFIRMED') {
            $payment = $request->payment;
            if (self::finalizePaymentAsaas($payment['id'])) {
                Log::info('Asaas Callback - Pagamento finalizado com sucesso', ['payment_id' => $payment['id']]);
                return response()->json(['status' => true]);
            }
        }

        Log::info('Asaas Callback - Evento não processado', ['event' => $request->event]);
        return response()->json(['status' => false]);
    }

    /**
     * Callback de pagamento
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function callbackMethodPayment(Request $request)
    {
        Log::info('Asaas Payment Callback', $request->all());
        return self::consultStatusTransactionAsaas($request);
    }
} 