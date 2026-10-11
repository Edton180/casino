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
            if (!Schema::hasColumn('gateways', 'asaas_credit_card_enable')) {
                $table->boolean('asaas_credit_card_enable')->default(false);
            }

            if (!Schema::hasColumn('gateways', 'mp_sandbox')) {
                $table->boolean('mp_sandbox')->default(true);
            }
            if (!Schema::hasColumn('gateways', 'mp_is_enable')) {
                $table->boolean('mp_is_enable')->default(false);
            }
            if (!Schema::hasColumn('gateways', 'mp_credit_card_enable')) {
                $table->boolean('mp_credit_card_enable')->default(false);
            }

            if (!Schema::hasColumn('gateways', 'paypal_client_id')) {
                $table->string('paypal_client_id')->nullable();
            }
            if (!Schema::hasColumn('gateways', 'paypal_client_secret')) {
                $table->string('paypal_client_secret')->nullable();
            }
            if (!Schema::hasColumn('gateways', 'paypal_sandbox')) {
                $table->boolean('paypal_sandbox')->default(true);
            }
            if (!Schema::hasColumn('gateways', 'paypal_is_enable')) {
                $table->boolean('paypal_is_enable')->default(false);
            }
            if (!Schema::hasColumn('gateways', 'paypal_credit_card_enable')) {
                $table->boolean('paypal_credit_card_enable')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            foreach ([
                'asaas_credit_card_enable',
                'mp_sandbox',
                'mp_is_enable',
                'mp_credit_card_enable',
                'paypal_client_id',
                'paypal_client_secret',
                'paypal_sandbox',
                'paypal_is_enable',
                'paypal_credit_card_enable',
            ] as $column) {
                if (Schema::hasColumn('gateways', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
