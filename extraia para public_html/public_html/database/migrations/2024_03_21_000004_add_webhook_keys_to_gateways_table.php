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
            $table->string('asaas_webhook_key')->nullable()->after('asaas_api_key');
            
            // Mercado Pago
            $table->string('mp_webhook_key')->nullable()->after('mp_public_key');
            
            // PayPal
            $table->string('paypal_webhook_key')->nullable()->after('paypal_client_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn([
                'asaas_webhook_key',
                'mp_webhook_key',
                'paypal_webhook_key'
            ]);
        });
    }
}; 