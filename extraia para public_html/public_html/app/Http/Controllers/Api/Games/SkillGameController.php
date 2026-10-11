<?php

namespace App\Http\Controllers\Api\Games;

use App\Helpers\Core as Helper;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Jogos de habilidade locais (public/originals: candy, subway, dino, fruitninja).
 *
 * Substitui os scripts PHP soltos (processar_aposta.php, win.php, loss.php,
 * update.php, loseGame.php), que:
 *  - debitavam a carteira de qualquer usuário a partir de um "user_id" vindo do navegador;
 *  - ignoravam o saldo de depósito ("balance") e truncavam centavos (PDO::PARAM_INT);
 *  - nunca creditavam o prêmio (chamavam rotas/tabelas que não existem);
 *  - não geravam histórico nem comissão de afiliado.
 *
 * Fluxo: start (debita a aposta e abre uma rodada) -> finish (fecha a rodada uma única vez).
 */
class SkillGameController extends Controller
{
    /** Multiplicador máximo do prêmio sobre a aposta (meta de cada jogo). */
    private const GAMES = [
        'candy'      => 3,
        'subway'     => 5,
        'dino'       => 5,
        'fruitninja' => 2,
    ];

    private const MIN_BET = 1;
    private const MAX_BET = 25;
    private const ROUND_TTL_MINUTES = 180;

    public function start(Request $request)
    {
        $tokenOpen = \Helper::DecToken((string) $request->input('token'));
        if (!is_array($tokenOpen) || empty($tokenOpen['status']) || empty($tokenOpen['id'])) {
            return response()->json(['message' => 'Sessão expirada. Abra o jogo novamente.'], 401);
        }

        $gameCode = $tokenOpen['game'] ?? null;
        $user = User::find($tokenOpen['id']);
        $game = Game::whereStatus(1)->where('game_code', $gameCode)->first();

        if (!$user || !$game || !array_key_exists($gameCode, self::GAMES)) {
            return response()->json(['message' => 'Jogo indisponível.'], 404);
        }

        $bet = round((float) Helper::amountPrepare((string) $request->input('bet')), 2);
        if ($bet < self::MIN_BET || $bet > self::MAX_BET) {
            return response()->json(['message' => 'A aposta deve ser entre R$ ' . self::MIN_BET . ' e R$ ' . self::MAX_BET . '.'], 400);
        }

        $round = 'skl_' . Str::lower(Str::random(40));

        $changeBonus = DB::transaction(function () use ($user, $game, $bet, $round) {
            $wallet = $this->wallet($user->id);
            if (!$wallet) {
                return null;
            }

            if ($user->is_demo_agent) {
                if ((float) $wallet->balance_demo < $bet) {
                    return null;
                }
                $wallet->decrement('balance_demo', $bet);
                return 'balance_demo';
            }

            $changeBonus = Helper::DiscountBalance($wallet, $bet);
            if ($changeBonus === 'no_balance') {
                return null;
            }

            Order::create([
                'user_id'        => $user->id,
                'session_id'     => $round,
                'transaction_id' => $round,
                'game'           => $game->game_code,
                'game_uuid'      => $game->game_code,
                'type'           => 'bet',
                'type_money'     => $changeBonus,
                'amount'         => $bet,
                'providers'      => 'skill',
                'refunded'       => false,
                'round_id'       => $round,
                'status'         => 0,
            ]);

            return $changeBonus;
        });

        if ($changeBonus === null) {
            return response()->json(['message' => 'Saldo insuficiente.'], 400);
        }

        Cache::put($this->roundKey($round), [
            'user_id'      => $user->id,
            'game'         => $game->game_code,
            'bet'          => $bet,
            'change_bonus' => $changeBonus,
            'demo'         => (bool) $user->is_demo_agent,
        ], now()->addMinutes(self::ROUND_TTL_MINUTES));

        return response()->json([
            'round'   => $round,
            'bet'     => $bet,
            'max_win' => round($bet * self::GAMES[$game->game_code], 2),
            'is_demo' => (bool) $user->is_demo_agent,
            'balance' => $this->balance($user),
        ]);
    }

    public function finish(Request $request)
    {
        $round = (string) $request->input('round');
        if (!str_starts_with($round, 'skl_')) {
            return response()->json(['message' => 'Rodada inválida.'], 400);
        }

        $lock = Cache::lock('skill_finish_' . $round, 10);
        if (!$lock->get()) {
            return response()->json(['message' => 'Rodada em processamento.'], 409);
        }

        try {
            $data = Cache::pull($this->roundKey($round));
            if (!$data) {
                return response()->json(['message' => 'Rodada já encerrada ou expirada.'], 410);
            }

            $user = User::find($data['user_id']);
            $maxWin = round($data['bet'] * self::GAMES[$data['game']], 2);
            $win = round((float) Helper::amountPrepare((string) $request->input('value', 0)), 2);
            $win = max(0, min($win, $maxWin)); // o valor vem do navegador: limita à meta do jogo

            DB::transaction(function () use ($user, $data, $win, $round) {
                if ($data['demo']) {
                    if ($win > 0) {
                        $this->wallet($user->id)?->increment('balance_demo', $win);
                    }
                    return;
                }

                // 'bet' paga o revshare do afiliado e avança o rollover; 'win' paga o prêmio
                Helper::generateGameHistory(
                    $user->id,
                    $win > 0 ? 'win' : 'bet',
                    $win,
                    $data['bet'],
                    $data['change_bonus'],
                    $round
                );

                // generateGameHistory só fecha a ordem de quem tem indicador
                Order::where('transaction_id', $round)->where('status', 0)->update(
                    $win > 0 ? ['status' => 1, 'type' => 'win', 'amount' => $win] : ['status' => 1]
                );
            });

            return response()->json([
                'win'     => $win,
                'balance' => $this->balance($user),
            ]);
        } finally {
            $lock->release();
        }
    }

    private function roundKey(string $round): string
    {
        return 'skill_round_' . $round;
    }

    private function wallet(int $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->where('active', 1)->lockForUpdate()->first()
            ?? Wallet::where('user_id', $userId)->lockForUpdate()->first();
    }

    private function balance(User $user): float
    {
        $wallet = Wallet::where('user_id', $user->id)->where('active', 1)->first()
            ?? Wallet::where('user_id', $user->id)->first();

        return (float) ($user->is_demo_agent ? $wallet?->balance_demo : $wallet?->total_balance);
    }
}
