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
            if (!Schema::hasColumn('gateways', 'mp_client_id')) {
                $table->string('mp_client_id')->nullable();
            }
            if (!Schema::hasColumn('gateways', 'mp_client_secret')) {
                $table->string('mp_client_secret')->nullable();
            }
            if (!Schema::hasColumn('gateways', 'mp_public_key')) {
                $table->string('mp_public_key')->nullable();
            }
            if (!Schema::hasColumn('gateways', 'mp_access_token')) {
                $table->string('mp_access_token')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            foreach (['mp_client_id', 'mp_client_secret', 'mp_public_key', 'mp_access_token'] as $column) {
                if (Schema::hasColumn('gateways', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
