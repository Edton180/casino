<?php

namespace App\Traits\Gateways;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait AsaasTrait
{
    private static $uriAsaas;
    private static $apiKey;
    private static $webhookKey;

    /**
     * Gera as credenciais do Asaas
     * @return array
     */
    private static function generateCredentialsAsaas()
    {
        $gateway = \App\Models\Gateway::first();
        if (!empty($gateway) && $gateway->asaas_is_enable) {
            self::$apiKey = $gateway->asaas_api_key;
            self::$webhookKey = $gateway->asaas_webhook_key;
            self::$uriAsaas = $gateway->asaas_sandbox ? 'https://sandbox.asaas.com/api/v3/' : 'https://api.asaas.com/v3/';
            return true;
        }
        return false;
    }

    /**
     * Cria um pagamento PIX via Asaas
     * @param $request
     * @return array
     */
    public static function requestQrcodeAsaas($request)
    {
        if (self::generateCredentialsAsaas()) {
            $setting = \Helper::getSetting();
            $rules = [
                'amount' => ['required', 'max:' . $setting->min_deposit, 'max:' . $setting->max_deposit],
                'cpf' => ['required', 'max:255'],
            ];

            $validator = \Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $response = Http::withHeaders([
                'access_token' => self::$apiKey,
                'Content-Type' => 'application/json',
            ])->post(self::$uriAsaas . 'payments', [
                'customer' => [
                    'name' => auth('api')->user()->name,
                    'cpfCnpj' => \Helper::soNumero($request->cpf),
                    'email' => auth('api')->user()->email,
                    'phone' => \Helper::soNumero(auth('api')->user()->phone),
                ],
                'billingType' => 'PIX',
                'value' => floatval($request->amount),
                'dueDate' => Carbon::now()->addDay()->format('Y-m-d'),
                'description' => 'Depósito',
                'externalReference' => uniqid(),
                'callback' => [
                    'url' => url('/asaas/callback')
                ]
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
                    'qrcode' => $responseData['pixQrCode']
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
     * Cria um pagamento com cartão de crédito via Asaas
     * @param $request
     * @return array
     */
    public static function requestCreditCardAsaas($request)
    {
        if (self::generateCredentialsAsaas()) {
            $setting = \Helper::getSetting();
            $rules = [
                'amount' => ['required', 'max:' . $setting->min_deposit, 'max:' . $setting->max_deposit],
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
                'access_token' => self::$apiKey,
                'Content-Type' => 'application/json',
            ])->post(self::$uriAsaas . 'creditCard/tokenize', [
                'creditCard' => [
                    'holderName' => $request->credit_card['name'],
                    'number' => $request->credit_card['number'],
                    'expiryMonth' => substr($request->credit_card['expiry'], 0, 2),
                    'expiryYear' => '20' . substr($request->credit_card['expiry'], 3, 2),
                    'ccv' => $request->credit_card['cvc']
                ]
            ]);

            if (!$tokenResponse->successful()) {
                return [
                    'status' => false,
                    'message' => 'Erro ao processar cartão'
                ];
            }

            $token = $tokenResponse->json()['creditCardToken'];

            // Agora, cria o pagamento com o token
            $response = Http::withHeaders([
                'access_token' => self::$apiKey,
                'Content-Type' => 'application/json',
            ])->post(self::$uriAsaas . 'payments', [
                'customer' => [
                    'name' => auth('api')->user()->name,
                    'cpfCnpj' => \Helper::soNumero($request->cpf),
                    'email' => auth('api')->user()->email,
                    'phone' => \Helper::soNumero(auth('api')->user()->phone),
                ],
                'billingType' => 'CREDIT_CARD',
                'value' => floatval($request->amount),
                'dueDate' => Carbon::now()->addDay()->format('Y-m-d'),
                'description' => 'Depósito',
                'externalReference' => uniqid(),
                'creditCard' => [
                    'holderName' => $request->credit_card['name'],
                    'number' => $request->credit_card['number'],
                    'expiryMonth' => substr($request->credit_card['expiry'], 0, 2),
                    'expiryYear' => '20' . substr($request->credit_card['expiry'], 3, 2),
                    'ccv' => $request->credit_card['cvc']
                ],
                'creditCardToken' => $token,
                'callback' => [
                    'url' => url('/asaas/callback')
                ]
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
                    'redirect_url' => $responseData['bankSlipUrl']
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
    public static function consultStatusTransactionAsaas($request)
    {
        if (self::generateCredentialsAsaas()) {
            $response = Http::withHeaders([
                'access_token' => self::$apiKey,
                'Content-Type' => 'application/json',
            ])->get(self::$uriAsaas . 'payments/' . $request->idTransaction);

            if ($response->successful()) {
                $responseData = $response->json();

                if ($responseData['status'] === 'CONFIRMED') {
                    if (self::finalizePaymentAsaas($request->idTransaction)) {
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
    private static function finalizePaymentAsaas($idTransaction): bool
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
} 