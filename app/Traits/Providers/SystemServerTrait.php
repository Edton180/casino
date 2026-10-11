<?php

namespace App\Traits\Providers;

use App\Models\GamesKey;
use App\Models\Order;
use App\Models\User;
use App\Traits\Missions\MissionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request as FacadesRequest;
use App\Helpers\Core as Helper;

trait SystemServerTrait
{
    use MissionTrait;

    /**
     * @var string
     */
    protected static $urlSystemServer;
    protected static $secretSystemServer;
    protected static $codeSystemServer;
    protected static $tokenSystemServer;

    private static function credencialSystemServer()
    {
        $setting = GamesKey::first();
        self::$urlSystemServer          = $setting->getAttributes()['system_url'];
        self::$secretSystemServer          = $setting->getAttributes()['system_secret'];
        self::$codeSystemServer         = $setting->getAttributes()['system_code'];
        self::$tokenSystemServer         = $setting->getAttributes()['system_token'];
    }

    public static function SystemServerLaunch($id, $demo)
    {
        self::credencialSystemServer();
        $postArray = [
            "agentToken" => self::$tokenSystemServer,
            "secretKey" => self::$secretSystemServer,
            "user_code" => Auth::guard("api")->user()->email,
            "game_code" => $id,
            "user_balance" => self::getBalanceSystemServer(Auth::guard("api")->id()),
            "user_rtp" => 50

        ];
        $response = Http::post(self::$urlSystemServer . "/game_launch/", $postArray);
        if ($response->successful()) {
            $data = $response->json();
            Log::info($data);
            return ["launch_url" => $data['launch_url']];
        } else {
            $data = $response->body();
            Log::info($data);
        }
    }
    public static function webhookSystemServerAPI(Request $request)
    {
        $tipo = $request->input("type");
        switch ($tipo) {
            case 'Balance':
                return self::getBalanceSystemServerAPI($request);
            case 'WinBet':
                return self::percaOuGanhoSystemServer($request);
        }
    }
    private static function getBalanceSystemServerAPI($dados)
    {
        $user = User::where("email", $dados->input("user_code"))->first();
        if ($user != null) {
            $saldo = (float)$user->wallet->balance + (float)$user->wallet->balance_bonus + (float) +(float)$user->wallet->balance_withdrawal;
            return response()->json(["msg" => "", "balance" => number_format($saldo, 2, ".", ".")]);
        } else {
            return response()->json(["msg" => "INVALID_USER", "balance" => 0]);
        }
    }
    private static function percaOuGanhoSystemServer($dados)
    {
        self::credencialSystemServer();
        $user = User::where("email", $dados->input("user_code"))->first();
        if ($user != null) {
            $wallet = $user->wallet;
            $detalhes = $dados->input($dados->input("game_type"));
            Log::info($dados);

            $bet = $detalhes['bet'];
            $win = $detalhes['win'];
            $saldoAnt = (float)$wallet->balance + (float)$wallet->balance_bonus + (float)$wallet->balance_withdrawal;
            $saldo = ((float)$wallet->balance + (float)$wallet->balance_bonus + (float)$wallet->balance_withdrawal) - $bet + $win;
            $id = rand(0, 9999999999);
            $changeBonus = null;
            if ($saldoAnt >= $bet) {
                if ($wallet->balance_bonus > $bet) {
                    $wallet->decrement('balance_bonus', $bet);
                    $changeBonus = 'balance_bonus';
                } elseif ($wallet->balance >= $bet) {
                    $wallet->decrement('balance', $bet);
                    $changeBonus = 'balance';
                } elseif ($wallet->balance_withdrawal >= $bet) {
                    $wallet->decrement('balance_withdrawal', $bet);
                    $changeBonus = 'balance_withdrawal';
                } else {
                    $changeBonus = "balance";
                    if ($saldoAnt >= $bet) {
                        $valorPerdido =  $bet;
                        $balanceBonus = $wallet->balance_bonus;
                        $balance = $wallet->balance;
                        $balanceWithdrawal = $wallet->balance_withdrawal;

                        if ($balanceBonus >= $valorPerdido) {
                            $balanceBonus -= $valorPerdido;
                            $valorPerdido = 0;
                        } else {
                            $valorPerdido -= $balanceBonus;
                            $balanceBonus = 0;
                        }


                        if ($balance >= $valorPerdido) {
                            $balance -= $valorPerdido;
                            $valorPerdido = 0;
                        } else {
                            $valorPerdido -= $balance;
                            $balance = 0;
                        }


                        if ($balanceWithdrawal >= $valorPerdido) {
                            $balanceWithdrawal -= $valorPerdido;
                            $valorPerdido = 0;
                        } else {
                            $valorPerdido -= $balanceWithdrawal;
                            $balanceWithdrawal = 0;
                        }


                        $wallet->update([
                            "balance_bonus" => $balanceBonus,
                            "balance" => $balance,
                            "balance_withdrawal" => $balanceWithdrawal
                        ]);
                    }
                }
                if ($bet == 0 && $win != 0) {
                    $changeBonus = "balance";
                }

                if ($bet != 0 || $win != 0) {
                    Order::create([
                        "user_id" => $user->id,
                        "session_id" => $detalhes['round_id'],
                        "transaction_id" => $detalhes['txn_id'],
                        "game" => $detalhes['game_code'],
                        "game_uuid" => $detalhes['game_code'],
                        "type" => $bet == 0 ? "win" : "bet",
                        "type_money" => $changeBonus,
                        "amount" =>  $bet == 0 ? $win : $bet,
                        "providers" => "systemserver",
                        "refunded" => false,
                        "round_id" => $detalhes['round_id'],
                        "status" => true
                    ]);
                    Helper::generateGameHistory($user->id, $bet == 0 ? "win" : "loss", $win, $bet, $changeBonus, $detalhes['txn_id']);
                }

                return response()->json(["msg" => "", "balance" => $saldo]);
            } else {
                return response()->json(["msg" => "INSUFFICIENT_USER_FUNDS"]);
            }
        } else {
            return response()->json(["msg" => "INVALID_USER"]);
        }
    }
    private static function getBalanceSystemServer($id)
    {
        $user = User::where("id", $id)->first();
        if ($user != null) {
            $saldo = (float)$user->wallet->balance + (float)$user->wallet->balance_bonus + (float) +(float)$user->wallet->balance_withdrawal;
            return $saldo;
        } else {
            return 0;
        }
    }
}
