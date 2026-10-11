<?php

namespace App\Traits\Providers;

use App\Helpers\Core as Helper;
use App\Models\Game;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\Missions\MissionTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Motor dos jogos locais (distribuição "source": Fortune Tiger, Fortune OX,
 * Fortune Mouse, Queen of Bounty, Treasures of Aztec, etc.).
 *
 * Cada resultado pré-gerado tem o formato:
 *   [0 => SlotIcons, 1 => ActiveIcons, 2 => ActiveLines, 3 => DropLineData, 4 => MultiplyCount, 5 => Payout]
 */
trait PrivateGamesTrait
{
    use MissionTrait;

    /**
     * @param string $token
     * @param array $settingGame
     * @param array $iconData
     * @param array $activeLines
     * @param array $dropLine
     * @param array $betSizeList
     * @param array $multipleList
     * @param array $feature
     * @param array $featureResult
     * @return JsonResponse
     */
    public static function SessionStructure(string $token, array $settingGame, array $iconData, array $activeLines, array $dropLine, array $betSizeList, array $multipleList, array $feature, array $featureResult = [])
    {
        $session = self::openGameSession($token);
        if ($session instanceof JsonResponse) {
            return $session;
        }

        [$user, , $wallet] = $session;

        $data                       = new \stdClass();
        $data->user_name            = $user->name;
        $data->credit               = self::playerCredit($user, $wallet);
        $data->num_line             = $settingGame['num_line'];
        $data->line_num             = $settingGame['line_num'];
        $data->bet_amount           = $settingGame['bet_amount'];
        $data->free_num             = $settingGame['free_num'];
        $data->free_total           = $settingGame['free_total'];
        $data->free_amount          = $settingGame['free_amount'];
        $data->free_multi           = $settingGame['free_multi'];
        $data->freespin_mode        = $settingGame['freespin_mode'];
        $data->multiple_list        = $multipleList;
        $data->credit_line          = $settingGame['credit_line'];
        $data->buy_feature          = $settingGame['buy_feature'];
        $data->buy_max              = $settingGame['buy_max'];
        $data->feature              = $feature;
        $data->total_way            = $settingGame['total_way'];
        $data->multiply             = $settingGame['multiply'];
        $data->icon_data            = $iconData;
        $data->active_lines         = $activeLines;
        $data->drop_line            = $dropLine;
        $data->currency_prefix      = $wallet->symbol;
        $data->currency_suffix      = "";
        $data->currency_thousand    = ".";
        $data->currency_decimal     = ",";
        $data->bet_size_list        = $betSizeList;
        $data->previous_session     = $settingGame['previous_session'];
        $data->game_state           = $settingGame['game_state'];
        $data->feature_result       = $featureResult;

        return response()->json([
            "data" => $data,
            "success" => true,
            "message" => "Session success"
        ]);
    }

