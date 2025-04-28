<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            // Asaas
            $table->boolean('asaas_credit_card_enable')->default(false)->after('asaas_is_enable');

            // Mercado Pago
            $table->boolean('mp_sandbox')->default(true)->after('mp_public_key');
            $table->boolean('mp_is_enable')->default(false)->after('mp_sandbox');
            $table->boolean('mp_credit_card_enable')->default(false)->after('mp_is_enable');

            // PayPal
            $table->string('paypal_client_id')->nullable()->after('mp_credit_card_enable');
            $table->string('paypal_client_secret')->nullable()->after('paypal_client_id');
            $table->boolean('paypal_sandbox')->default(true)->after('paypal_client_secret');
            $table->boolean('paypal_is_enable')->default(false)->after('paypal_sandbox');
            $table->boolean('paypal_credit_card_enable')->default(false)->after('paypal_is_enable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn([
                'asaas_credit_card_enable',
                'mp_sandbox',
                'mp_is_enable',
                'mp_credit_card_enable',
                'paypal_client_id',
                'paypal_client_secret',
                'paypal_sandbox',
                'paypal_is_enable',
                'paypal_credit_card_enable'
            ]);
        });
    }
}; 