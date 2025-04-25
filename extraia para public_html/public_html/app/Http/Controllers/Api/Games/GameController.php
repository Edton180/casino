<?php

namespace App\Http\Controllers\Api\Games;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameFavorite;
use App\Models\GameLike;
use App\Models\GamesKey;
use App\Models\Gateway;
use App\Models\Provider;
use App\Models\Wallet;
use App\Traits\Providers\EvergameTrait;
use App\Traits\Providers\FiversTrait;
use App\Traits\Providers\Games2ApiTrait;
use App\Traits\Providers\KaGamingTrait;
use App\Traits\Providers\PlayGamingTrait;
use App\Traits\Providers\PlayIGamingTrait;
use App\Traits\Providers\SalsaGamesTrait;
use App\Traits\Providers\VeniXTrait;
use App\Traits\Providers\VibraTrait;
use App\Traits\Providers\WorldSlotTrait;
use Illuminate\Http\Request;
use App\Traits\Providers\PlayConnectTrait;
use App\Traits\Providers\PlayFiverTrait; // Adicione essa linha
use App\Traits\Providers\SystemServerTrait;
use App\Traits\Providers\ApiPragmatic40Trait;
use App\Traits\Providers\ApiPg12Trait;

class GameController extends Controller
{
    use PlayConnectTrait,
        KaGamingTrait,
        VibraTrait,
        SalsaGamesTrait,
        WorldSlotTrait,
        Games2ApiTrait,
        VeniXTrait,
        EvergameTrait,
        PlayGamingTrait,
        PlayIGamingTrait,
        ApiPragmatic40Trait,
        PlayFiverTrait,
        ApiPg12Trait;
        
    use FiversTrait, SystemServerTrait;

    /**
     * @dev victormsalatiel
     * Display a listing of the resource.
     */
    public function index()
    {
        $providers = Provider::with(['games', 'games.provider'])
            ->whereHas('games')
            ->orderBy('name', 'desc')
            ->where('status', 1)
            ->get();

        return response()->json(['providers' =>$providers]);
    }

    /**
     * @dev victormsalatiel
     * @return \Illuminate\Http\JsonResponse
     */
    public function featured()
    {
        $featured_games = Game::with(['provider'])
                    ->where('is_featured', 1)
                    ->where('status', 1)
                    ->get();

        return response()->json(['featured_games' => $featured_games]);
    }

    /**
     * Source Provider
     *
     * @dev victormsalatiel
     * @param Request $request
     * @param $token
     * @param $action
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function sourceProvider(Request $request, $token, $action)
    {
        $tokenOpen = \Helper::DecToken($token);
        $validEndpoints = ['session', 'icons', 'spin', 'freenum'];

        if (in_array($action, $validEndpoints)) {
            if(isset($tokenOpen['status']) && $tokenOpen['status'])
            {
                $game = Game::whereStatus(1)->where('game_code', $tokenOpen['game'])->first();
                if(!empty($game)) {
                    $controller = \Helper::createController($game->game_code);

                    switch ($action) {
                        case 'session':
                            return $controller->session($token);
                        case 'spin':
                            return $controller->spin($request, $token);
                        case 'freenum':
                            return $controller->freenum($request, $token);
                        case 'icons':
                            return $controller->icons();
                    }
                }
            }
        } else {
            return response()->json([], 500);
        }
    }

    /**
     * @dev victormsalatiel
     * Store a newly created resource in storage.
     */
    public function toggleFavorite($id)
    {
        if(auth('api')->check()) {
            $checkExist = GameFavorite::where('user_id', auth('api')->id())->where('game_id', $id)->first();
            if(!empty($checkExist)) {
                if($checkExist->delete()) {
                    return response()->json(['status' => true, 'message' => 'Removido com sucesso']);
                }
            }else{
                $gameFavoriteCreate = GameFavorite::create([
                    'user_id' => auth('api')->id(),
                    'game_id' => $id
                ]);

                if($gameFavoriteCreate) {
                    return response()->json(['status' => true, 'message' => 'Criado com sucesso']);
                }
            }
        }
    }

    /**
     * @dev victormsalatiel
     * Store a newly created resource in storage.
     */
    public function toggleLike($id)
    {
        if(auth('api')->check()) {
            $checkExist = GameLike::where('user_id', auth('api')->id())->where('game_id', $id)->first();
            if(!empty($checkExist)) {
                if($checkExist->delete()) {
                    return response()->json(['status' => true, 'message' => 'Removido com sucesso']);
                }
            }else{
                $gameLikeCreate = GameLike::create([
                    'user_id' => auth('api')->id(),
                    'game_id' => $id
                ]);

                if($gameLikeCreate) {
                    return response()->json(['status' => true, 'message' => 'Criado com sucesso']);
                }
            }
        }
    }