    /**
     * @param string $token
     * @param array $settingGame  cpl e betamount (enviados pelo jogo) e lines (fixo do jogo)
     * @param array $pull
     * @param array $dataLose
     * @param array $dataDemo
     * @param array $dataWin
     * @param array $dataBonus
     * @return JsonResponse
     */
    public static function SpinStructure(string $token, array $settingGame, array $pull, array $dataLose, array $dataDemo, array $dataWin, array $dataBonus = [])
    {
        $session = self::openGameSession($token);
        if ($session instanceof JsonResponse) {
            return $session;
        }

        [$user, $game, $wallet] = $session;

        $cpl     = intval($settingGame['cpl']);
        $amount  = round(floatval($settingGame['betamount']), 2);

        // O número de linhas é fixo por jogo. Antes era usado o valor enviado
        // pelo navegador: com numline=1 a aposta caía para 1/5 e o prêmio
        // continuava calculado sobre todas as linhas.
        $numline = intval($settingGame['lines'] ?? $settingGame['num_line']);

        if ($cpl < 1 || $cpl > 100 || $amount <= 0 || $numline < 1) {
            return response()->json(['success' => false, 'message' => 'Aposta inválida'], 400);
        }

        $bet = round($amount * $cpl * $numline, 2);

        self::CheckMissionExist($user->id, $game);

        $result = self::pickResult($user, $game, $dataLose, $dataDemo, $dataWin);
        if ($result === null) {
            Log::error('[source] Jogo sem resultados configurados', ['game' => $game->game_code]);
            return response()->json(['success' => false, 'message' => 'Jogo indisponível'], 500);
        }

        // payout_base: quantidade de linhas para a qual a tabela de resultados
        // foi gerada (ex.: Treasures of Aztec usa os dados de 20 linhas do Queen of Bounty)
        $payoutScale = $numline / max(1, intval($settingGame['payout_base'] ?? $numline));
        $winAmount = round($cpl * $amount * self::resultPayout($result) * $payoutScale, 2);

        $response = DB::transaction(function () use ($user, $game, $bet, $winAmount) {
            $wallet = Wallet::where('user_id', $user->id)->where('active', 1)->lockForUpdate()->first()
                ?? Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if ($user->is_demo_agent) {
                // agente demo joga apenas com o saldo demo
                if ((float) $wallet->balance_demo < $bet) {
                    return null;
                }

                $wallet->decrement('balance_demo', $bet);
                if ($winAmount > 0) {
                    $wallet->increment('balance_demo', $winAmount);
                }

                return true;
            }

            $changeBonus = Helper::DiscountBalance($wallet, $bet);
            if ($changeBonus === 'no_balance') {
                return null;
            }

            $txId = 'src_' . Str::lower(Str::random(24));

            Order::create([
                'user_id'        => $user->id,
                'session_id'     => $txId,
                'transaction_id' => $txId,
                'game'           => $game->game_code,
                'game_uuid'      => $game->game_code,
                'type'           => 'bet',
                'type_money'     => $changeBonus,
                'amount'         => $bet,
                'providers'      => 'source',
                'refunded'       => false,
                'round_id'       => $txId,
                'status'         => 0,
            ]);

            // 'bet' paga revshare do afiliado e avança o rollover; 'win' paga o prêmio
            Helper::generateGameHistory($user->id, $winAmount > 0 ? 'win' : 'bet', $winAmount, $bet, $changeBonus, $txId);

            // generateGameHistory só fecha a ordem de quem tem indicador
            Order::where('transaction_id', $txId)->where('status', 0)->update(
                $winAmount > 0 ? ['status' => 1, 'type' => 'win', 'amount' => $winAmount] : ['status' => 1]
            );

            return true;
        });

        if ($response === null) {
            return response()->json(['success' => false, 'message' => 'Saldo insuficiente'], 400);
        }

        $activeLines = $result[2] ?? [];
        if ($winAmount > 0 && !empty($activeLines)) {
            $activeLines[0]['win_amount'] = $winAmount;
        }

        $pull['WinAmount']    = $winAmount;
        $pull['WinOnDrop']    = $winAmount;
        $pull['SlotIcons']    = $result[0] ?? [];
        $pull['ActiveIcons']  = $result[1] ?? [];
        $pull['ActiveLines']  = $activeLines;
        $pull['DropLineData'] = $result[3] ?? [];

        $wallet = Wallet::find($wallet->id);

        return response()->json([
            "data" => [
                "credit"     => self::playerCredit($user, $wallet), // saldo já com o prêmio
                "freemode"   => $settingGame['freemode'] ?? false,
                "jackpot"    => $settingGame['jackpot'] ?? 0,
                "free_spin"  => $settingGame['free_spin'] ?? 0,
                "free_num"   => $settingGame['free_num'] ?? 0,
                "scaler"     => $settingGame['scaler'] ?? 0,
                "num_line"   => $numline,
                "cpl"        => $cpl,
                "betamount"  => $amount,
                "bet_amount" => $bet,
                "pull"       => $pull,
            ],
            "success" => true,
            "message" => "Spin success"
        ]);
    }

