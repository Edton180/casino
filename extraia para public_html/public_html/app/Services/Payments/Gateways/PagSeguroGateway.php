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
 * PagSeguro / PagBank — API de Pedidos (PIX) e Checkout PagBank (cartão).
 * Docs: https://developer.pagbank.com.br/reference
 */
class PagSeguroGateway extends PaymentGateway
{
    public function key(): string
    {
        return 'pagseguro';
    }

    public function label(): string
    {
        return 'PagSeguro';
    }

    public function isEnabled(): bool
    {
        return (bool) $this->config->pagseguro_is_enable && !empty($this->config->credential('pagseguro_token'));
    }

    public function supportsCreditCard(): bool
    {
        return (bool) $this->config->pagseguro_credit_card_enable;
    }

    private function baseUrl(): string
    {
        return $this->config->pagseguro_sandbox
            ? 'https://sandbox.api.pagseguro.com'
            : 'https://api.pagseguro.com';
    }

    private function client(): PendingRequest
    {
        return $this->http()
            ->baseUrl($this->baseUrl())
            ->withToken($this->requireCredential($this->config->credential('pagseguro_token')));
    }

    private function customer(ChargeData $charge): array
    {
        $customer = [
            'name'   => $charge->fullName(),
            'email'  => $charge->user->email,
            'tax_id' => $charge->document,
        ];

        if ($phone = $charge->phone()) {
            $customer['phones'] = [[
                'country' => '55',
                'area'    => $phone['area_code'],
                'number'  => $phone['number'],
                'type'    => 'MOBILE',
            ]];
        }

        return $customer;
    }

    private function items(ChargeData $charge): array
    {
        return [[
            'reference_id' => $charge->reference,
            'name'         => $charge->description,
            'quantity'     => 1,
            'unit_amount'  => $charge->amountInCents(),
        ]];
    }

    public function createPix(ChargeData $charge): array
    {
        $response = $this->client()->post('/orders', [
            'reference_id' => $charge->reference,
            'customer'     => $this->customer($charge),
            'items'        => $this->items($charge),
            'qr_codes' => [[
                'amount'          => ['value' => $charge->amountInCents()],
                'expiration_date' => now()->addMinutes(30)->toIso8601String(),
            ]],
            'notification_urls' => [$this->webhookUrl()],
        ]);

        $data = $this->ensureSuccessful($response, 'Erro ao gerar PIX');
        $qrCode = $data['qr_codes'][0] ?? null;

        if (empty($qrCode['text'])) {
            throw new PaymentException('O PagSeguro não retornou o código PIX.');
        }

        $image = collect($qrCode['links'] ?? [])->firstWhere('rel', 'QRCODE.PNG');

        return [
            'gateway_payment_id' => $data['id'],
            'qrcode'             => $qrCode['text'],
            'qrcode_image'       => $image['href'] ?? null,
        ];
    }

    public function createCreditCard(ChargeData $charge): array
    {
        $response = $this->client()->post('/checkouts', [
            'reference_id'        => $charge->reference,
            'customer'            => $this->customer($charge),
            'customer_modifiable' => true,
            'items'               => $this->items($charge),
            'payment_methods'     => [['type' => 'CREDIT_CARD']],
            'payment_methods_configs' => [[
                'type'           => 'CREDIT_CARD',
                'config_options' => [['option' => 'INSTALLMENTS_LIMIT', 'value' => '1']],
            ]],
            'expiration_date'           => now()->addHours(2)->toIso8601String(),
            'redirect_url'              => $this->returnUrl('success'),
            'notification_urls'         => [$this->webhookUrl()],
            'payment_notification_urls' => [$this->webhookUrl()],
        ]);

        $data = $this->ensureSuccessful($response, 'Erro ao criar checkout');
        $pay = collect($data['links'] ?? [])->firstWhere('rel', 'PAY');

        if (empty($pay['href'])) {
            throw new PaymentException('O PagSeguro não retornou o link de pagamento.');
        }

        return ['gateway_payment_id' => $data['id'], 'redirect_url' => $pay['href']];
    }

    public function fetchStatus(Transaction $transaction): array
    {
        $pending = ['status' => self::STATUS_PENDING, 'amount' => null];
        $id = (string) $transaction->gateway_payment_id;

        if (str_starts_with($id, 'ORDE_')) {
            return $this->orderStatus($id) ?? $pending;
        }

        if (str_starts_with($id, 'CHEC_')) {
            $response = $this->client()->get('/checkouts/' . urlencode($id));
            if (!$response->successful()) {
                return $pending;
            }

            foreach ($response->json('orders') ?? [] as $order) {
                $status = isset($order['id']) ? $this->orderStatus($order['id']) : null;
                if ($status && $status['status'] === self::STATUS_PAID) {
                    return $status;
                }
            }
        }

        return $pending;
    }

    /**
     * @return array{status: string, amount: ?float, reference: ?string}|null
     */
    private function orderStatus(string $orderId): ?array
    {
        $response = $this->client()->get('/orders/' . urlencode($orderId));
        if (!$response->successful()) {
            return null;
        }

        $order = $response->json();
        $charges = $order['charges'] ?? [];

        foreach ($charges as $charge) {
            if (($charge['status'] ?? null) === 'PAID') {
                return [
                    'status'    => self::STATUS_PAID,
                    'amount'    => ($charge['amount']['summary']['paid'] ?? $charge['amount']['value'] ?? 0) / 100,
                    'reference' => $order['reference_id'] ?? null,
                ];
            }
        }

        $failed = !empty($charges) && collect($charges)->every(
            fn ($c) => in_array($c['status'] ?? '', ['DECLINED', 'CANCELED'], true)
        );

        return [
            'status'    => $failed ? self::STATUS_FAILED : self::STATUS_PENDING,
            'amount'    => null,
            'reference' => $order['reference_id'] ?? null,
        ];
    }

    public function resolveWebhook(Request $request): ?string
    {
        // Assinatura: sha256("{token}-{payload}") enviada em x-authenticity-token
        $signature = $request->header('x-authenticity-token');
        if (!empty($signature)) {
            $expected = hash('sha256', $this->config->credential('pagseguro_token') . '-' . $request->getContent());
            if (!hash_equals($expected, $signature)) {
                Log::warning('[pagseguro] Webhook com assinatura inválida');
                return null;
            }
        }

        $orderId = $request->input('id');
        if (!is_string($orderId) || !str_starts_with($orderId, 'ORDE_')) {
            return null;
        }

        $order = $this->orderStatus($orderId);
        $reference = $order['reference'] ?? null;

        // Pedidos criados pelo Checkout PagBank: passa a acompanhar o pedido
        // (ORDE_) em vez do checkout (CHEC_), que pode não listar o pedido.
        if ($reference) {
            Transaction::where('payment_id', $reference)
                ->where('gateway', $this->key())
                ->where('gateway_payment_id', 'like', 'CHEC\_%')
                ->update(['gateway_payment_id' => $orderId]);
        }

        return $reference;
    }
}
