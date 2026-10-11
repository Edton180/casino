<?php

namespace App\Services\Payments;

use App\Models\Gateway;
use App\Models\Transaction;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class PaymentGateway
{
    public const STATUS_PAID    = 'paid';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED  = 'failed';

    public function __construct(protected Gateway $config)
    {
    }

    /** Identificador usado na API e nas rotas (ex: "mercadopago"). */
    abstract public function key(): string;

    /** Nome exibido ao usuário. */
    abstract public function label(): string;

    abstract public function isEnabled(): bool;

    abstract public function supportsCreditCard(): bool;

    public function supportsPix(): bool
    {
        return true;
    }

    /**
     * Gera uma cobrança PIX.
     * @return array{gateway_payment_id: string, qrcode: string, qrcode_image?: ?string}
     */
    abstract public function createPix(ChargeData $charge): array;

    /**
     * Gera um checkout hospedado para cartão de crédito. Os dados do cartão
     * nunca passam pelo nosso servidor (exigência PCI).
     * @return array{gateway_payment_id: string, redirect_url: string}
     */
    abstract public function createCreditCard(ChargeData $charge): array;

    /**
     * Consulta o status no gateway (fonte da verdade).
     * "amount" é o valor efetivamente pago, quando o gateway informa; ele é
     * comparado com o valor do depósito antes de creditar o saldo.
     * @return array{status: string, amount: ?float}
     */
    abstract public function fetchStatus(Transaction $transaction): array;

    /**
     * Valida o webhook e devolve a referência interna (Transaction.payment_id)
     * cujo status deve ser reconsultado, ou null quando o evento deve ser ignorado.
     * Os créditos nunca são feitos com base apenas no corpo do webhook.
     */
    abstract public function resolveWebhook(Request $request): ?string;

    public function webhookUrl(): string
    {
        return url('/webhooks/' . $this->key(), [], true);
    }

    public function returnUrl(string $status = 'success'): string
    {
        return url('/?deposit=' . $status, [], true);
    }

    protected function http(): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout(30)->connectTimeout(10);
    }

    /**
     * Lança PaymentException com mensagem amigável e registra o erro técnico.
     */
    protected function ensureSuccessful(Response $response, string $context): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        Log::error("[{$this->key()}] {$context}", [
            'status' => $response->status(),
            'body'   => $response->json() ?? $response->body(),
        ]);

        throw new PaymentException('Não foi possível processar o pagamento com ' . $this->label() . '. Tente novamente.');
    }

    protected function requireCredential(?string $value): string
    {
        if (empty($value)) {
            throw new PaymentException($this->label() . ' não está configurado.');
        }

        return $value;
    }
}
