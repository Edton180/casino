<?php

namespace App\Http\Controllers\Api\Games;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\OriginalRound;
use App\Models\User;
use App\Services\Games\GameWallet;
use App\Services\Games\OriginalGames;
use App\Services\Games\ProvablyFair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * API dos jogos originais da plataforma: Mines, Dice, Plinko, Crash e Double.
 * O navegador só envia a aposta e as escolhas do jogador; o resultado é
 * sempre calculado no servidor.
 */
class OriginalsController extends Controller
{
    private const PROVIDER = 'originals';

    public function __construct(private GameWallet $wallet)
    {
    }

    /* ------------------------------------------------------------------ */
    /* Informações / retomada de rodada                                     */
    /* ------------------------------------------------------------------ */

    public function info(Request $request, string $game)
    {
        $user = $this->user($request, $game);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $edge = $this->edge();
        $open = OriginalRound::where('user_id', $user->id)->where('game', $game)->where('status', 'open')->latest('id')->first();

        $data = [
            'balance'  => $this->wallet->balance($user),
            'currency' => $user->wallet?->symbol ?? 'R$',
            'min_bet'  => (float) config('originals.min_bet'),
            'max_bet'  => (float) config('originals.max_bet'),
            'max_win'  => (float) config('originals.max_win'),
            'rtp'      => round((1 - $edge) * 100, 2),
        ];

        if ($game === 'plinko') {
            $data['tables'] = array_map(fn ($risk) => OriginalGames::plinkoTable($risk, $edge), array_combine(array_keys(OriginalGames::PLINKO), array_keys(OriginalGames::PLINKO)));
        }

        if ($open && $game === 'mines') {
            $data['round'] = $this->minesPublicState($open);
        }

        if ($open && $game === 'crash') {
            $data['round'] = $this->crashResolve($open);
        }

        return response()->json($data);
    }

    /* ------------------------------------------------------------------ */
    /* Jogos instantâneos                                                    */
    /* ------------------------------------------------------------------ */

    public function dice(Request $request)
    {
        $target = round((float) $request->input('target', 50), 2);
        if ($target < 2 || $target > 98) {
            return $this->error('Escolha um alvo entre 2 e 98.');
        }
        $over = filter_var($request->input('over', true), FILTER_VALIDATE_BOOLEAN);

        return $this->instant($request, 'dice', function (ProvablyFair $pf) use ($target, $over) {
            return OriginalGames::dice($pf, $target, $over, $this->edge()) + ['target' => $target, 'over' => $over];
        });
    }

    public function double(Request $request)
    {
        $color = (string) $request->input('color');
        if (!in_array($color, ['red', 'black', 'white'], true)) {
            return $this->error('Escolha vermelho, preto ou branco.');
        }

        return $this->instant($request, 'double', fn (ProvablyFair $pf) => OriginalGames::double($pf, $color) + ['bet_color' => $color]);
    }

    public function plinko(Request $request)
    {
        $risk = (string) $request->input('risk', 'medium');
        if (!array_key_exists($risk, OriginalGames::PLINKO)) {
            return $this->error('Risco inválido.');
        }

        return $this->instant($request, 'plinko', fn (ProvablyFair $pf) => OriginalGames::plinko($pf, $risk, $this->edge()) + ['risk' => $risk]);
    }

    /* ------------------------------------------------------------------ */
    /* Mines                                                                 */
    /* ------------------------------------------------------------------ */

    public function minesStart(Request $request)
    {
        $user = $this->user($request, 'mines');
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $mines = (int) $request->input('mines', 3);
        if ($mines < 1 || $mines > 24) {
            return $this->error('Escolha entre 1 e 24 minas.');
        }

        $open = OriginalRound::where('user_id', $user->id)->where('game', 'mines')->where('status', 'open')->first();
        if ($open) {
            return response()->json(['message' => 'Você tem uma rodada em andamento.', 'round' => $this->minesPublicState($open)], 409);
        }

        $bet = $this->betAmount($request);
        if ($bet instanceof JsonResponse) {
            return $bet;
        }

        $pf = ProvablyFair::make($request->input('client_seed'));
        $round = $this->open($user, 'mines', $bet, $pf, [
            'mines'    => $mines,
            'layout'   => OriginalGames::minesLayout($pf, $mines),
            'revealed' => [],
        ]);

        if (!$round) {
            return $this->error('Saldo insuficiente.');
        }

        return response()->json(['round' => $this->minesPublicState($round), 'balance' => $this->wallet->balance($user)]);
    }

