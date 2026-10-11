<?php

namespace App\Services\Payments;

use App\Models\Gateway;
use App\Services\Payments\Gateways\AsaasGateway;
use App\Services\Payments\Gateways\MercadoPagoGateway;
use App\Services\Payments\Gateways\PagarmeGateway;
use App\Services\Payments\Gateways\PagSeguroGateway;
use App\Services\Payments\Gateways\StripeGateway;

/**
 * Registro dos gateways de pagamento disponíveis na plataforma.
 */
class PaymentManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    public const GATEWAYS = [
        'mercadopago' => MercadoPagoGateway::class,
        'pagseguro'   => PagSeguroGateway::class,
        'pagarme'     => PagarmeGateway::class,
        'asaas'       => AsaasGateway::class,
        'stripe'      => StripeGateway::class,
    ];

    private ?Gateway $config = null;

    private function config(): Gateway
    {
        return $this->config ??= (Gateway::first() ?? new Gateway());
    }

    public function get(string $key): PaymentGateway
    {
        $class = self::GATEWAYS[$key] ?? null;
        if ($class === null) {
            throw new PaymentException('Gateway de pagamento não encontrado.');
        }

        return new $class($this->config());
    }

    /** @return PaymentGateway[] */
    public function enabled(): array
    {
        $gateways = [];
        foreach (array_keys(self::GATEWAYS) as $key) {
            $gateway = $this->get($key);
            if ($gateway->isEnabled()) {
                $gateways[$key] = $gateway;
            }
        }

        return $gateways;
    }

    /**
     * Métodos de depósito exibidos no frontend (sem nenhuma credencial).
     */
    public function availableMethods(): array
    {
        $methods = [];

        foreach ($this->enabled() as $key => $gateway) {
            $hasPix = !($gateway instanceof StripeGateway) || $gateway->supportsPix();

            if ($hasPix) {
                $methods[] = ['gateway' => $key, 'method' => 'pix', 'label' => $gateway->label() . ' PIX'];
            }

            if ($gateway->supportsCreditCard()) {
                $methods[] = ['gateway' => $key, 'method' => 'credit_card', 'label' => $gateway->label() . ' Cartão'];
            }
        }

        return $methods;
    }
}
