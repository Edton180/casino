<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Saques antigos foram gravados sem moeda, o que quebrava as páginas
 * Carteira e Transações do jogador. Usa a moeda da carteira (ou BRL).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('withdrawals') || !Schema::hasColumn('withdrawals', 'currency')) {
            return;
        }

        DB::table('withdrawals')->whereNull('currency')->orWhere('currency', '')->orderBy('id')->each(function ($withdrawal) {
            $wallet = DB::table('wallets')->where('user_id', $withdrawal->user_id)->first(['currency', 'symbol']);
            DB::table('withdrawals')->where('id', $withdrawal->id)->update([
                'currency' => $wallet->currency ?? 'BRL',
                'symbol'   => $withdrawal->symbol ?: ($wallet->symbol ?? 'R$'),
            ]);
        });
    }

    public function down(): void
    {
    }
};
