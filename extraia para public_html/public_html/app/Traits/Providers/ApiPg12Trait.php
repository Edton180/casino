<?php

namespace App\Traits\Providers;

use App\Helpers\Core as Helper;
use App\Models\Game;
use App\Models\GamesKey;
use App\Models\GGRGames;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\Missions\MissionTrait;
use Illuminate\Support\Facades\Http;

trait ApiPg12Trait
{
    use MissionTrait;

    /**
     * * Criado por @thigasdev -> https://t.me/thigasdev
     * @var string
     */
    protected static $agentCode;
    protected static $agentToken;
    protected static $agentSecretKey;
    protected static $apiEndpoint;

    /**
     * * Criado por @thigasdev -> https://t.me/thigasdev
     * @return void
     */
    public static function ApiPg12GetCredential(): bool
    {
        $setting = GamesKey::first();

        self::$agentCode        = $setting->getAttributes()['apipg12_code'];
        self::$agentToken       = $setting->getAttributes()['apipg12_token'];
        self::$agentSecretKey   = $setting->getAttributes()['apipg12_secret'];
        self::$apiEndpoint      = $setting->getAttributes()['apipg12_url'];

        return true;
    }

    public static function GameLaunchApiPg12($provider_code, $game_code, $lang, $userEmail)
    {
        self::ApiPg12GetCredential();

        $endpointwo =  self::$apiEndpoint . "/api/v1/game_launch";
        $user = User::where('email', $userEmail)->first();
        $wallet = Wallet::where('user_id', $user->id)->where('active', 1)->first();

        error_log($game_code);

        switch ($game_code) {
            case '40':
                $gamename = "jungle-delight";
                break;
            case '98':
                $gamename = "fortune-ox";
                break;
            case '63':
                $gamename = "dragon-tiger-luck";
                break;
            case '42':
                $gamename = "ganesha-gold";
                break;
            case '48':
                $gamename = "double-fortune";
                break;
            case '68':
                $gamename = "fortune-mouse";
                break;
            case '69':
                $gamename = "bikini-paradise";
                break;
            case '126':
                $gamename = "fortune-tiger";
                break;
            case '1543462':
                $gamename = "fortune-rabbit";
                break;
            case '1695365':
                $gamename = "fortune-dragon";
                break;
            case '1682240':
                $gamename = "cash-mania";
                break;
            case '1738001':
                $gamename = "chicky-run";
                break;
            default:
                $gamename = null;
        }

        $postArray = [
            "secretKey"    => self::$agentSecretKey,
            "agentToken"   => self::$agentToken,
            "user_code"    => $userEmail,
            "provider_code" => $provider_code,
            "game_code"    => $gamename,
            "user_balance" => $wallet->total_balance,
            // "lang"         => $lang
        ];

        $response = Http::withOptions(['verify' => false])->post($endpointwo, $postArray);
        $data = $response->json();

        error_log(json_encode($data));
        $data['launchUrl'] = $data['launch_url'];

        return $data;
    }


    public static function ApiPg12GameCallback($request)
    {
        $data = $request->all();
        try {
            if ($data['game_type'] == 'slot' && isset($data['slot'])) {
                $user = User::where('email',  $data['user_code'])->first();
                return self::ApiPg12ProcessPlay($data, $user->id, 'slot');
            }

            if ($data['game_type'] == 'casino' && isset($data['casino'])) {
                $user = User::where('email',  $data['user_code'])->first();
                return self::ApiPg12ProcessPlay($data, $user->id, 'casino');
            }

            return response()->json([
                'status' => 0,
                'msg' => 'INVALID_USER	'
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }


    public static function ApiPg12UserBalance($request)
    {
        $userCode = $request->input('user_code');
        $user = User::where('email', $userCode)->first();
        $wallet = Wallet::where('user_id', $user->id)->first();

        return response()->json([
            "user_code" => $userCode,
            "user_balance" => $wallet->total_balance,
        ]);
    }


    private static function ApiPg12ProcessPlay($data, $userId, $type)
    {
        Http::post('https://teste.oneplayslots.pro/gold_api/game_callback', $data);
        $wallet = Wallet::where('user_id', $userId)->where('active', 1)->first();
        
        $win = $data[$type]['win'];
        $bet = $data[$type]['bet'];
        $saldo = floatval(floatval($wallet->balance)-floatval($bet)+floatval($win));
        
        $wallet->update(['balance'=> $saldo]);
        $wallet = Wallet::where('user_id', $userId)->where('active', 1)->first();
        
        $transactionWin = Order::where('transaction_id', $data[$type]['txn_id'])->where('type', 'win')->first();
            
        Helper::generateGameHistory(
            $wallet->user_id,
            'win',
            $data[$type]['win'],
            $transaction->amount,
            $transaction->getAttributes()['type_money'],
            $transaction->transaction_id
        );

        return response()->json([
            'status' => 1,
            'user_balance' => $wallet->total_balance,
        ]);
        
    }
    
}
