<?php

namespace App\Services\Payments\Gateways;

use App\Models\Transaction;
use App\Services\Payments\ChargeData;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PayPal — Checkout (Orders API v2). O cliente paga com saldo PayPal ou
 * cartão na página do PayPal; no retorno o pedido é capturado.
 * Docs: https://developer.paypal.com/docs/api/orders/v2/
 */
class PayPalGateway extends PaymentGateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return 'PayPal';
    }

    public function isEnabled(): bool
    {
        return (bool) $this->config->paypal_is_enable
            && !empty($this->config->credential('paypal_client_id'))
            && !empty($this->config->credential('paypal_client_secret'));
    }

    public function supportsCreditCard(): bool
    {
        return true;
    }

    public function supportsPix(): bool
    {
        return false;
    }

    private function baseUrl(): string
    {
        return $this->config->paypal_sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }

    private function accessToken(): string
    {
        $clientId = $this->requireCredential($this->config->credential('paypal_client_id'));
        $secret = $this->requireCredential($this->config->credential('paypal_client_secret'));
        $cacheKey = 'paypal_token_' . md5($this->baseUrl() . $clientId);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($clientId, $secret) {
            $response = $this->http()
                ->asForm()
                ->withBasicAuth($clientId, $secret)
                ->post($this->baseUrl() . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

            return $this->ensureSuccessful($response, 'Erro de autenticação')['access_token'];
        });
    }

    private function client(): PendingRequest
    {
        return $this->http()->baseUrl($this->baseUrl())->withToken($this->accessToken());
    }

    public function createPix(ChargeData $charge): array
    {
        throw new PaymentException('O PayPal não aceita PIX.');
    }

    public function createCreditCard(ChargeData $charge): array
    {
        $response = $this->client()
            ->withHeaders(['PayPal-Request-Id' => $charge->reference])
            ->post('/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $charge->reference,
                    'custom_id'    => $charge->reference,
                    'description'  => $charge->description,
                    'amount'       => [
                        'currency_code' => $charge->currency,
                        'value'         => number_format($charge->amount, 2, '.', ''),
                    ],
                ]],
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'brand_name'          => mb_substr(config('app.name', 'Casino'), 0, 127),
                            'user_action'         => 'PAY_NOW',
                            'shipping_preference' => 'NO_SHIPPING',
                            'return_url'          => url('/paypal/return', [], true),
                            'cancel_url'          => $this->returnUrl('cancel'),
                        ],
                    ],
                ],
            ]);

        $data = $this->ensureSuccessful($response, 'Erro ao criar pedido');
        $link = collect($data['links'] ?? [])->first(fn ($l) => in_array($l['rel'] ?? '', ['payer-action', 'approve'], true));

        if (empty($link['href'])) {
            throw new PaymentException('O PayPal não retornou o link de pagamento.');
        }

        return ['gateway_payment_id' => $data['id'], 'redirect_url' => $link['href']];
    }

    public function fetchStatus(Transaction $transaction): array
    {
        $order = $this->getOrder((string) $transaction->gateway_payment_id);

        return $order
            ? ['status' => $order['status'], 'amount' => $order['amount']]
            : ['status' => self::STATUS_PENDING, 'amount' => null];
    }

    /**
     * Consulta o pedido e captura automaticamente quando o cliente já aprovou.
     * @return array{status: string, amount: ?float, reference: ?string}|null
     */
    public function getOrder(string $orderId): ?array
    {
        if ($orderId === '') {
            return null;
        }

        $response = $this->client()->get('/v2/checkout/orders/' . urlencode($orderId));
        if (!$response->successful()) {
            return null;
        }

        $order = $response->json();

        if (($order['status'] ?? null) === 'APPROVED') {
            $capture = $this->client()
                ->withHeaders(['PayPal-Request-Id' => 'capture-' . $orderId])
                ->withBody('{}', 'application/json')
                ->post('/v2/checkout/orders/' . urlencode($orderId) . '/capture');

            if ($capture->successful()) {
                $order = $capture->json();
            } else {
                Log::warning('[paypal] Falha ao capturar pedido', ['order' => $orderId, 'body' => $capture->json()]);
            }
        }

        $unit = $order['purchase_units'][0] ?? [];
        $captures = collect($unit['payments']['captures'] ?? []);
        $completed = $captures->where('status', 'COMPLETED');

        $status = match (true) {
            ($order['status'] ?? null) === 'COMPLETED' && $completed->isNotEmpty() => self::STATUS_PAID,
            ($order['status'] ?? null) === 'VOIDED' || $captures->whereIn('status', ['DECLINED', 'FAILED'])->isNotEmpty() => self::STATUS_FAILED,
            default => self::STATUS_PENDING,
        };

        return [
            'status'    => $status,
            'amount'    => $status === self::STATUS_PAID ? (float) $completed->sum(fn ($c) => (float) $c['amount']['value']) : null,
            'reference' => $unit['custom_id'] ?? $unit['reference_id'] ?? null,
        ];
    }

    public function resolveWebhook(Request $request): ?string
    {
        $webhookId = $this->config->credential('paypal_webhook_key');
        if (empty($webhookId)) {
            Log::error('[paypal] Webhook recebido mas o "Webhook ID" não está configurado.');
            return null;
        }

        // Verificação oficial da assinatura pela API do PayPal
        $verify = $this->client()->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo'         => $request->header('paypal-auth-algo'),
            'cert_url'          => $request->header('paypal-cert-url'),
            'transmission_id'   => $request->header('paypal-transmission-id'),
            'transmission_sig'  => $request->header('paypal-transmission-sig'),
            'transmission_time' => $request->header('paypal-transmission-time'),
            'webhook_id'        => $webhookId,
            'webhook_event'     => json_decode($request->getContent(), true),
        ]);

        if (!$verify->successful() || $verify->json('verification_status') !== 'SUCCESS') {
            Log::warning('[paypal] Webhook com assinatura inválida');
            return null;
        }

        $orderId = match ($request->input('event_type')) {
            'CHECKOUT.ORDER.APPROVED', 'CHECKOUT.ORDER.COMPLETED' => $request->input('resource.id'),
            'PAYMENT.CAPTURE.COMPLETED' => $request->input('resource.supplementary_data.related_ids.order_id'),
            default => null,
        };

        return is_string($orderId) ? ($this->getOrder($orderId)['reference'] ?? null) : null;
    }
}
