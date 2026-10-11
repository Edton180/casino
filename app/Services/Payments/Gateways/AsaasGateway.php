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
 * Asaas (API v3) — cobranças PIX, fatura com cartão e transferências PIX
 * para pagamento automático de saques.
 * Docs: https://docs.asaas.com/reference
 */
class AsaasGateway extends PaymentGateway
{
    public function key(): string
    {
        return 'asaas';
    }

    public function label(): string
    {
        return 'Asaas';
    }

    public function isEnabled(): bool
    {
        return (bool) $this->config->asaas_is_enable && !empty($this->config->credential('asaas_api_key'));
    }

    public function supportsCreditCard(): bool
    {
        return (bool) $this->config->asaas_credit_card_enable;
    }

    public function supportsPixPayout(): bool
    {
        return !empty($this->config->credential('asaas_api_key')) && (bool) $this->config->asaas_pix_payout_enable;
    }

    private function baseUrl(): string
    {
        return $this->config->asaas_sandbox
            ? 'https://api-sandbox.asaas.com/v3'
            : 'https://api.asaas.com/v3';
    }

    private function client(): PendingRequest
    {
        return $this->http()
            ->baseUrl($this->baseUrl())
            ->withHeaders([
                'access_token' => $this->requireCredential($this->config->credential('asaas_api_key')),
                'User-Agent'   => config('app.name', 'Casino') . '/payments',
            ]);
    }

    /**
     * Busca o cliente pelo CPF/CNPJ ou cria um novo.
     */
    private function customerId(ChargeData $charge): string
    {
        $found = $this->client()->get('/customers', ['cpfCnpj' => $charge->document, 'limit' => 1]);
        if ($found->successful() && !empty($found->json('data.0.id'))) {
            return $found->json('data.0.id');
        }

        $payload = [
            'name'                 => $charge->fullName(),
            'cpfCnpj'              => $charge->document,
            'email'                => $charge->user->email,
            'externalReference'    => (string) $charge->user->id,
            'notificationDisabled' => true,
        ];

        if ($phone = $charge->phone()) {
            $payload['mobilePhone'] = $phone['area_code'] . $phone['number'];
        }

        $data = $this->ensureSuccessful($this->client()->post('/customers', $payload), 'Erro ao criar cliente');

        return $data['id'];
    }

    private function createPayment(ChargeData $charge, string $billingType): array
    {
        $response = $this->client()->post('/payments', [
            'customer'          => $this->customerId($charge),
            'billingType'       => $billingType,
            'value'             => round($charge->amount, 2),
            'dueDate'           => now()->addDay()->format('Y-m-d'),
            'description'       => $charge->description,
            'externalReference' => $charge->reference,
            'callback'          => [
                'successUrl'   => $this->returnUrl('success'),
                'autoRedirect' => true,
            ],
        ]);

        return $this->ensureSuccessful($response, 'Erro ao criar cobrança');
    }

    public function createPix(ChargeData $charge): array
    {
        $payment = $this->createPayment($charge, 'PIX');

        $qr = $this->ensureSuccessful(
            $this->client()->get('/payments/' . $payment['id'] . '/pixQrCode'),
            'Erro ao obter QR Code PIX'
        );

        if (empty($qr['payload'])) {
            throw new PaymentException('O Asaas não retornou o código PIX.');
        }

        return [
            'gateway_payment_id' => $payment['id'],
            'qrcode'             => $qr['payload'],
            'qrcode_image'       => !empty($qr['encodedImage']) ? 'data:image/png;base64,' . $qr['encodedImage'] : null,
        ];
    }

    public function createCreditCard(ChargeData $charge): array
    {
        $payment = $this->createPayment($charge, 'CREDIT_CARD');

        if (empty($payment['invoiceUrl'])) {
            throw new PaymentException('O Asaas não retornou o link de pagamento.');
        }

        return ['gateway_payment_id' => $payment['id'], 'redirect_url' => $payment['invoiceUrl']];
    }

    public function fetchStatus(Transaction $transaction): array
    {
        $payment = $this->getPayment((string) $transaction->gateway_payment_id);

        return $payment
            ? ['status' => $payment['status'], 'amount' => $payment['amount']]
            : ['status' => self::STATUS_PENDING, 'amount' => null];
    }

    /**
     * @return array{status: string, amount: ?float, reference: ?string}|null
     */
    private function getPayment(string $paymentId): ?array
    {
        if ($paymentId === '') {
            return null;
        }

        $response = $this->client()->get('/payments/' . urlencode($paymentId));
        if (!$response->successful()) {
            return null;
        }

        $payment = $response->json();
        $status = match ($payment['status'] ?? '') {
            'RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH' => self::STATUS_PAID,
            'REFUNDED', 'REFUND_REQUESTED', 'CHARGEBACK_REQUESTED', 'CHARGEBACK_DISPUTE', 'DELETED' => self::STATUS_FAILED,
            default => self::STATUS_PENDING,
        };

        return [
            'status'    => $status,
            'amount'    => $status === self::STATUS_PAID ? (float) $payment['value'] : null,
            'reference' => $payment['externalReference'] ?? null,
        ];
    }

    public function resolveWebhook(Request $request): ?string
    {
        $token = $this->config->credential('asaas_webhook_key');
        if (!empty($token) && !hash_equals($token, (string) $request->header('asaas-access-token'))) {
            Log::warning('[asaas] Webhook com token inválido');
            return null;
        }

        if (!in_array($request->input('event'), ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'], true)) {
            return null;
        }

        $paymentId = $request->input('payment.id');

        return is_string($paymentId) ? ($this->getPayment($paymentId)['reference'] ?? null) : null;
    }

    /**
     * Envia um PIX (saque) via transferência Asaas.
     * @return string id da transferência
     */
    public function pixTransfer(float $amount, string $pixKey, string $pixType, string $description): string
    {
        $keyType = match ($pixType) {
            'document'    => strlen(preg_replace('/\D/', '', $pixKey)) > 11 ? 'CNPJ' : 'CPF',
            'email'       => 'EMAIL',
            'phoneNumber' => 'PHONE',
            default       => 'EVP',
        };

        if (in_array($keyType, ['CPF', 'CNPJ', 'PHONE'], true)) {
            $pixKey = preg_replace('/\D/', '', $pixKey);
        }

        $response = $this->client()->post('/transfers', [
            'value'             => round($amount, 2),
            'operationType'     => 'PIX',
            'pixAddressKey'     => $pixKey,
            'pixAddressKeyType' => $keyType,
            'description'       => $description,
        ]);

        return $this->ensureSuccessful($response, 'Erro ao enviar PIX de saque')['id'];
    }
}
