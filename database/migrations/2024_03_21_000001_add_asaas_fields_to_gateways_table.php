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
            $table->boolean('asaas_sandbox')->default(true)->after('asaas_api_key');
            $table->string('asaas_webhook_key')->nullable()->after('asaas_sandbox');
            $table->boolean('asaas_is_enable')->default(false)->after('asaas_webhook_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn(['asaas_sandbox', 'asaas_webhook_key', 'asaas_is_enable']);
        });
    }
}; 