<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Services\Payments\DepositService;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recebe os webhooks de Mercado Pago, PagSeguro, Pagar.me, Asaas e Stripe.
 * O corpo do webhook só é usado para descobrir qual depósito reconsultar;
 * o crédito acontece apenas depois de confirmar o status na API do gateway.
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentManager $payments, DepositService $deposits)
    {
        if (!array_key_exists($gateway, PaymentManager::GATEWAYS)) {
            return response()->json(['status' => false], 404);
        }

        try {
            $reference = $payments->get($gateway)->resolveWebhook($request);

            if ($reference) {
                $status = $deposits->confirm($reference);
                Log::info("[{$gateway}] Webhook processado", ['reference' => $reference, 'status' => $status]);
            }
        } catch (\Throwable $e) {
            Log::error("[{$gateway}] Erro ao processar webhook", ['error' => $e->getMessage()]);

            // 500 faz o gateway reenviar a notificação mais tarde
            return response()->json(['status' => false], 500);
        }

        // Sempre 200 para eventos ignorados, evitando reenvios infinitos
        return response()->json(['status' => true]);
    }

    /**
     * O PayPal redireciona o cliente para cá com ?token={ID do pedido}.
     */
    public function paypalReturn(Request $request, DepositService $deposits)
    {
        $transaction = \App\Models\Transaction::where('gateway', 'paypal')
            ->where('gateway_payment_id', (string) $request->query('token'))
            ->first();

        $status = 'pending';
        if ($transaction) {
            try {
                $status = $deposits->confirm($transaction->payment_id);
            } catch (\Throwable $e) {
                Log::error('[paypal] Erro no retorno do checkout', ['error' => $e->getMessage()]);
            }
        }

        return redirect('/?deposit=' . ($status === 'paid' ? 'success' : 'pending'));
    }
}
