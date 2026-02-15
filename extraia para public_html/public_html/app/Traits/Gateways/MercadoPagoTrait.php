<?php

namespace App\Traits\Gateways;

use App\Models\AffiliateHistory;
use App\Models\Deposit;
use App\Models\GamesKey;
use App\Models\Gateway;
use App\Models\Setting;
use App\Models\SuitPayPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewDepositNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Core as Helper;
use Illuminate\Support\Facades\Log;

trait MercadoPagoTrait
{
    private static $uriMP;
    private static $apiKey;
    private static $publicKey;

    /**
     * Gera as credenciais do Mercado Pago
     * @return array
     */
    private static function generateCredentialsMP()
    {
        $gateway = \App\Models\Gateway::first();
        if (!empty($gateway) && $gateway->mp_is_enable) {
            self::$apiKey = $gateway->mp_access_token;
            self::$publicKey = $gateway->mp_public_key;
            self::$uriMP = $gateway->mp_sandbox ? 'https://api.mercadopago.com/v1/' : 'https://api.mercadopago.com/v1/';
            return true;
        }
        return false;
    }

    /**
     * @param $idTransaction
     * @param $amount
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     * @return void
     */
    private static function generateDepositMP($idTransaction, $amount)
    {
        $userId = auth('api')->user()->id;
        $wallet = Wallet::where('user_id', $userId)->first();

        Deposit::create([
            'payment_id'=> $idTransaction,
            'user_id'   => $userId,
            'amount'    => $amount,
            'type'      => 'pix',
            'currency'  => $wallet->currency,
            'symbol'    => $wallet->symbol,
            'status'    => 0
        ]);
    }

    /**
     * @param $idTransaction
     * @param $amount
     * @dev victormsalatiel - Corra de golpista, me chame no instagram
     * @return void
     */
    private static function generateTransactionMP($idTransaction, $amount)
    {
        $setting = Helper::getSetting();

        Transaction::create([
            'payment_id' => $idTransaction,
            'user_id' => auth('api')->user()->id,
            'payment_method' => 'pix',
            'price' => $amount,
            'currency' => $setting->currency_code,
            'status' => 0
        ]);
    }

    /**
     * Cria um pagamento PIX via Mercado Pago
     * @param $request
     * @return array
     */
    public static function requestQrcodeMP($request)
    {
        if (self::generateCredentialsMP()) {
            $setting = \Helper::getSetting();
            $rules = [
                'amount' => ['required', 'min:' . $setting->min_deposit, 'max:' . $setting->max_deposit],
                'cpf' => ['required', 'max:255'],
            ];

            $validator = \Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::$apiKey,
                'Content-Type' => 'application/json',
            ])->post(self::$uriMP . 'payments', [
                'transaction_amount' => floatval($request->amount),
                'description' => 'Depósito',
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => auth('api')->user()->email,
                    'first_name' => auth('api')->user()->name,
                    'last_name' => auth('api')->user()->name,
                    'identification' => [
                        'type' => 'CPF',
                        'number' => \Helper::soNumero($request->cpf)
                    ]
                ],
                'notification_url' => url('/mercadopago/callback')
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Gera a transação
                $transaction = Transaction::create([
                    'payment_id' => $responseData['id'],
                    'user_id' => auth('api')->user()->id,
                    'payment_method' => 'pix',
                    'price' => $request->amount,
                    'currency' => $setting->currency_code,
                    'status' => 0
                ]);

                // Gera o depósito
                Deposit::create([
                    'payment_id' => $responseData['id'],
                    'user_id' => auth('api')->user()->id,
                    'amount' => $request->amount,
                    'type' => 'deposit',
                    'status' => 0
                ]);

                return [
                    'status' => true,
                    'idTransaction' => $responseData['id'],
                    'qrcode' => $responseData['point_of_interaction']['transaction_data']['qr_code']
                ];
            }

