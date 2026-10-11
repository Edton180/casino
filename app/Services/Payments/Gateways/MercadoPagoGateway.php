<?php

namespace App\Services\Payments\Gateways;

use App\Models\Transaction;
use App\Services\Payments\ChargeData;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Mercado Pago — PIX via API de Pagamentos (/v1/payments) e cartão via
 * Checkout Pro (/checkout/preferences).
 * Docs: https://www.mercadopago.com.br/developers/pt/reference
 */
class MercadoPagoGateway extends PaymentGateway
{
    private const BASE_URL = 'https://api.mercadopago.com';

    public function key(): string
    {
        return 'mercadopago';
    }

    public function label(): string
    {
        return 'Mercado Pago';
    }

    public function isEnabled(): bool
    {
        return (bool) $this->config->mp_is_enable && !empty($this->config->credential('mp_access_token'));
    }

    public function supportsCreditCard(): bool
    {
        return (bool) $this->config->mp_credit_card_enable;
    }

    private function client(): PendingRequest
    {
        return $this->http()
            ->baseUrl(self::BASE_URL)
            ->withToken($this->requireCredential($this->config->credential('mp_access_token')));
    }

    private function isTestToken(): bool
    {
        return str_starts_with((string) $this->config->credential('mp_access_token'), 'TEST-');
    }

    public function createPix(ChargeData $charge): array
    {
        $response = $this->client()
            ->withHeaders(['X-Idempotency-Key' => $charge->reference])
            ->post('/v1/payments', [
                'transaction_amount' => round($charge->amount, 2),
                'description'        => $charge->description,
                'payment_method_id'  => 'pix',
                'external_reference' => $charge->reference,
                'notification_url'   => $this->webhookUrl(),
                'date_of_expiration' => now()->addMinutes(30)->format('Y-m-d\TH:i:s.vP'),
                'payer' => [
                    'email'          => $charge->user->email,
                    'first_name'     => $charge->firstName(),
                    'last_name'      => $charge->lastName(),
                    'identification' => [
                        'type'   => $charge->documentType(),
                        'number' => $charge->document,
                    ],
                ],
            ]);

        $data = $this->ensureSuccessful($response, 'Erro ao gerar PIX');
        $transactionData = $data['point_of_interaction']['transaction_data'] ?? [];

        if (empty($transactionData['qr_code'])) {
            throw new PaymentException('O Mercado Pago não retornou o código PIX.');
        }

        return [
            'gateway_payment_id' => (string) $data['id'],
            'qrcode'             => $transactionData['qr_code'],
            'qrcode_image'       => isset($transactionData['qr_code_base64'])
                ? 'data:image/png;base64,' . $transactionData['qr_code_base64']
                : null,
        ];
    }

    public function createCreditCard(ChargeData $charge): array
    {
        $response = $this->client()->post('/checkout/preferences', [
            'items' => [[
                'id'          => $charge->reference,
                'title'       => $charge->description,
                'quantity'    => 1,
                'currency_id' => $charge->currency,
                'unit_price'  => round($charge->amount, 2),
            ]],
            'payer' => [
                'email'   => $charge->user->email,
                'name'    => $charge->firstName(),
                'surname' => $charge->lastName(),
            ],
            'payment_methods' => [
                'excluded_payment_types' => [['id' => 'ticket'], ['id' => 'atm']],
                'installments'           => 1,
            ],
            'external_reference' => $charge->reference,
            'notification_url'   => $this->webhookUrl(),
            'back_urls' => [
                'success' => $this->returnUrl('success'),
                'pending' => $this->returnUrl('pending'),
                'failure' => $this->returnUrl('failure'),
            ],
            'auto_return' => 'approved',
        ]);

        $data = $this->ensureSuccessful($response, 'Erro ao criar checkout');
        $url = $this->isTestToken() ? ($data['sandbox_init_point'] ?? null) : ($data['init_point'] ?? null);

        if (empty($url)) {
            throw new PaymentException('O Mercado Pago não retornou o link de pagamento.');
        }

        return ['gateway_payment_id' => (string) $data['id'], 'redirect_url' => $url];
    }

    public function fetchStatus(Transaction $transaction): array
    {
        $response = $this->client()->get('/v1/payments/search', [
            'external_reference' => $transaction->payment_id,
            'sort'               => 'date_created',
            'criteria'           => 'desc',
        ]);

        if (!$response->successful()) {
            return ['status' => self::STATUS_PENDING, 'amount' => null];
        }

        $results = $response->json('results') ?? [];
        foreach ($results as $payment) {
            if (($payment['status'] ?? null) === 'approved') {
                return ['status' => self::STATUS_PAID, 'amount' => (float) $payment['transaction_amount']];
            }
        }

        $failed = !empty($results) && collect($results)->every(
            fn ($p) => in_array($p['status'] ?? '', ['rejected', 'cancelled', 'refunded', 'charged_back'], true)
        );

        return ['status' => $failed ? self::STATUS_FAILED : self::STATUS_PENDING, 'amount' => null];
    }

    public function resolveWebhook(Request $request): ?string
    {
        $type = $request->input('type', $request->query('type', $request->input('topic')));
        $paymentId = $request->input('data.id', $request->query('data_id', $request->query('id')));

        if ($type !== 'payment' || empty($paymentId)) {
            return null;
        }

        if (!$this->hasValidSignature($request, (string) $paymentId)) {
            Log::warning('[mercadopago] Webhook com assinatura inválida', ['id' => $paymentId]);
            return null;
        }

        $response = $this->client()->get('/v1/payments/' . urlencode((string) $paymentId));
        if (!$response->successful()) {
            return null;
        }

        return $response->json('external_reference');
    }

    /**
     * Valida o header x-signature quando a "assinatura secreta" está configurada.
     * https://www.mercadopago.com.br/developers/pt/docs/your-integrations/notifications/webhooks
     */
    private function hasValidSignature(Request $request, string $dataId): bool
    {
        $secret = $this->config->credential('mp_webhook_key');
        if (empty($secret)) {
            return true; // sem segredo configurado: o status é sempre reconsultado na API
        }

        $parts = [];
        foreach (explode(',', (string) $request->header('x-signature')) as $piece) {
            [$k, $v] = array_pad(explode('=', trim($piece), 2), 2, null);
            $parts[$k] = $v;
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }

        $manifest = 'id:' . strtolower($dataId) . ';';
        if ($request->header('x-request-id')) {
            $manifest .= 'request-id:' . $request->header('x-request-id') . ';';
        }
        $manifest .= 'ts:' . $parts['ts'] . ';';

        return hash_equals(hash_hmac('sha256', $manifest, $secret), $parts['v1']);
    }
}