    /**
     * Seleção de rodadas grátis (tela "comprar recurso"). Apenas informativo.
     *
     * @param $request
     * @param string $token
     * @param array $freeSpin  índice => quantidade de rodadas
     * @return JsonResponse
     */
    public static function FreeNumStructure($request, $token, array $freeSpin = [], array $multiples = [])
    {
        $session = self::openGameSession($token);
        if ($session instanceof JsonResponse) {
            return $session;
        }

        $index = intval($request->index ?? 0);
        if (!isset($freeSpin[$index])) {
            return response()->json([
                "success" => false,
                "data"    => ["free_num" => 0],
                "message" => "Recurso indisponível"
            ]);
        }

        return response()->json([
            "success" => true,
            "data" => [
                "free_num" => $freeSpin[$index],
                "multiple" => $multiples[$index] ?? 0,
            ],
            "message" => "Change success"
        ]);
    }

    /**
     * Valida o token e carrega usuário, jogo e carteira.
     * @return array{0: User, 1: Game, 2: Wallet}|JsonResponse
     */
    private static function openGameSession(string $token): array|JsonResponse
    {
        $tokenOpen = \Helper::DecToken($token);
        if (!is_array($tokenOpen) || empty($tokenOpen['status']) || empty($tokenOpen['id'])) {
            return response()->json(['success' => false, 'message' => 'Token inválido'], 401);
        }

        $user = User::find($tokenOpen['id']);
        $game = Game::whereStatus(1)->where('game_code', $tokenOpen['game'] ?? null)->first();
        if (!$user || !$game) {
            return response()->json(['success' => false, 'message' => 'Sessão inválida'], 404);
        }

        $wallet = Wallet::where('user_id', $user->id)->where('active', 1)->first()
            ?? Wallet::where('user_id', $user->id)->first();
        if (!$wallet) {
            return response()->json(['success' => false, 'message' => 'Carteira não encontrada'], 404);
        }

        return [$user, $game, $wallet];
    }

    private static function playerCredit(User $user, Wallet $wallet): float
    {
        return (float) ($user->is_demo_agent ? $wallet->balance_demo : $wallet->total_balance);
    }

    /**
     * Sorteia o resultado. O campo "rtp" do jogo é a chance (%) de vitória.
     */
    private static function pickResult(User $user, Game $game, array $dataLose, array $dataDemo, array $dataWin): ?array
    {
        $winPool  = $user->is_demo_agent ? array_merge($dataWin, $dataDemo) : $dataWin;
        $losePool = $dataLose;

        $winChance = $user->is_demo_agent ? 90 : max(0, min(100, intval($game->rtp)));
        $wantsWin  = random_int(1, 100) <= $winChance;

        $pool = ($wantsWin && !empty($winPool)) || empty($losePool) ? $winPool : $losePool;
        if (empty($pool)) {
            return null;
        }

        return array_values($pool)[random_int(0, count($pool) - 1)];
    }

    /**
     * Multiplicador do resultado. Alguns arquivos (Queen of Bounty, Treasures
     * of Aztec) não trazem o payout total no índice 5 (null), o que fazia
     * todo "ganho" pagar zero; nesse caso ele é somado das linhas vencedoras
     * e das cascatas.
     */
    private static function resultPayout(array $result): float
    {
        if (isset($result[5]) && is_numeric($result[5])) {
            return (float) $result[5];
        }

        $lines = $result[2] ?? [];
        foreach ($result[3] ?? [] as $drop) {
            $lines = array_merge($lines, $drop['ActiveLines'] ?? []);
        }

        $payout = 0.0;
        foreach ($lines as $line) {
            $payout += floatval($line['payout'] ?? 0) * max(1, floatval($line['multiply'] ?? 1));
        }

        return $payout;
    }
}
