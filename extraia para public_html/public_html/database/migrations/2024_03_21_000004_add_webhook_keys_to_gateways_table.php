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
            if (!Schema::hasColumn('gateways', 'asaas_webhook_key')) {
                $table->string('asaas_webhook_key')->nullable();
            }

            if (!Schema::hasColumn('gateways', 'mp_webhook_key')) {
                $table->string('mp_webhook_key')->nullable();
            }

            if (!Schema::hasColumn('gateways', 'paypal_webhook_key')) {
                $table->string('paypal_webhook_key')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            foreach (['asaas_webhook_key', 'mp_webhook_key', 'paypal_webhook_key'] as $column) {
                if (Schema::hasColumn('gateways', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