            return [
                'status' => false,
                'message' => 'Erro ao gerar pagamento'
            ];
        }

        return [
            'status' => false,
            'message' => 'Credenciais inválidas ou gateway desativado'
        ];
    }

    /**
     * Cria um pagamento com cartão de crédito via Mercado Pago
     * @param $request
     * @return array
     */
    public static function requestCreditCardMP($request)
    {
        if (self::generateCredentialsMP()) {
            $gateway = \App\Models\Gateway::first();
            if (empty($gateway) || !$gateway->mp_credit_card_enable) {
                return [
                    'status' => false,
                    'message' => 'Pagamento com cartão está desativado para o gateway Mercado Pago'
                ];
            }

            $setting = \Helper::getSetting();
            $rules = [
                'amount' => ['required', 'min:' . $setting->min_deposit, 'max:' . $setting->max_deposit],
                'cpf' => ['required', 'max:255'],
                'credit_card' => ['required', 'array'],
                'credit_card.number' => ['required', 'string'],
                'credit_card.name' => ['required', 'string'],
                'credit_card.expiry' => ['required', 'string'],
                'credit_card.cvc' => ['required', 'string'],
            ];

            $validator = \Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            // Primeiro, cria um token do cartão
            $tokenResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::$apiKey,
                'Content-Type' => 'application/json',
            ])->post(self::$uriMP . 'card_tokens', [
                'card_number' => $request->credit_card['number'],
                'cardholder' => [
                    'name' => $request->credit_card['name']
                ],
                'expiration_month' => substr($request->credit_card['expiry'], 0, 2),
                'expiration_year' => '20' . substr($request->credit_card['expiry'], 3, 2),
                'security_code' => $request->credit_card['cvc']
            ]);

            if (!$tokenResponse->successful()) {
                return [
                    'status' => false,
                    'message' => 'Erro ao processar cartão'
                ];
            }

            $token = $tokenResponse->json()['id'];

            // Agora, cria o pagamento com o token
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::$apiKey,
                'Content-Type' => 'application/json',
            ])->post(self::$uriMP . 'payments', [
                'transaction_amount' => floatval($request->amount),
                'token' => $token,
                'description' => 'Depósito',
                'installments' => 1,
                'payment_method_id' => 'visa',
                'payer' => [
                    'email' => auth('api')->user()->email,
                    'first_name' => auth('api')->user()->name,
                    'last_name' => auth('api')->user()->name,
                    'identification' => [
                        'type' => 'CPF',
                        'number' => \Helper::soNumero($request->cpf)
                    ]
                ],
                'notification_url' => url('/mercadopago/callback')
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Gera a transação
                $transaction = Transaction::create([
                    'payment_id' => $responseData['id'],
                    'user_id' => auth('api')->user()->id,
                    'payment_method' => 'credit_card',
                    'price' => $request->amount,
                    'currency' => $setting->currency_code,
                    'status' => 0
                ]);

                // Gera o depósito
                Deposit::create([
                    'payment_id' => $responseData['id'],
                    'user_id' => auth('api')->user()->id,
                    'amount' => $request->amount,
                    'type' => 'deposit',
                    'status' => 0
                ]);

                return [
                    'status' => true,
                    'idTransaction' => $responseData['id'],
                    'redirect_url' => $responseData['point_of_interaction']['transaction_data']['ticket_url']
                ];
            }

            return [
                'status' => false,
                'message' => 'Erro ao processar pagamento'
            ];
        }

        return [
            'status' => false,
            'message' => 'Credenciais inválidas ou gateway desativado'
        ];
    }

    /**
     * Consulta o status da transação
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function consultStatusTransactionMP($request)
    {
        if (self::generateCredentialsMP()) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::$apiKey,
                'Content-Type' => 'application/json',
            ])->get(self::$uriMP . 'payments/' . $request->idTransaction);

            if ($response->successful()) {
                $responseData = $response->json();

                if ($responseData['status'] === 'approved') {
                    if (self::finalizePaymentMP($request->idTransaction)) {
                        return response()->json(['status' => 'PAID']);
                    }
                }

                return response()->json(['status' => $responseData['status']]);
            }
        }

        return response()->json(['status' => 'ERROR']);
    }

    /**
     * Finaliza o pagamento
     * @param $idTransaction
     * @return bool
     */
    private static function finalizePaymentMP($idTransaction): bool
    {
        $transaction = Transaction::where('payment_id', $idTransaction)->where('status', 0)->first();
        $setting = \Helper::getSetting();

        if (!empty($transaction)) {
            $user = User::find($transaction->user_id);
            $wallet = Wallet::where('user_id', $transaction->user_id)->first();

            if (!empty($wallet)) {
                if (strtolower($user->wallet->currency) == strtolower($setting->currency_code)) {
                    $wallet->increment('balance', intval($transaction->price));
                    $wallet->update(['active' => 1]);
                } else {
                    $wallet->update(['active' => 0]);
                    $currency = \App\Models\Currency::where('code', strtoupper($setting->currency_code))->first();

                    Wallet::create([
                        'user_id' => $user->id,
                        'balance' => $transaction->price,
                        'currency' => $currency->code,
                        'symbol' => $currency->symbol,
                        'active' => 1
                    ]);
                }

                $transaction->update(['status' => 1]);
                return true;
            }
        }

        return false;
    }

    public static function pixCashOutMP($params)
    {

    }

}
