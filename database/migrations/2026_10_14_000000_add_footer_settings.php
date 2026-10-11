<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rodapé configurável no painel: direitos autorais, crédito do desenvolvedor
 * e e-mails de contato (antes fixos no código).
 */
return new class extends Migration
{
    private array $columns = [
        'footer_copyright'   => ['string', null],
        'footer_credit'      => ['string', 'Desenvolvido por Edton Braz'],
        'footer_credit_url'  => ['string', null],
        'footer_show_credit' => ['boolean', true],
        'footer_about'       => ['text', null],
        'support_email'      => ['string', null],
        'legal_email'        => ['string', null],
        'partners_email'     => ['string', null],
    ];

    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            foreach ($this->columns as $column => [$type, $default]) {
                if (!Schema::hasColumn('settings', $column)) {
                    $definition = $table->{$type}($column)->nullable();
                    if ($default !== null) {
                        $definition->default($default);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            foreach (array_keys($this->columns) as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
