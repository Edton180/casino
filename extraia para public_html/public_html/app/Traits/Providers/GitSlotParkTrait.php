<?php

namespace App\Traits\Providers;

use App\Helpers\Core;
use App\Helpers\Core as Helper;
use App\Models\Game;
use App\Models\GamesKey;
use App\Models\GGRGames;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\Missions\MissionTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait GitSlotParkTrait
{
    use MissionTrait;

    /**
     * 456
     * @var string
     */
    protected static $pp_agent;
    protected static $pp_token;
    protected static $pp_secret;
    protected static $pp_endpoint;

    /**
     * 456
     * @var string
     */
    protected static $pg_agent;
    protected static $pg_token;
    protected static $pg_secret;
    protected static $pg_endpoint;

    /**
     * @return void
     */
    public static function getGitSlotParkPragmaticCredentials(): bool
    {
        $setting = GamesKey::first();

        self::$pp_agent = $setting->getAttributes()['gitslotpark_pp_agent'];
        self::$pp_token = $setting->getAttributes()['gitslotpark_pp_token'];
        self::$pp_secret = $setting->getAttributes()['gitslotpark_pp_secret'];
        self::$pp_endpoint = $setting->getAttributes()['gitslotpark_pp_endpoint'];

        Log::info('getGitSlotParkPragmaticCredentials');
        Log::info(json_encode([
            'pp_agent'      => self::$pp_agent,
            'pp_token'      => self::$pp_token,
            'pp_secret'     => self::$pp_secret,
            'pp_endpoint'   => self::$pp_endpoint
        ], JSON_PRETTY_PRINT));

        return true;
    }

    /**
     * @return void
     */
    public static function getGitSlotParkPGSoftCredentials(): bool
    {
        $setting = GamesKey::first();

        self::$pg_agent = $setting->getAttributes()['gitslotpark_pg_agent'];
        self::$pg_token = $setting->getAttributes()['gitslotpark_pg_token'];
        self::$pg_secret = $setting->getAttributes()['gitslotpark_pg_secret'];
        self::$pg_endpoint = $setting->getAttributes()['gitslotpark_pg_endpoint'];

        return true;
    }

    /**
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     * @param $rtp
     * @param $provider
     * @return void
     */
    public static function LoadingGamesGitSlotPark()
    {
        if (self::getCredentialsGitSlotPark()) {
            $postArray = [
                "agent_code" => "",
                "agent_token" => "",
                "user_code" => "test",
                "game_type" => "slot",
                "provider_code" => "PRAGMATIC",
                "game_code" => "vs20doghouse",
                "lang" => "en",
                "user_balance" => 1000,
            ];

            $response = Http::post(self::$apiEndpoint . 'game_launch', $postArray);

            if ($response->successful()) {
                $games = $response->json();

                dd($games);
            }
        }
    }

    /**
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     * @param $rtp
     * @param $provider
     * @return void
     */
    public static function UpdateRTPGitSlotPark($rtp, $provider)
    {
        if (self::getCredentialsGitSlotPark()) {
            $postArray = [
                "method" => "control_rtp",
                "agent_code" => self::$agentCode,
                "agent_token" => self::$agentToken,
                "provider_code" => $provider,
                "user_code" => auth('api')->id() . '',
                "rtp" => $rtp,
            ];

            $response = Http::post(self::$apiEndpoint, $postArray);

            if ($response->successful()) {

            }
        }
    }

    /**
     * Create User
     * Metodo para criar novo usuário
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     *
     * @return bool
     */
    public static function createUserGitSlotPark()
    {
        if (self::getCredentialsGitSlotPark()) {
            $postArray = [
                "method" => "user_create",
                "agent_code" => self::$agentCode,
                "agent_token" => self::$agentToken,
                "user_code" => auth('api')->id() . '',
            ];

            $response = Http::post(self::$apiEndpoint, $postArray);

            if ($response->successful()) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * Iniciar Jogo
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     * Metodo responsavel para iniciar o jogo
     *
     */
    public static function GameLaunchGitSlotPark($game, $user)
    {
        $providerCode = strtolower($game->provider->code);
        $userId = 'user' . $user->id;
        $withCredentials = FALSE;
        if ($providerCode == 'pragmatic') {
            $withCredentials = self::getGitSlotParkPragmaticCredentials();
        } else if ($providerCode == 'pgsoft') {
            $withCredentials = self::getGitSlotParkPGSoftCredentials();
        }

        if ($providerCode == 'pragmatic' && $withCredentials) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::$pp_token,
            ])->post(self::$pp_endpoint . '/userAuth', [
                'agentID'   => self::$pp_agent,
                'userID'    => $userId,
                'isaffiliate'=> ($user->is_demo_agent == 1),
                'lang'      => substr($user->language, 0, 2),
                'gameid'    => $game->game_id,
                'lobbyUrl'  => config('app.url')
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['url'];
            }
        } else if ($providerCode == 'pgsoft' && $withCredentials) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::$pg_token,
            ])->post(self::$pg_endpoint . '/userAuth', [
                'agentID'   => self::$pg_agent,
                'userID'    => $userId,
                'isaffiliate'=> ($user->is_demo_agent == 1),
                'lang'      => substr($user->language, 0, 2),
                'gameid'    => $game->game_id,
                'lobbyUrl'  => config('app.url')
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['url'];
            }
        } else {
            Log::info('no credentials: ' . json_encode([
                'provider'      => $game->provider->code,
                'credentials'   => $withCredentials
            ], JSON_PRETTY_PRINT));
            dd('no credentials');
        }
    }

    /**
     * Get GitSlotPark Balance
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     * @return false|void
     */
    public static function getGitSlotParkUserDetail()
    {
        if (self::getCredentialsGitSlotPark()) {
            $dataArray = [
                "method" => "call_players",
                "agent_code" => self::$agentCode,
                "agent_token" => self::$agentToken,
            ];

            $response = Http::post(self::$apiEndpoint, $dataArray);

            if ($response->successful()) {
                $data = $response->json();

                dd($data);
            } else {
                return false;
            }
        }

    }

    /**
     * Get GitSlotPark Balance
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     * @param $provider_code
     * @param $game_code
     * @param $lang
     * @param $userId
     * @return false|void
     */
    public static function getGitSlotParkBalance()
    {
        try {
            if (self::getCredentialsGitSlotPark()) {
                $dataArray = [
                    "agent_code" => self::$agentCode,
                    "agent_token" => self::$agentToken,
                ];

                $response = Http::post(self::$apiEndpoint . '/info', $dataArray);

                if ($response->successful()) {
                    $data = $response->json();

                    return $data['agent_balance'] ?? 0;
                } else {
                    return 0;
                }
            }
        } catch (\Exception $e) {
            return 0;
        }

    }

    /**
     * Prepare Transaction
     * Metodo responsavel por preparar a transação
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     *
     * @param $wallet
     * @param $userCode
     * @param $txnId
     * @param $betMoney
     * @param $winMoney
     * @param $gameCode
     * @return \Illuminate\Http\JsonResponse|void
     */
    private static function PrepareTransactionsGitSlotPark($walletId, $userCode, $txnId, $betMoney, $winMoney, $gameCode, $providerCode)
    {
        $wallet = Wallet::find($walletId);
        $user = User::find($wallet->user_id);

        $typeAction = 'bet';
        $changeBonus = 'balance';
        $bet = floatval($betMoney);

        /// deduz o saldo apostado
        if ($wallet->balance_bonus > $bet) {
            $wallet->decrement('balance_bonus', $bet); // retira do bônus
            $changeBonus = 'balance_bonus';

        } elseif ($wallet->balance >= $bet) {
            $wallet->decrement('balance', $bet); // retira do saldo depositado
            $changeBonus = 'balance';

        } elseif ($wallet->balance_withdrawal >= $bet) {
            $wallet->decrement('balance_withdrawal', $bet);
            $changeBonus = 'balance_withdrawal';

        } elseif ($wallet->total_balance >= $bet) {
            $remainingBet = $bet - $wallet->balance;
            $wallet->decrement('balance', $wallet->balance);
            $wallet->decrement('balance_withdrawal', $remainingBet);
            $changeBonus = 'balance';
        } else {
            return false;
        }

        /// criar uma transação
        $transaction = self::CreateTransactionsGitSlotPark($userCode, time(), $txnId, $typeAction, $changeBonus, $bet, $gameCode, $gameCode);

        if ($transaction) {
            /// salvar transação GGR
            GGRGames::create([
                'user_id' => $userCode,
                'provider' => $providerCode,
                'game' => $gameCode,
                'balance_bet' => $bet,
                'balance_win' => 0,
                'currency' => $wallet->currency,
                'aggregator' => "wordslot",
                "type" => "loss",
            ]);

            return $transaction;
        }

        return false;
    }

    /**
     * @param $request
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     * @return \Illuminate\Http\JsonResponse|null
     */
    public static function webhookGitSlotPark($request, $brand, $action)
    {
        $user = NULL;
        $return_code= 0;
        $return_message= 'success';
        $signature  = NULL;
        $invalidSignature = FALSE;
        $walletBalance = NULL;
        $agent      = NULL;
        $token      = NULL;
        $secret     = NULL;
        $endpoint   = NULL;

        Log::info('webhookGitSlotPark > ' . $brand . ' > ' . $action);

        if ($brand == 'pgsoft') {
            self::getGitSlotParkPGSoftCredentials();
            $agent = self::$pg_agent;
            $secret = self::$pg_secret;
        } else if ($brand == 'pragmatic') {
            self::getGitSlotParkPragmaticCredentials();
            $agent = self::$pp_agent;
            $secret = self::$pp_secret;
        }
        $json_data = file_get_contents("php://input");
        Log::info('RECEIVED: ' . $json_data);
        $received = json_decode($json_data, true);

        if ($received !== null) {
            if ($received['agentID'] != $agent) {
                $return_code = 4;
                $return_message = 'Invalid Agent. Unknown agent id or agent is disabled';
            } else if ($received['userID']) {
                $userId = str_replace(['user'], '', $received['userID']);
                $user = User::where('id', $userId)->first();

                if ($user) {
                    $walletBalance = Wallet::where('user_id', $user->id)->first()->total_balance;
                    switch (strtolower($action)) {
                        case "getbalance":
                            $str = $received['agentID'].$received['userID'].$received['gameID'];
                            $signature = self::GitSlotParkGetSign($secret, $str);
                            if($signature != $received['sign']) {
                                $invalidSignature = TRUE;
                            }
                            break;
                        case "betwin":
                            if ($walletBalance < $received['betAmount']) {
                                $return_code = 6;
                                $return_message = 'Insufficient funds available to complete the transaction';
                            } else {
                                $str = $received['agentID'].$received['userID'].number_format((float)$received['betAmount'], 2).number_format((float)$received['winAmount'], 2).$received['transactionID'].$received['roundID'].$received['gameID'];
                                $signature = self::GitSlotParkGetSign($secret, $str);
                                if($signature != $received['sign']) {
                                    $invalidSignature = TRUE;
                                } else {
                                    $cc = self::GameCallbackGitSlotPark($user, $received);
                                    if (is_array($cc)) {
                                        $return_code = $cc[0];
                                        $return_message = $cc[1];
                                    } else {
                                        $walletBalance = Wallet::where('user_id', $user->id)->first()->total_balance;
                                    }
                                }
                            }
                            break;
                        case "withdraw":
                            $str = $received['agentID'].$received['userID'].number_format((float)$received['amount'], 2).$received['transactionID'].$received['roundID'];
                            $transaction = Order::create([
                                'user_id'   => $user->id,
                                'session_id'=> time(),
                                'transaction_id'=> $received['transactionID'],
                                'game'  => $received['gameID'],
                                'game_uuid'  => $received['gameID'],
                                'type'  => 'withdraw',
                                'type_money'  => 'balance',
                                'amount'    => $received['amount'],
                                'providers'    => 'gitslotpark',
                                'status'        => 0
                            ]);
                            break;
                        case "deposit":
                            $str = $received['agentID'].$received['userID'].number_format((float)$received['amount'], 2).$received['refTransactionID'].$received['transactionID'].$received['roundID'];
                            $user->wallet->increment('balance_withdrawal', $received['amount']);
                            $walletBalance = $user->wallet->total_balance;
                            break;
                        case "money_callback":
                            return self::MoneyCallbackGitSlotPark($user, $received);
                        default:
                            return response()->json(['status' => 0]);
                            break;
                    }

                    if ($invalidSignature) {
                        $return_code = 3;
                        $return_message = 'Invalid Sign check if valid key was used and if the data was sent in a valid format';
                        $walletBalance = NULL;
                    }
                } else {
                    $return_message = 'Cannot find specified user id';
                }
            } else {
                $return_code = 5;
                $return_message = 'User ID not found';
            }
        } else {
            abort(400);
        }

        $data_reponse = [
            'code'      => $return_code,
            'message'   => $return_message
        ];

        if ($walletBalance != NULL) {
            $data_reponse['balance'] = $walletBalance;
        }
        Log::info('RESPONSE: ' . json_encode($data_reponse));
        return response()->json($data_reponse);
    }

    /**
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     * @param $request
     * @return \Illuminate\Http\JsonResponse|void|null
     */
    private static function GameCallbackGitSlotPark($user, $data)
    {
        try {
            $game = Game::where('game_id', $data['gameID'])->first();
            return self::GitSlotProcessPlay($data, $user, $game);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            dd($e->getMessage());
        }
    }

    /**
     * @param $data
     * @param $userId
     * @param $type
     * @return \Illuminate\Http\JsonResponse|void
     */
    private static function GitSlotProcessPlay($data, $user, $game)
    {
        $wallet = $user->wallet;
        $userId = $user->id;
        if (!empty($wallet)) {
            self::CheckMissionExist($userId, $game);
            /// verificar se é transação duplicada
            $transaction = Order::where([
                ['transaction_id', '=', $data['transactionID']],
                ['type', '=', 'bet']
            ])->first();
            if (!empty($transaction)) {
                Log::info('GitSlotProcessPlay > Duplicate transaction');
                return [11, 'Duplicate transaction'];
            }

            $transaction = self::PrepareTransactionsGitSlotPark(
                $wallet->id,
                $userId,
                $data['transactionID'],
                $data['betAmount'],
                $data['winAmount'],
                $game['game_code'],
                $game['provider_id']
            );

            $transactionWin = Order::where('transaction_id', $data['transactionID'])->where('type', 'win')->first();
            if ($transactionWin) {
                Log::info('GitSlotProcessPlay > $transactionWin :: NULL');
                return NULL;
            }

            $transaction = Order::where('transaction_id', $data['transactionID'])->where('type', 'bet')->first();
            if ($transaction) {
                $tmp = $transaction->toArray();
                $tmp['type'] = 'check';
                $order = Order::create($tmp);

                if (floatval($data['winAmount']) > 0) {
                    Helper::generateGameHistory(
                        $wallet->user_id,
                        'win',
                        $data['winAmount'],
                        $transaction->amount,
                        $transaction->getAttributes()['type_money'],
                        $transaction->transaction_id
                    );

                    GGRGames::create([
                        'user_id' => $userId,
                        'provider' => $game['provider_id'],
                        'game' => $game['game_code'],
                        'balance_bet' => $data['betAmount'],
                        'balance_win' => $data['winAmount'],
                        'currency' => $wallet->currency,
                        'aggregator' => "gitslotpark",
                        "type" => "win",
                    ]);

                    Log::info('GitSlotProcessPlay > WIN');
                } else {
                    Helper::generateGameHistory(
                        $wallet->user_id,
                        'loss',
                        $data['winAmount'],
                        $transaction->amount,
                        $transaction->getAttributes()['type_money'],
                        $transaction->transaction_id
                    );

                    $order->update([
                        'type'  => 'loss'
                    ]);

                    Log::info('GitSlotProcessPlay > LOSS');
                }
            }
        }

        return TRUE;
    }

    /**
     * Money Callback World Slot
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    private static function MoneyCallbackGitSlotPark($request)
    {
        $data = $request->all();

        $transaction = Order::where('transaction_id', $data['txn_id'])->first();
        $wallet = Wallet::where('user_id', $transaction->user_id)->first();

        if (!empty($transaction) && !empty($wallet)) {

        }

        return response()->json([
            'status' => 1,
            'user_balance' => $wallet->total_balance,
        ]);
    }

    /**
     * Create Transactions
     * Metodo para criar uma transação
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     *
     * @return false
     */
    private static function CreateTransactionsGitSlotPark($playerId, $betReferenceNum, $transactionID, $type, $changeBonus, $amount, $game, $pn)
    {

        $order = Order::create([
            'user_id' => $playerId,
            'session_id' => $betReferenceNum,
            'transaction_id' => $transactionID,
            'type' => $type,
            'type_money' => $changeBonus,
            'amount' => $amount,
            'providers' => 'worldslot',
            'game' => $game,
            'game_uuid' => $pn,
            'round_id' => 1,
        ]);

        if ($order) {
            return $order;
        }

        return false;
    }

    /**
     * Create User
     * Metodo para criar novo usuário
     *
     * @return bool
     */
    public static function getProviderGitSlotPark($param)
    {
        if (self::getCredentialsGitSlotPark()) {
            $response = Http::post(self::$apiEndpoint . 'provider_list', [
                'agent_code' => self::$agentCode,
                'agent_token' => self::$agentToken,
                'game_type' => $param, ///  [slot, casino, pachinko]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] == 1) {
                    foreach ($data['providers'] as $provider) {
                        $checkProvider = Provider::where('code', $provider['code'])->where('distribution', 'worldslot')->first();
                        if (empty($checkProvider)) {

                            $dataProvider = [
                                'code' => $provider['code'],
                                'name' => $provider['name'],
                                'rtp' => 90,
                                'status' => 1,
                                'distribution' => 'worldslot',
                            ];

                            Provider::create($dataProvider);
                        }
                    }
                }
            }
        }
    }

    /**
     * Create User
     * Metodo para criar novo usuário
     *
     * @return bool
     */
    public static function getPragmaticGames()
    {
        if (self::getGitSlotParkPragmaticCredentials()) {
            $providers = Provider::where([
                ['distribution', '=', 'gitslotpark'],
                ['code', '=', 'pragmatic'],
            ])->get();

            foreach ($providers as $provider) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . self::$pp_token,
                ])->get(self::$pp_endpoint . '/gamelist');

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data)) {
                        set_time_limit(3600);
                        foreach ($data['data'] as $game) {
                            $slugGame = Core::Slugify($game['symbol']);
                            $checkGame = Game::where('provider_id', $provider->id)->where('game_code', $slugGame)->first();

                            if (empty($checkGame)) {
                                if (!empty($game['iconurl2'])) {
                                    $image = self::uploadFromUrlGitSlotPark($game['iconurl2'], $game['symbol']);
                                } else {
                                    $image = null;
                                }

                                if (!empty($game['symbol'])) {
                                    $data = [
                                        'provider_id' => $provider->id,
                                        'game_id' => $game['gameid'],
                                        'game_code' => $slugGame,
                                        'game_name' => $game['name'],
                                        'technology' => 'html5',
                                        'distribution' => 'gitslotpark',
                                        'rtp' => 90,
                                        'cover' => $image,
                                        'show_home' => rand(0, 1),
                                        'status' => 1,
                                    ];

                                    Game::create($data);
                                }
                            }
                        }
                    }
                }
            }
        } else {

            dd('no credentials');
        }
    }

    /**
     * Create User
     * Metodo para criar novo usuário
     *
     * @return bool
     */
    public static function getPGSoftGames()
    {
        if (self::getGitSlotParkPGSoftCredentials()) {
            $providers = Provider::where([
                ['distribution', '=', 'gitslotpark'],
                ['code', '=', 'pgsoft'],
            ])->get();

            Log::info('providers: ' . json_encode($providers, JSON_PRETTY_PRINT));

            foreach ($providers as $provider) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . self::$pg_token,
                ])->get(self::$pg_endpoint . '/gamelist');

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data)) {
                        set_time_limit(3600);
                        foreach ($data['data'] as $game) {
                            $slugGame = Core::Slugify($game['symbol']);
                            $checkGame = Game::where('provider_id', $provider->id)->where('game_code', $slugGame)->first();

                            if (empty($checkGame)) {
                                if (!empty($game['iconurl'])) {
                                    $image = self::uploadFromUrlGitSlotPark($game['iconurl'], $game['symbol']);
                                } else {
                                    $image = null;
                                }

                                if (!empty($game['symbol'])) {
                                    $data = [
                                        'provider_id' => $provider->id,
                                        'game_id' => $game['gameid'],
                                        'game_code' => $slugGame,
                                        'game_name' => $game['name'],
                                        'technology' => 'html5',
                                        'distribution' => 'gitslotpark',
                                        'rtp' => 90,
                                        'cover' => $image,
                                        'show_home' => rand(0, 1),
                                        'status' => 1,
                                    ];

                                    Game::create($data);
                                }
                            }
                        }
                    }
                }
            }
        } else {

            dd('no credentials');}
    }

    /**
     * @param $url
     * @return string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private static function uploadFromUrlGitSlotPark($url, $name = null)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get($url);

            if ($response->getStatusCode() === 200) {
                $fileContent = $response->getBody();

                // Extrai o nome do arquivo e a extensão da URL
                $parsedUrl = parse_url($url);
                $pathInfo = pathinfo($parsedUrl['path']);
                //$fileName = $pathInfo['filename'] ?? 'file_' . time(); // Nome do arquivo
                $fileName = $name ?? $pathInfo['filename'];
                $extension = $pathInfo['extension'] ?? 'png'; // Extensão do arquivo

                // Monta o nome do arquivo com o prefixo e a extensão
                $fileName = 'gitslotpark/' . $fileName . '.' . $extension;

                // Salva o arquivo usando o nome extraído da URL
                Storage::disk('public')->put($fileName, $fileContent);

                return $fileName;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function GitSlotParkGetSign($key, $message) {
        return strtoupper(hash_hmac('sha256', pack('A*', $message), pack('A*', $key)));
    }
}
