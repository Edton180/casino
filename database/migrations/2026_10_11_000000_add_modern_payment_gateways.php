<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gateways atuais: Mercado Pago, PagSeguro (PagBank), Pagar.me, Asaas e Stripe.
 * Todas as colunas são criadas apenas se ainda não existirem, porque o banco
 * pode ter vindo tanto das migrations quanto do dump SQL de instalação.
 */
return new class extends Migration
{
    private array $gatewayColumns = [
        // Mercado Pago
        'mp_is_enable'                 => ['boolean', false],
        'mp_credit_card_enable'        => ['boolean', false],
        'mp_access_token'              => ['string', null],
        'mp_public_key'                => ['string', null],
        'mp_webhook_key'               => ['string', null],

        // PagSeguro / PagBank
        'pagseguro_is_enable'          => ['boolean', false],
        'pagseguro_credit_card_enable' => ['boolean', false],
        'pagseguro_sandbox'            => ['boolean', true],
        'pagseguro_token'              => ['text', null],

        // Pagar.me
        'pagarme_is_enable'            => ['boolean', false],
        'pagarme_credit_card_enable'   => ['boolean', false],
        'pagarme_secret_key'           => ['string', null],
        'pagarme_webhook_user'         => ['string', null],
        'pagarme_webhook_password'     => ['string', null],

        // Asaas
        'asaas_is_enable'              => ['boolean', false],
        'asaas_credit_card_enable'     => ['boolean', false],
        'asaas_sandbox'                => ['boolean', true],
        'asaas_api_key'                => ['text', null],
        'asaas_webhook_key'            => ['string', null],
        'asaas_pix_payout_enable'      => ['boolean', false],

        // Stripe
        'stripe_is_enable'             => ['boolean', false],
        'stripe_pix_enable'            => ['boolean', false],
        'stripe_production'            => ['boolean', false],
        'stripe_public_key'            => ['string', null],
        'stripe_secret_key'            => ['string', null],
        'stripe_webhook_key'           => ['string', null],
    ];

    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            foreach ($this->gatewayColumns as $column => [$type, $default]) {
                if (Schema::hasColumn('gateways', $column)) {
                    continue;
                }

                $definition = $table->{$type}($column)->nullable();
                if ($default !== null) {
                    $definition->default($default);
                }
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'gateway')) {
                $table->string('gateway', 50)->nullable();
            }
            if (!Schema::hasColumn('transactions', 'gateway_payment_id')) {
                $table->string('gateway_payment_id', 191)->nullable()->index();
            }
            if (!Schema::hasColumn('transactions', 'accept_bonus')) {
                $table->boolean('accept_bonus')->default(true);
            }
        });
    }

    public function down(): void
    {
        $new = ['pagseguro_is_enable', 'pagseguro_credit_card_enable', 'pagseguro_sandbox', 'pagseguro_token',
            'pagarme_is_enable', 'pagarme_credit_card_enable', 'pagarme_secret_key', 'pagarme_webhook_user',
            'pagarme_webhook_password', 'asaas_pix_payout_enable', 'stripe_is_enable', 'stripe_pix_enable'];

        Schema::table('gateways', function (Blueprint $table) use ($new) {
            foreach ($new as $column) {
                if (Schema::hasColumn('gateways', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'gateway_payment_id')) {
                $table->dropColumn('gateway_payment_id');
            }
        });
    }
};
