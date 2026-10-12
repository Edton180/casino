<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Link de suporte configurável no painel (WhatsApp, Telegram, chat...): antes a página
 *   de suporte apontava fixo para o chat de outro site.
 * - Corrige textos gravados com acentuação quebrada no banco inicial (ex.: "bÃ´nus").
 * - Jogos locais que usavam como capa o ícone padrão do Construct 3 (um "C" cinza) passam a usar
 *   a capa do próprio jogo (public/originals/<jogo>/cover.jpg), sem mexer em capas enviadas no painel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('settings', 'support_url')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('support_url')->nullable();
            });
        }

        foreach (DB::table('settings')->get(['id', 'software_description', 'software_description2']) as $row) {
            $changes = [];
            foreach (['software_description', 'software_description2'] as $column) {
                $fixed = $this->fixDoubleEncoding($row->{$column});
                if ($fixed !== $row->{$column}) {
                    $changes[$column] = $fixed;
                }
            }
            if ($changes) {
                DB::table('settings')->where('id', $row->id)->update($changes);
            }
        }

        foreach (DB::table('games')->where('distribution', 'source')->where('cover', 'like', '/originals/%/icons/icon-512.png')->get(['id', 'game_code', 'cover']) as $game) {
            $cover = '/originals/' . $game->game_code . '/cover.jpg';
            if ($game->cover === '/originals/' . $game->game_code . '/icons/icon-512.png' && is_file(public_path(ltrim($cover, '/')))) {
                DB::table('games')->where('id', $game->id)->update(['cover' => $cover]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('settings', 'support_url')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('support_url');
            });
        }
    }

    /** "bÃ´nus" → "bônus": texto UTF-8 que foi codificado duas vezes. */
    private function fixDoubleEncoding(?string $text): ?string
    {
        // só textos com o padrão de dupla codificação e sem caracteres fora do latin-1 (emoji etc.)
        if ($text === null || !preg_match('/[ÃÂ][\x{80}-\x{BF}]/u', $text) || preg_match('/[^\x{00}-\x{FF}]/u', $text)) {
            return $text;
        }
        $candidate = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

        return mb_check_encoding($candidate, 'UTF-8') ? $candidate : $text;
    }
};
