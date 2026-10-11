<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Models\AffiliateHistory;
use App\Models\AffiliateWithdraw;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
class AffiliateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $indications    = User::where('inviter', auth('api')->id())->count();
        $walletDefault  = Wallet::where('user_id', auth('api')->id())->first();

        return response()->json([
            'status'        => true,
            'code'          => auth('api')->user()->inviter_code,
            'url'           => config('app.url') . '/register?code='.auth('api')->user()->inviter_code,
            'indications'   => $indications,
            'wallet'        => $walletDefault
        ]);
    }
    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    /**
     * Show the form for creating a new resource.
     */
    public function generateCode()
    {
        $code = $this->gencode();
        $setting = \Helper::getSetting();

        if(!empty($code)) {
            $user = auth('api')->user();
            \DB::table('model_has_roles')->updateOrInsert(
                [
                    'role_id' => 2,
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                ],
            );

            if(auth('api')->user()->update(['inviter_code' => $code, 'affiliate_revenue_share' => $setting->revshare_percentage])) {
                return response()->json(['status' => true, 'message' => trans('Successfully generated code')]);
            }

            return response()->json(['error' => ''], 400);
        }

        return response()->json(['error' => ''], 400);
    }
    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    /**
     * @return null
     */
    private function gencode() {
        $code = \Helper::generateCode(10);

        $checkCode = User::where('inviter_code', $code)->first();
        if(empty($checkCode)) {
            return $code;
        }

        return $this->gencode();
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Store a newly created resource in storage.
     */
         /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
public function makeRequest(Request $request)
{
    // Obtendo as configurações de saque do usuário
    $settings = Setting::where('id', 1)->first();

    // Verificando se as configurações foram encontradas e se os limites de saque foram definidos
    if ($settings) {
        $withdrawalLimit = $settings->withdrawal_limit;
        $withdrawalPeriod = $settings->withdrawal_period;
    } else {
        // Caso as configurações não tenham sido encontradas, defina os valores padrão ou trate conforme necessário
        $withdrawalLimit = null;
        $withdrawalPeriod = null;
    }



    $rules = [
        'amount' => ['required', 'numeric', 'max:'.$settings->max_withdrawal],
    ];

    switch ($request->pix_type) {
        case 'document':
            $rules['pix_key'] = 'cpf_ou_cnpj';
            break;
        case 'email':
            $rules['pix_key'] = 'email';
            break;
        default:
            $rules['pix_key'] = 'required';
            break;
    }

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }
    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    // Verificando se o usuário tem saldo suficiente para o saque
    $cashback = auth('api')->user()->wallet->cashback;
 if (floatval($cashback) >= floatval($request->amount) && floatval($request->amount) > 0) {
        // Criando o registro de saque
        AffiliateWithdraw::create([
            'user_id'   => auth('api')->id(),
            'amount'    => $request->amount,
            'currency'  => 'CASHBACK',
            'symbol'    => 'R$',
        ]);

        // Decrementando o saldo do usuário
        auth('api')->user()->wallet->decrement('cashback', $request->amount);
        auth('api')->user()->wallet->increment('balance_bonus', $request->amount);
        auth('api')->user()->wallet->update(['cashbackliquido' => 0]);

        // Retornando mensagem de sucesso
        return response()->json(['message' => 'CashBack Ativado!'], 200);
    }
    // Retornando mensagem de erro se não houver saldo suficiente
    return response()->json(['message' => trans('Você já ativou o CashBack!')], 400);
}
    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
public function cupom(Request $request)
{
    // Obter as configurações
    $settings = Setting::where('id', 1)->first();

    // Verificar se o cupom fornecido pelo usuário é o mesmo que está nas configurações
    if ($settings->cupom !== $request->input('promoCode')) {
        return response()->json(['message' => 'Código promocional inválido.'], 404);
    }
    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    // Obter o usuário autenticado
    $user = auth('api')->user();

    // Verificar se o usuário já ativou o cupom
    if ($user->wallet->cupom_ativado) {
        return response()->json(['message' => 'Você já usou um cupom.'], 403);
    }
    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    // Adicionar o valor do cupom ao saldo bônus do usuário
    $amount = $settings->cupomvalor;
    $user->wallet->increment('balance_bonus', $amount);

    // Atualizar o status do cupom_ativado para true
    $user->wallet->update(['cupom_ativado' => true]);

    return response()->json(['message' => 'Código promocional ativado com sucesso.', 'balance_bonus' => $user->wallet->balance_bonus], 200);
}
    
}