    public function minesReveal(Request $request)
    {
        $user = $this->user($request, 'mines');
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $cell = (int) $request->input('cell', -1);
        if ($cell < 0 || $cell >= OriginalGames::MINES_CELLS) {
            return $this->error('Casa inválida.');
        }

        $result = DB::transaction(function () use ($user, $request, $cell) {
            $round = $this->lockRound($user, (string) $request->input('round'), 'mines');
            if (!$round) {
                return null;
            }

            $state = $round->state;
            if (in_array($cell, $state['revealed'], true)) {
                return $round;
            }

            if (in_array($cell, $state['layout'], true)) {
                $state['hit'] = $cell;
                return $this->close($user, $round, 0.0, $state);
            }

            $state['revealed'][] = $cell;
            $safeTotal = OriginalGames::MINES_CELLS - $state['mines'];
            $multiplier = OriginalGames::minesMultiplier($state['mines'], count($state['revealed']), $this->edge());

            // revelou todas as casas seguras: recebe automaticamente
            if (count($state['revealed']) >= $safeTotal) {
                return $this->close($user, $round, $multiplier, $state);
            }

            $round->update(['state' => $state, 'multiplier' => $multiplier]);

            return $round;
        });

        if (!$result) {
            return $this->error('Rodada não encontrada ou já encerrada.', 404);
        }

        return response()->json(['round' => $this->minesPublicState($result), 'balance' => $this->wallet->balance($user)]);
    }

    public function minesCashout(Request $request)
    {
        $user = $this->user($request, 'mines');
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $result = DB::transaction(function () use ($user, $request) {
            $round = $this->lockRound($user, (string) $request->input('round'), 'mines');
            if (!$round || empty($round->state['revealed'])) {
                return null;
            }

            $multiplier = OriginalGames::minesMultiplier($round->state['mines'], count($round->state['revealed']), $this->edge());

            return $this->close($user, $round, $multiplier, $round->state);
        });

        if (!$result) {
            return $this->error('Revele pelo menos uma casa antes de retirar.');
        }

        return response()->json(['round' => $this->minesPublicState($result), 'balance' => $this->wallet->balance($user)]);
    }

