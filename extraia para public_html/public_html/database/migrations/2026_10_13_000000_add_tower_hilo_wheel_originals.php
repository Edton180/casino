<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Novos jogos originais (mecânicas inspiradas no Luck2x): Tower, HiLo e Wheel.
 */
return new class extends Migration
{
    private array $games = [
        ['tower', 'Tower', 'Suba os 10 andares escolhendo portas sem bomba. Retire quando quiser.'],
        ['hilo',  'HiLo',  'A próxima carta é maior ou menor? Acumule o multiplicador.'],
        ['wheel', 'Wheel', 'Gire a roda: preto 2x, vermelho 3x, verde 5x, amarelo 50x.'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('games') || !Schema::hasTable('providers')) {
            return;
        }

        $providerId = DB::table('providers')->where('code', 'ORIGINALS')->value('id');
        if (!$providerId) {
            return; // criado pela migration anterior
        }

        $now = now();
        $categoryId = Schema::hasTable('categories') ? DB::table('categories')->where('slug', 'originais')->value('id') : null;

        foreach ($this->games as [$code, $name, $description]) {
            $gameId = DB::table('games')->where('game_code', $code)->where('distribution', 'source')->value('id');

            if (!$gameId) {
                $gameId = DB::table('games')->insertGetId([
                    'provider_id'   => $providerId,
                    'game_id'       => $code,
                    'game_name'     => $name,
                    'game_code'     => $code,
                    'game_type'     => 'originals',
                    'description'   => $description,
                    'cover'         => "/originals/{$code}/cover.svg",
                    'status'        => '1',
                    'technology'    => 'html5',
                    'has_lobby'     => 0,
                    'is_mobile'     => 1,
                    'has_freespins' => 0,
                    'has_tables'    => 0,
                    'only_demo'     => 0,
                    'rtp'           => 97,
                    'distribution'  => 'source',
                    'views'         => 0,
                    'is_featured'   => 1,
                    'show_home'     => 1,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }

            if ($categoryId && !DB::table('category_game')->where('category_id', $categoryId)->where('game_id', $gameId)->exists()) {
                DB::table('category_game')->insert(['category_id' => $categoryId, 'game_id' => $gameId]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('games')->whereIn('game_code', ['tower', 'hilo', 'wheel'])->where('distribution', 'source')->pluck('id');
        DB::table('category_game')->whereIn('game_id', $ids)->delete();
        DB::table('games')->whereIn('id', $ids)->delete();
    }
};
