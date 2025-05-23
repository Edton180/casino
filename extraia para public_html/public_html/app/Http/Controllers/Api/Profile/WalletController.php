<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Notifications\NewWithdrawalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;

class WalletController extends Controller
{
    
    
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $wallet = Wallet::whereUserId(auth('api')->id())->where('active', 1)->first();
        return response()->json(['wallet' => $wallet], 200);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function myWallet()
    {
        $wallets = Wallet::whereUserId(auth('api')->id())->get();
        return response()->json(['wallets' => $wallets], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function setWalletActive($id)
    {
        $checkWallet = Wallet::whereUserId(auth('api')->id())->where('active', 1)->first();
        if(!empty($checkWallet)) {
            $checkWallet->update(['active' => 0]);
        }

        $wallet = Wallet::find($id);
        if(!empty($wallet)) {
            $wallet->update(['active' => 1]);
            return response()->json(['wallet' => $wallet], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestWithdrawal(Request $request)
    {
        $setting = Setting::first();

        /// Verificar se é afiliado
        If(auth('api')->check()) {

            if($request->type === 'pix') {
                $rules = [
                    'amount'        => ['required', 'numeric', 'min:'.$setting->min_withdrawal, 'max:'.$setting->max_withdrawal],
                ];

                switch ($request->pix_type) {
                    case 'document':
                        $rules['pix_key'] = 'required|cpf_ou_cnpj';
                        break;
                    case 'email':
                        $rules['pix_key'] = 'required|email';
                        break;
                    default:
                        $rules['pix_key'] = 'required';
                        break;

                }
            }

            if($request->type === 'bank') {
                $rules = [
                    'amount'        => ['required', 'numeric', 'min:'.$setting->min_withdrawal, 'max:'.$setting->max_withdrawal],
                    'bank_info'     => 'required',
                ];
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            if($request->amount < 0) {
                return response()->json(['error' => 'Você tem que solicitar um valor'], 400);
            }


            /// verificar o limite de saque


            if($request->amount > $setting->max_withdrawal) {
                return response()->json(['error' => 'Você excedeu o limite máximo permitido de: '. $setting->max_withdrawal], 400);
            }

            if($request->accept_terms == true) {
                if(floatval($request->amount) > floatval(auth('api')->user()->wallet->balance_withdrawal)) {
                    return response()->json(['error' => 'Você não tem saldo suficiente'], 400);
                }

                $data = [];
                if($request->type === 'pix') {
                    $data = [
                        'user_id'   => auth('api')->user()->id,
                        'amount'    => \Helper::amountPrepare($request->amount),
                        'type'      => $request->type,
                        'pix_key'   => $request->pix_key,
                        'pix_type'  => $request->pix_type,
                        'currency'  => $request->currency,
                        'symbol'    => $request->symbol,
                        'status'    => 0,
                    ];
                }

                if($request->type === 'bank') {
                    $data = [
                        'user_id'   => auth('api')->user()->id,
                        'amount'    => \Helper::amountPrepare($request->amount),
                        'type'      => $request->type,
                        'bank_info' => $request->bank_info,
                        'currency'  => $request->currency,
                        'symbol'    => $request->symbol,
                        'status'    => 0,
                    ];
                }

                $withdrawal = Withdrawal::create($data);

                if($withdrawal) {
                    $wallet = Wallet::where('user_id', auth('api')->id())->first();
                    $wallet->decrement('balance_withdrawal', floatval($request->amount));
                    $wallet->update(['balance_bonus' => 0]);


                    return response()->json([
                        'status' => true,
                        'message' => 'Saque realizado com sucesso',
                    ], 200);

                }
            }

            return response()->json(['error' => 'Você precisa aceitar os termos'], 400);
        }

        return response()->json(['error' => 'Erro ao realizar o saqu'], 400);
    }


}
