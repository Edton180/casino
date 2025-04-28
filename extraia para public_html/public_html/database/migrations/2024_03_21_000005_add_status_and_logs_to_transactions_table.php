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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('payment_method');
            $table->text('gateway_response')->nullable()->after('gateway');
            $table->text('gateway_error')->nullable()->after('gateway_response');
            $table->timestamp('paid_at')->nullable()->after('gateway_error');
            $table->timestamp('expired_at')->nullable()->after('paid_at');
            $table->timestamp('canceled_at')->nullable()->after('expired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'gateway',
                'gateway_response',
                'gateway_error',
                'paid_at',
                'expired_at',
                'canceled_at'
            ]);
        });
    }
}; 