<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * O dump original criava quase todas as tabelas (wallets, transactions,
 * orders, deposits, withdrawals...) como MyISAM. MyISAM ignora transações e
 * "SELECT ... FOR UPDATE", então dois webhooks/giros simultâneos podiam
 * creditar ou debitar o saldo em dobro. Converte tudo para InnoDB.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $tables = DB::select(
            "SELECT table_name AS name FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' AND engine = 'MyISAM'"
        );

        foreach ($tables as $table) {
            $name = str_replace('`', '', $table->name);

            try {
                // ROW_FORMAT=DYNAMIC evita erro com tabelas criadas como ROW_FORMAT=FIXED/COMPACT
                DB::statement("ALTER TABLE `{$name}` ENGINE=InnoDB ROW_FORMAT=DYNAMIC");
            } catch (\Throwable $e) {
                Log::warning("Não foi possível converter {$name} para InnoDB", ['error' => $e->getMessage()]);
            }
        }
    }

    public function down(): void
    {
        // não volta para MyISAM
    }
};