    /**
     * @dev victormsalatiel
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $game = Game::with(['categories', 'provider'])->whereStatus(1)->find($id);
        if(!empty($game)) {
            if(auth('api')->check()) {
                $wallet = Wallet::where('user_id', auth('api')->user()->id)->first();
                if($wallet->total_balance >= 0) {
                    $game->increment('views');

                    $token = \Helper::MakeToken([
                        'id' => auth('api')->user()->id,
                        'game' => $game->game_code
                    ]);

                    switch ($game->distribution) {
                         case 'apipg12':
                            $apipg12Launch = self::GameLaunchApiPg12($game->provider->code, $game->game_id, 'pt', auth('api')->user()->email);
                            if(isset($apipg12Launch['launchUrl'])){
                                return response()->json([
                                    'game'=> $game,
                                    'gameUrl'=> $apipg12Launch['launchUrl']
                                ]);
                            } else {
                                return response()->json($centergamingpgLaunch);
                            }
                        case 'apipragmatic40':
                        $apiPragmatic40Launch = self::ApiPragmaticGameLaunch($game->provider->code, $game->game_id, 'pt', auth('api')->user()->email);
                        if(isset($apiPragmatic40Launch['launch_url'])) {
                            return response()->json([
                                'game' => $game,
                                'gameUrl' => $apiPragmatic40Launch['launch_url'],
                                'token' => $token
                            ]);
                        }
                    
                        return response()->json(['error' => $apiPragmatic40Launch, 'status' => false ], 400);
                        case 'source':
                            return response()->json([
                                'game' => $game,
                                'gameUrl' => url('/originals/'.$game->game_code.'/index.html?token='.$token),
                                'token' => $token
                            ]);
                            
                                    // Adicione essa linha  
                        case 'play_fiver':
                            $playfiver = self::playFiverLaunch($game->game_id, $game->only_demo);
                            return response()->json([
                                'game' => $game,
                                'gameUrl' => $playfiver['launch_url'],
                                'token' => $token
                            ]);
                       case 'playconnect':
                              $gameLauncher = self::GameLaunchPlayConnect($game);
                        
                              if($gameLauncher) {
                                return response()->json([
                                  'game' => $game,
                                  'gameUrl' => $gameLauncher,
                                  'token' => $token
                                ]);
                              }else{
                                return response()->json();
                              }
                        case 'venix':
                            $gameLauncher = self::GameLaunchVeniX($game);

                            if($gameLauncher) {
                                return response()->json([
                                    'game' => $game,
                                    'gameUrl' => $gameLauncher,
                                    'token' => $token
                                ]);
                            }else{
                                return response()->json();
                            }
                        case 'playigaming':
                            $gameLauncher = self::GameLaunchPIG($game);

                            if($gameLauncher) {
                                return response()->json([
                                    'game' => $game,
                                    'gameUrl' => $gameLauncher,
                                    'token' => $token
                                ]);
                            }else{
                                return response()->json();
                            }
                        case 'playgaming':
                            $gameLauncher = self::LaunchGamePlayGaming($game->game_id);

                            if($gameLauncher) {
                                return response()->json([
                                    'game' => $game,
                                    'gameUrl' => $gameLauncher,
                                    'token' => $token
                                ]);
                            }else{
                                return response()->json();
                            }
                        case 'salsa':
                            return response()->json([
                                'game' => $game,
                                'gameUrl' => self::playGameSalsa('CHARGED', 'BRL', 'pt', $game->game_id),
                                'token' => $token
                            ]);
                        case 'kagaming':
                            return response()->json([
                                'game' => $game,
                                'gameUrl' => self::GameLaunchKaGaming($game->game_server_url, $game->game_code),
                                'token' => $token
                            ]);
                        case 'evergame':
                            $evergameLaunch = self::GameLaunchEvergame($game->provider->code, $game->game_id, 'pt', auth('api')->id());

                            if(isset($evergameLaunch['launchUrl'])) {
                                return response()->json([
                                    'game' => $game,
                                    'gameUrl' => $evergameLaunch['launchUrl'],
                                ]);
                            }else{
                                return response()->json($evergameLaunch);
                            }
                        case 'vibra_gaming':
                            return response()->json([
                                'game' => $game,
                                'gameUrl' => self::GenerateGameLaunch($game),
                                'token' => $token
                            ]);
                        case 'fivers':
                            $fiversLaunch = self::GameLaunchFivers($game->provider->code, $game->game_id, 'pt', auth('api')->id());

                            if(isset($fiversLaunch['launch_url'])) {
                                return response()->json([
                                    'game' => $game,
                                    'gameUrl' => $fiversLaunch['launch_url'],
                                    'token' => $token
                                ]);
                            }

                            return response()->json(['error' => $fiversLaunch, 'status' => false ], 400);
                        case 'games2_api':
                            $games2ApiLaunch = self::GameLaunchGames2($game->provider->code, $game->game_id, 'pt', auth('api')->id());

                            if(isset($games2ApiLaunch['launch_url'])) {
                                return response()->json([
                                    'game' => $game,
                                    'gameUrl' => $games2ApiLaunch['launch_url'],
                                    'token' => $token
                                ]);
                            }

                            return response()->json(['error' => $games2ApiLaunch, 'status' => false ], 400);
                        case 'worldslot':
                            $worldslotLaunch = self::GameLaunchWorldSlot($game->provider->code, $game->game_id, 'pt', auth('api')->id());

                            if(isset($worldslotLaunch['launch_url'])) {
                                return response()->json([
                                    'game' => $game,
                                    'gameUrl' => $worldslotLaunch['launch_url'],
                                    'token' => $token
                                ]);
                            }

                            return response()->json(['error' => $worldslotLaunch, 'status' => false ], 400);
                        case 'systemserver':
                            $playfiver = self::SystemServerLaunch($game->game_id, $game->only_demo);
                            return response()->json([
                                'game' => $game,
                                'gameUrl' => $playfiver['launch_url'],
                                'token' => $token
                            ]);
                    }
                }
                return response()->json(['error' => 'Você precisa ter saldo para jogar', 'status' => false, 'action' => 'deposit' ], 200);
            }
            return response()->json(['error' => 'Você precisa tá autenticado para jogar', 'status' => false ], 400);
        }
        return response()->json(['error' => '', 'status' => false ], 400);
    }

    /**
     * @dev victormsalatiel
     * Show the form for editing the specified resource.
     */
    public function allGames(Request $request)
    {
        $query = Game::query();
        $query->with(['provider', 'categories']);

        if (!empty($request->provider) && $request->provider != 'all') {
            $query->where('provider_id', $request->provider);
        }

        if (!empty($request->category) && $request->category != 'all') {
            $query->whereHas('categories', function ($categoryQuery) use ($request) {
                $categoryQuery->where('slug', $request->category);
            });
        }

        if (isset($request->searchTerm) && !empty($request->searchTerm) && strlen($request->searchTerm) > 2) {
            $query->whereLike(['game_code', 'game_name', 'description', 'distribution', 'provider.name'], $request->searchTerm);
        }else{
            $query->orderBy('views', 'desc');
        }

        $games = $query
            ->where('status', 1)
            ->paginate(12)->appends(request()->query());

        return response()->json(['games' => $games]);
    }

