<?php

namespace App\Services\Payments\Gateways;

use App\Models\Transaction;
use App\Services\Payments\ChargeData;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Stripe — Checkout Session hospedado (cartão e, em BRL, PIX).
 * Docs: https://docs.stripe.com/api/checkout/sessions
 *
 * Correções em relação à integração antiga:
 *  - a chave secreta não é mais enviada ao navegador;
 *  - o webhook exige assinatura válida (Stripe-Signature);
 *  - valores com centavos não são mais truncados (intval * 100);
 *  - o crédito passa pelo mesmo fluxo dos demais gateways (bônus, rollover, CPA).
 */
class StripeGateway extends PaymentGateway
{
    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Stripe';
    }

    public function isEnabled(): bool
    {
        return (bool) $this->config->stripe_is_enable && !empty($this->config->credential('stripe_secret_key'));
    }

    public function supportsCreditCard(): bool
    {
        return true;
    }

    public function supportsPix(): bool
    {
        return (bool) $this->config->stripe_pix_enable;
    }

    private function client(): StripeClient
    {
        return new StripeClient($this->requireCredential($this->config->credential('stripe_secret_key')));
    }

    private function createSession(ChargeData $charge, string $method): Session
    {
        try {
            return $this->client()->checkout->sessions->create([
                'mode'                 => 'payment',
                'payment_method_types' => [$method],
                'client_reference_id'  => $charge->reference,
                'customer_email'       => $charge->user->email,
                'metadata'             => ['reference' => $charge->reference, 'user_id' => $charge->user->id],
                'payment_intent_data'  => ['metadata' => ['reference' => $charge->reference]],
                'line_items' => [[
                    'quantity'   => 1,
                    'price_data' => [
                        'currency'     => strtolower($charge->currency),
                        'unit_amount'  => $charge->amountInCents(),
                        'product_data' => ['name' => $charge->description],
                    ],
                ]],
                'expires_at'  => now()->addMinutes(60)->timestamp,
                'success_url' => $this->returnUrl('success'),
                'cancel_url'  => $this->returnUrl('cancel'),
            ], ['idempotency_key' => $charge->reference . '-' . $method]);
        } catch (\Throwable $e) {
            Log::error('[stripe] Erro ao criar Checkout Session', ['error' => $e->getMessage()]);
            throw new PaymentException('Não foi possível iniciar o pagamento com Stripe. Tente novamente.');
        }
    }

    public function createPix(ChargeData $charge): array
    {
        if (!$this->supportsPix()) {
            throw new PaymentException('PIX não está habilitado no Stripe.');
        }

        // O PIX do Stripe é exibido no checkout hospedado, por isso redirecionamos.
        $session = $this->createSession($charge, 'pix');

        return ['gateway_payment_id' => $session->id, 'redirect_url' => $session->url, 'qrcode' => ''];
    }

    public function createCreditCard(ChargeData $charge): array
    {
        $session = $this->createSession($charge, 'card');

        if (empty($session->url)) {
            throw new PaymentException('O Stripe não retornou o link de pagamento.');
        }

        return ['gateway_payment_id' => $session->id, 'redirect_url' => $session->url];
    }

    public function fetchStatus(Transaction $transaction): array
    {
        try {
            $session = $this->client()->checkout->sessions->retrieve((string) $transaction->gateway_payment_id);
        } catch (\Throwable $e) {
            return ['status' => self::STATUS_PENDING, 'amount' => null];
        }

        if ($session->payment_status === 'paid') {
            return ['status' => self::STATUS_PAID, 'amount' => $session->amount_total / 100];
        }

        return [
            'status' => $session->status === 'expired' ? self::STATUS_FAILED : self::STATUS_PENDING,
            'amount' => null,
        ];
    }

    public function resolveWebhook(Request $request): ?string
    {
        $secret = $this->config->credential('stripe_webhook_key');
        if (empty($secret)) {
            Log::error('[stripe] Webhook recebido mas o "Webhook Secret" (whsec_...) não está configurado.');
            return null;
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (\Throwable $e) {
            Log::warning('[stripe] Webhook com assinatura inválida', ['error' => $e->getMessage()]);
            return null;
        }

        if (!in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return null;
        }

        return $event->data->object->client_reference_id ?? null;
    }
}
