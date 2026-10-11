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
 * Pagar.me (API v5) — pedidos com PIX e Checkout Pagar.me para cartão.
 * Docs: https://docs.pagar.me/reference
 */
class PagarmeGateway extends PaymentGateway
{
    private const BASE_URL = 'https://api.pagar.me/core/v5';

    public function key(): string
    {
        return 'pagarme';
    }

    public function label(): string
    {
        return 'Pagar.me';
    }

    public function isEnabled(): bool
    {
        return (bool) $this->config->pagarme_is_enable && !empty($this->config->credential('pagarme_secret_key'));
    }

    public function supportsCreditCard(): bool
    {
        return (bool) $this->config->pagarme_credit_card_enable;
    }

    private function client(): PendingRequest
    {
        // Autenticação Basic: secret key como usuário e senha vazia
        return $this->http()
            ->baseUrl(self::BASE_URL)
            ->withBasicAuth($this->requireCredential($this->config->credential('pagarme_secret_key')), '');
    }

    private function orderPayload(ChargeData $charge, array $payment): array
    {
        $customer = [
            'name'          => $charge->fullName(),
            'email'         => $charge->user->email,
            'type'          => $charge->documentType() === 'CNPJ' ? 'company' : 'individual',
            'document'      => $charge->document,
            'document_type' => $charge->documentType(),
        ];

        if ($phone = $charge->phone()) {
            $customer['phones'] = ['mobile_phone' => [
                'country_code' => '55',
                'area_code'    => $phone['area_code'],
                'number'       => $phone['number'],
            ]];
        }

        return [
            'code'     => $charge->reference,
            'items'    => [[
                'code'        => $charge->reference,
                'amount'      => $charge->amountInCents(),
                'description' => $charge->description,
                'quantity'    => 1,
            ]],
            'customer' => $customer,
            'payments' => [$payment],
        ];
    }

    public function createPix(ChargeData $charge): array
    {
        $response = $this->client()->post('/orders', $this->orderPayload($charge, [
            'payment_method' => 'pix',
            'pix'            => ['expires_in' => 1800],
        ]));

        $data = $this->ensureSuccessful($response, 'Erro ao gerar PIX');
        $lastTransaction = $data['charges'][0]['last_transaction'] ?? [];

        if (($data['status'] ?? null) === 'failed' || empty($lastTransaction['qr_code'])) {
            Log::error('[pagarme] PIX sem QR Code', ['body' => $data]);
            throw new PaymentException('O Pagar.me não retornou o código PIX.');
        }

        return [
            'gateway_payment_id' => $data['id'],
            'qrcode'             => $lastTransaction['qr_code'],
            'qrcode_image'       => $lastTransaction['qr_code_url'] ?? null,
        ];
    }

    public function createCreditCard(ChargeData $charge): array
    {
        $response = $this->client()->post('/orders', $this->orderPayload($charge, [
            'payment_method' => 'checkout',
            'checkout'       => [
                'expires_in'                 => 120,
                'accepted_payment_methods'   => ['credit_card'],
                'success_url'                => $this->returnUrl('success'),
                'skip_checkout_success_page' => true,
                'customer_editable'          => true,
                'billing_address_editable'   => false,
                'credit_card' => [
                    'installments' => [['number' => 1, 'total' => $charge->amountInCents()]],
                ],
            ],
        ]));

        $data = $this->ensureSuccessful($response, 'Erro ao criar checkout');
        $url = $data['checkouts'][0]['payment_url'] ?? null;

        if (empty($url)) {
            throw new PaymentException('O Pagar.me não retornou o link de pagamento.');
        }

        return ['gateway_payment_id' => $data['id'], 'redirect_url' => $url];
    }

    public function fetchStatus(Transaction $transaction): array
    {
        $order = $this->getOrder((string) $transaction->gateway_payment_id);

        return $order
            ? ['status' => $order['status'], 'amount' => $order['amount']]
            : ['status' => self::STATUS_PENDING, 'amount' => null];
    }

    /**
     * @return array{status: string, amount: ?float, code: ?string}|null
     */
    private function getOrder(string $orderId): ?array
    {
        if ($orderId === '') {
            return null;
        }

        $response = $this->client()->get('/orders/' . urlencode($orderId));
        if (!$response->successful()) {
            return null;
        }

        $order = $response->json();
        $status = match ($order['status'] ?? '') {
            'paid'                => self::STATUS_PAID,
            'canceled', 'failed'  => self::STATUS_FAILED,
            default               => self::STATUS_PENDING,
        };

        $paid = collect($order['charges'] ?? [])->sum(fn ($c) => $c['paid_amount'] ?? 0);

        return [
            'status' => $status,
            'amount' => $status === self::STATUS_PAID ? ($paid ?: ($order['amount'] ?? 0)) / 100 : null,
            'code'   => $order['code'] ?? null,
        ];
    }

    public function resolveWebhook(Request $request): ?string
    {
        $user = $this->config->credential('pagarme_webhook_user');
        $password = $this->config->credential('pagarme_webhook_password');

        if (!empty($user) || !empty($password)) {
            if (!hash_equals((string) $user, (string) $request->getUser())
                || !hash_equals((string) $password, (string) $request->getPassword())) {
                Log::warning('[pagarme] Webhook com autenticação inválida');
                return null;
            }
        }

        $type = (string) $request->input('type');
        $orderId = str_starts_with($type, 'order.')
            ? $request->input('data.id')
            : $request->input('data.order.id');

        if (!is_string($orderId)) {
            return null;
        }

        return $this->getOrder($orderId)['code'] ?? null;
    }
}