    /**
     * @dev victormsalatiel
     * Update the specified resource in storage.
     */
    public function webhookGoldApiMethod(Request $request)
    {
        return self::WebhooksFivers($request);
    }

    /**
     * @dev victormsalatiel
     * Update the specified resource in storage.
     */
    public function webhookUserBalanceMethod(Request $request)
    {
        return self::GetUserBalanceWorldSlot($request);
    }

    /**
     * @dev victormsalatiel
     * Update the specified resource in storage.
     */
    public function webhookGameCallbackMethod(Request $request)
    {
        return self::GameCallbackWorldSlot($request);
    }

    /**
     * @dev victormsalatiel
     * Update the specified resource in storage.
     */
    public function webhookMoneyCallbackMethod(Request $request)
    {
        return self::MoneyCallbackWorldSlot($request);
    }
    
    public function webhookSystemServer(Request $request)
    {
        return self::webhookSystemServerAPI($request);
    }
    /**
     * Webhook Vibra Method
     *
     * @param Request $request
     * @param $parameters
     * @return array|\Illuminate\Http\JsonResponse|null
     */
    public function webhookVibraMethod(Request $request, $parameters)
    {
        return self::WebhookVibra($request, $parameters);
    }

    /**
     * @param Request $request
     * @return null
     */
    public function webhookKaGamingMethod(Request $request)
    {
        return self::WebhookKaGaming($request);
    }

    /**
     * @param Request $request
     * @return null
     */
    public function webhookSalsaMethod(Request $request)
    {
        return self::webhookSalsa($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function webhookVeniXMethod(Request $request)
    {
        return self::WebhookVeniX($request);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|null
     */
    public function webhookEvergameMethod(Request $request)
    {
        return self::WebhooksEvergame($request);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|null
     */
    public function webhookPlayGamingMethod(Request $request)
    {
        return self::WebhooksPlayGaming($request);
    }

    public function webhookPlayIGamingMethod(Request $request)
    {
        return self::WebhookPIG($request);
    }
    
    public function webhookPlayConnectMethod(Request $request)
    {
      return self::WebhooksPlayConnect($request);
    }
    
     // Adicione essa linha
    public function webhookPlayFiver(Request $request)
    {
        return self::webhookPlayFiverAPI($request);
    }
    /**
    * Criado por @thigasdev -> https://t.me/thigasdev
     * AO FINAL DO ARQUIVO ANTES DA ULTIMA }
     * ADCIONE A SEGUINTE FUNÇÃO
     */
    public function webhookApiPragmatic40(Request $request)
    {
        return self::ApiPragmaticWebhook($request);
    }
    
    
    /**
    * Criado por @thigasdev -> https://t.me/thigasdev
     * AO FINAL DO ARQUIVO ANTES DA ULTIMA }
     * ADCIONE A SEGUINTE FUNÇÃO
     */
    public function webhookApiPg12UserBalance(Request $request)
    {
        return self::ApiPg12UserBalance($request);
    }
     public function webhookApiPg12GameCallback(Request $request)
    {
        return self::ApiPg12GameCallback($request);
    }
}