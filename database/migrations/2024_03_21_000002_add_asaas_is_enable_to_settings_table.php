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
        if (!Schema::hasColumn('settings', 'asaas_is_enable')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean('asaas_is_enable')->default(false)->after('bspay_is_enable');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('settings', 'asaas_is_enable')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('asaas_is_enable');
            });
        }
    }
};