    private function minesPublicState(OriginalRound $round): array
    {
        $state = $round->state;
        $finished = $round->status !== 'open';
        $edge = $this->edge();
        $safe = count($state['revealed']);

        return [
            'round'           => $round->round,
            'status'          => $round->status,
            'bet'             => $round->bet,
            'mines'           => $state['mines'],
            'revealed'        => $state['revealed'],
            'hit'             => $state['hit'] ?? null,
            'layout'          => $finished ? $state['layout'] : null,
            'multiplier'      => OriginalGames::minesMultiplier($state['mines'], $safe, $edge),
            'next_multiplier' => $safe < OriginalGames::MINES_CELLS - $state['mines']
                ? OriginalGames::minesMultiplier($state['mines'], $safe + 1, $edge)
                : null,
            'payout'          => $round->payout,
            'fairness'        => $this->fairness($round),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Crash (rodada individual)                                             */
    /* ------------------------------------------------------------------ */

    public function crashStart(Request $request)
    {
        $user = $this->user($request, 'crash');
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $open = OriginalRound::where('user_id', $user->id)->where('game', 'crash')->where('status', 'open')->first();
        if ($open && $this->crashResolve($open)['status'] === 'open') {
            return response()->json(['message' => 'Aguarde a rodada atual terminar.', 'round' => $this->crashResolve($open)], 409);
        }

        $auto = $request->filled('auto_cashout') ? round((float) $request->input('auto_cashout'), 2) : null;
        if ($auto !== null && ($auto < 1.01 || $auto > OriginalGames::CRASH_MAX)) {
            return $this->error('Auto retirada deve ser entre 1.01x e ' . OriginalGames::CRASH_MAX . 'x.');
        }

        $bet = $this->betAmount($request);
        if ($bet instanceof JsonResponse) {
            return $bet;
        }

        $pf = ProvablyFair::make($request->input('client_seed'));
        $round = $this->open($user, 'crash', $bet, $pf, [
            'point'        => OriginalGames::crashPoint($pf, $this->edge()),
            'started_at'   => $this->nowMs(),
            'auto_cashout' => $auto,
        ]);

        if (!$round) {
            return $this->error('Saldo insuficiente.');
        }

        return response()->json(['round' => $this->crashPublicState($round), 'balance' => $this->wallet->balance($user)]);
    }

    public function crashStatus(Request $request)
    {
        $user = $this->user($request, 'crash');
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $round = OriginalRound::where('user_id', $user->id)->where('game', 'crash')->where('round', (string) $request->input('round'))->first();
        if (!$round) {
            return $this->error('Rodada não encontrada.', 404);
        }

        return response()->json(['round' => $this->crashResolve($round), 'balance' => $this->wallet->balance($user)]);
    }

    public function crashCashout(Request $request)
    {
        $user = $this->user($request, 'crash');
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $round = DB::transaction(function () use ($user, $request) {
            $round = OriginalRound::where('user_id', $user->id)->where('game', 'crash')
                ->where('round', (string) $request->input('round'))->lockForUpdate()->first();
            if (!$round || $round->status !== 'open') {
                return $round;
            }

            $state = $round->state;
            $elapsed = $this->nowMs() - $state['started_at'];
            $auto = $state['auto_cashout'];

            // a auto retirada pode ter acontecido antes do clique
            if ($auto !== null && $auto <= $state['point'] && $elapsed >= OriginalGames::crashTimeFor($auto)) {
                return $this->close($user, $round, $auto, $state);
            }

            if ($elapsed >= OriginalGames::crashTimeFor($state['point'])) {
                return $this->close($user, $round, 0.0, $state);
            }

            $multiplier = min(OriginalGames::crashMultiplierAt($elapsed), $state['point']);
            $state['cashed_at'] = $multiplier;

            return $this->close($user, $round, $multiplier, $state);
        });

        if (!$round) {
            return $this->error('Rodada não encontrada.', 404);
        }

        return response()->json(['round' => $this->crashPublicState($round), 'balance' => $this->wallet->balance($user)]);
    }

    /**
     * Encerra a rodada se o tempo já passou do ponto de quebra (ou da auto retirada).
     */
    private function crashResolve(OriginalRound $round): array
    {
        if ($round->status === 'open') {
            $round = DB::transaction(function () use ($round) {
                $locked = OriginalRound::whereKey($round->id)->lockForUpdate()->first();
                if ($locked->status !== 'open') {
                    return $locked;
                }

                $state = $locked->state;
                $elapsed = $this->nowMs() - $state['started_at'];
                $auto = $state['auto_cashout'];
                $user = User::find($locked->user_id);

                if ($auto !== null && $auto <= $state['point'] && $elapsed >= OriginalGames::crashTimeFor($auto)) {
                    return $this->close($user, $locked, $auto, $state);
                }

                if ($elapsed >= OriginalGames::crashTimeFor($state['point'])) {
                    return $this->close($user, $locked, 0.0, $state);
                }

                return $locked;
            });
        }

        return $this->crashPublicState($round);
    }

    private function crashPublicState(OriginalRound $round): array
    {
        $state = $round->state;
        $finished = $round->status !== 'open';
        $elapsed = $this->nowMs() - $state['started_at'];

        return [
            'round'        => $round->round,
            'status'       => $round->status,
            'bet'          => $round->bet,
            'started_at'   => $state['started_at'],
            'server_now'   => $this->nowMs(),
            'elapsed'      => $elapsed,
            'auto_cashout' => $state['auto_cashout'],
            'multiplier'   => $finished ? $round->multiplier : OriginalGames::crashMultiplierAt($elapsed),
            'crash_point'  => $finished ? $state['point'] : null,
            'payout'       => $round->payout,
            'fairness'     => $this->fairness($round),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Infraestrutura comum                                                  */
    /* ------------------------------------------------------------------ */

    private function instant(Request $request, string $game, callable $play)
    {
        $user = $this->user($request, $game);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $bet = $this->betAmount($request);
        if ($bet instanceof JsonResponse) {
            return $bet;
        }

        $pf = ProvablyFair::make($request->input('client_seed'));

        $round = DB::transaction(function () use ($user, $game, $bet, $pf, $play) {
            $result = $play($pf);
            $round = $this->open($user, $game, $bet, $pf, $result, false);
            if (!$round) {
                return null;
            }

            return $this->close($user, $round, (float) $result['multiplier'], $result);
        });

        if (!$round) {
            return $this->error('Saldo insuficiente.');
        }

        return response()->json([
            'result'   => $round->state,
            'bet'      => $round->bet,
            'payout'   => $round->payout,
            'won'      => $round->status === 'won',
            'balance'  => $this->wallet->balance($user),
            'fairness' => $this->fairness($round),
        ]);
    }

    /**
     * Debita a aposta e cria a rodada.
     */
    private function open(User $user, string $game, float $bet, ProvablyFair $pf, array $state, bool $ownTransaction = true): ?OriginalRound
    {
        $create = function () use ($user, $game, $bet, $pf, $state) {
            $roundId = 'org_' . Str::lower(Str::random(32));
            $changeBonus = $this->wallet->debit($user, $bet, $game, $roundId, self::PROVIDER);
            if ($changeBonus === null) {
                return null;
            }

            return OriginalRound::create([
                'round'            => $roundId,
                'user_id'          => $user->id,
                'game'             => $game,
                'bet'              => $bet,
                'status'           => 'open',
                'change_bonus'     => $changeBonus,
                'server_seed'      => $pf->serverSeed,
                'server_seed_hash' => $pf->hash(),
                'client_seed'      => $pf->clientSeed,
                'nonce'            => $pf->nonce,
                'state'            => $state,
            ]);
        };

        return $ownTransaction ? DB::transaction($create) : $create();
    }

    /**
     * Fecha a rodada e paga o prêmio (multiplicador 0 = derrota).
     */
    private function close(User $user, OriginalRound $round, float $multiplier, array $state): OriginalRound
    {
        $payout = $multiplier > 0
            ? min(round($round->bet * $multiplier, 2), (float) config('originals.max_win'))
            : 0.0;

        $this->wallet->settle($user, $round->round, $round->bet, $payout, $round->change_bonus);

        $round->update([
            'state'      => $state,
            'multiplier' => $multiplier,
            'payout'     => $payout,
            'status'     => $payout > 0 ? 'won' : 'lost',
            'settled_at' => now(),
        ]);

        return $round;
    }

    private function lockRound(User $user, string $roundId, string $game): ?OriginalRound
    {
        return OriginalRound::where('user_id', $user->id)->where('game', $game)
            ->where('round', $roundId)->where('status', 'open')->lockForUpdate()->first();
    }

    private function fairness(OriginalRound $round): array
    {
        return [
            'server_seed_hash' => $round->server_seed_hash,
            'server_seed'      => $round->status === 'open' ? null : $round->server_seed,
            'client_seed'      => $round->client_seed,
            'nonce'            => $round->nonce,
        ];
    }

    /**
     * Usuário a partir do token de lançamento do jogo (vem na URL do iframe).
     */
    private function user(Request $request, string $game): User|JsonResponse
    {
        $token = \Helper::DecToken((string) $request->input('token'));
        if (!is_array($token) || empty($token['status']) || empty($token['id']) || ($token['game'] ?? null) !== $game) {
            return $this->error('Sessão expirada. Abra o jogo novamente.', 401);
        }

        $enabled = Game::where('game_code', $game)->where('distribution', 'source')->where('status', 1)->exists();
        $user = User::find($token['id']);

        if (!$user || !$enabled) {
            return $this->error('Jogo indisponível.', 404);
        }

        if ($user->banned ?? false) {
            return $this->error('Conta bloqueada.', 403);
        }

        return $user;
    }

    private function betAmount(Request $request): float|JsonResponse
    {
        $bet = round((float) \Helper::amountPrepare((string) $request->input('bet')), 2);
        $min = (float) config('originals.min_bet');
        $max = (float) config('originals.max_bet');

        if ($bet < $min || $bet > $max) {
            return $this->error(sprintf('A aposta deve ser entre %s e %s.', number_format($min, 2, ',', '.'), number_format($max, 2, ',', '.')));
        }

        return $bet;
    }

    private function edge(): float
    {
        return max(0.0, min(0.5, (float) config('originals.house_edge')));
    }

    private function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
