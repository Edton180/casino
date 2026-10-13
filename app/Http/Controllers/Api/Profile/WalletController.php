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
        $wallet = Wallet::whereUserId(auth('api')->id())->where('active', 1)->first()
            ?? Wallet::whereUserId(auth('api')->id())->first();
        return response()->json(['wallet' => $this->present($wallet)], 200);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function myWallet()
    {
        $wallets = Wallet::whereUserId(auth('api')->id())->get()->map(fn (Wallet $wallet) => $this->present($wallet));
        return response()->json(['wallets' => $wallets], 200);
    }

    /**
     * Conta de influencer joga com o "saldo de influencer" (os jogos debitam e creditam nele):
     * é esse saldo que o site mostra no topo. O saldo real continua em real_total_balance.
     */
    private function present(?Wallet $wallet): ?array
    {
        if (!$wallet) {
            return null;
        }
        $data = $wallet->toArray();
        $user = auth('api')->user();
        if ($user && $user->is_demo_agent) {
            $data['real_total_balance'] = $data['total_balance'];
            $data['total_balance'] = round((float) $wallet->balance_demo, 2);
            $data['total_balance_without_bonus'] = $data['total_balance'];
            $data['is_influencer'] = true;
        }

        return $data;
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function setWalletActive($id)
    {
        // só carteiras do próprio usuário (antes qualquer ID era aceito e os dados da carteira voltavam na resposta)
        $wallet = Wallet::whereUserId(auth('api')->id())->find($id);
        if (empty($wallet)) {
            return response()->json(['error' => 'Carteira não encontrada'], 404);
        }

        Wallet::whereUserId(auth('api')->id())->where('active', 1)->whereKeyNot($wallet->getKey())->update(['active' => 0]);
        $wallet->update(['active' => 1]);

        return response()->json(['wallet' => $this->present($wallet)], 200);
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

            if(!in_array($request->type, ['pix', 'bank'], true)) {
                return response()->json(['error' => 'Tipo de saque inválido'], 400);
            }

            // Limite de saques por período (Configurações → Limites; 0 ou vazio = sem limite)
            $limit = (int) ($setting->withdrawal_limit ?? 0);
            if ($limit > 0) {
                $since = match ($setting->withdrawal_period) {
                    'weekly'  => now()->startOfWeek(),
                    'monthly' => now()->startOfMonth(),
                    'yearly'  => now()->startOfYear(),
                    default   => now()->startOfDay(),
                };
                $count = Withdrawal::where('user_id', auth('api')->id())
                    ->where('status', '!=', 2) // cancelados não contam
                    ->where('created_at', '>=', $since)
                    ->count();

                if ($count >= $limit) {
                    $periods = ['daily' => 'por dia', 'weekly' => 'por semana', 'monthly' => 'por mês', 'yearly' => 'por ano'];
                    return response()->json(['error' => 'Limite de ' . $limit . ' saque(s) ' . ($periods[$setting->withdrawal_period] ?? 'por dia') . ' atingido.'], 400);
                }
            }

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
                $amount = (float) \Helper::amountPrepare($request->amount);

                // checagem de saldo e débito na mesma transação (evita sacar duas vezes o mesmo saldo)
                $withdrawal = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $amount) {
                    $wallet = Wallet::where('user_id', auth('api')->id())->lockForUpdate()->first();
                    if (!$wallet || $amount > (float) $wallet->balance_withdrawal) {
                        return null;
                    }

                    $data = [
                        'user_id'   => auth('api')->id(),
                        'amount'    => $amount,
                        'type'      => $request->type,
                        'currency'  => $wallet->currency ?: 'BRL',
                        'symbol'    => $wallet->symbol ?: 'R$',
                        'status'    => 0,
                    ];

                    if ($request->type === 'pix') {
                        $data['pix_key']  = $request->pix_key;
                        $data['pix_type'] = $request->pix_type;
                    } else {
                        $data['bank_info'] = $request->bank_info;
                    }

                    $withdrawal = Withdrawal::create($data);
                    $wallet->decrement('balance_withdrawal', $amount);
                    $wallet->update(['balance_bonus' => 0]);

                    return $withdrawal;
                });

                if(!$withdrawal) {
                    return response()->json(['error' => 'Você não tem saldo suficiente'], 400);
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Saque realizado com sucesso',
                ], 200);
            }

            return response()->json(['error' => 'Você precisa aceitar os termos'], 400);
        }

        return response()->json(['error' => 'Erro ao realizar o saqu'], 400);
    }


}
