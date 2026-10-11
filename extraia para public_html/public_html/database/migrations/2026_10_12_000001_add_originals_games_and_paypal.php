<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Tabela de rodadas dos jogos originais (Mines, Dice, Plinko, Crash, Double)
 * - Cadastro do provedor "Originais" e dos jogos (distribuição "source")
 * - Colunas do PayPal na tabela de gateways
 * - Capas locais para os jogos "source" que já existiam
 */
return new class extends Migration
{
    private array $games = [
        ['mines',  'Mines',  'Encontre os diamantes e fuja das minas. Retire quando quiser.'],
        ['crash',  'Crash',  'O multiplicador sobe até quebrar. Retire antes!'],
        ['dice',   'Dice',   'Escolha o alvo e a chance: role acima ou abaixo.'],
        ['plinko', 'Plinko', 'Solte a bola e veja onde ela cai.'],
        ['double', 'Double', 'Vermelho, preto ou branco 14x.'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('original_rounds')) {
            Schema::create('original_rounds', function (Blueprint $table) {
                $table->id();
                $table->string('round', 64)->unique();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('game', 20)->index();
                $table->decimal('bet', 20, 2);
                $table->decimal('multiplier', 12, 4)->default(0);
                $table->decimal('payout', 20, 2)->default(0);
                $table->string('status', 10)->default('open')->index();
                $table->string('change_bonus', 40)->nullable();
                $table->string('server_seed', 64);
                $table->string('server_seed_hash', 64);
                $table->string('client_seed', 64);
                $table->unsignedBigInteger('nonce')->default(0);
                $table->json('state')->nullable();
                $table->timestamp('settled_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('gateways', function (Blueprint $table) {
            foreach ([
                'paypal_is_enable'     => ['boolean', false],
                'paypal_sandbox'       => ['boolean', true],
                'paypal_client_id'     => ['string', null],
                'paypal_client_secret' => ['string', null],
                'paypal_webhook_key'   => ['string', null],
            ] as $column => [$type, $default]) {
                if (!Schema::hasColumn('gateways', $column)) {
                    $definition = $table->{$type}($column)->nullable();
                    if ($default !== null) {
                        $definition->default($default);
                    }
                }
            }
        });

        if (!Schema::hasTable('games') || !Schema::hasTable('providers')) {
            return;
        }

        $now = now();

        $providerId = DB::table('providers')->where('code', 'ORIGINALS')->value('id');
        if (!$providerId) {
            $providerId = ((int) DB::table('providers')->max('id')) + 1;
            DB::table('providers')->insert([
                'id'           => $providerId,
                'cover'        => '/originals/_shared/provider.svg',
                'code'         => 'ORIGINALS',
                'name'         => 'Originais',
                'status'       => 1,
                'rtp'          => 97,
                'views'        => 1,
                'distribution' => 'source',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        $categoryId = null;
        if (Schema::hasTable('categories')) {
            $categoryId = DB::table('categories')->where('slug', 'originais')->value('id')
                ?? DB::table('categories')->insertGetId([
                    'name' => 'Originais', 'description' => 'Jogos originais da casa', 'slug' => 'originais',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
        }

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

        // Jogos locais antigos: usa a capa que já vem em public/ quando o arquivo enviado não existe
        foreach (DB::table('games')->where('distribution', 'source')->get(['id', 'game_code', 'cover']) as $game) {
            $local = public_path($game->game_code . '.png');
            $uploaded = $game->cover && !str_starts_with($game->cover, '/') ? storage_path('app/public/' . $game->cover) : null;

            if (is_file($local) && $uploaded && !is_file($uploaded)) {
                DB::table('games')->where('id', $game->id)->update(['cover' => '/' . $game->game_code . '.png']);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('original_rounds');

        if (Schema::hasTable('games')) {
            $ids = DB::table('games')->where('game_type', 'originals')->pluck('id');
            DB::table('category_game')->whereIn('game_id', $ids)->delete();
            DB::table('games')->whereIn('id', $ids)->delete();
        }
    }
};
