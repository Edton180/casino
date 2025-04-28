<?php

namespace App\Traits\Gateways;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait PayPalTrait
{
    private static $uriPayPal;
    private static $clientId;
    private static $clientSecret;
    private static $accessToken;

    /**
     * Gera as credenciais do PayPal
     * @return array
     */
    private static function generateCredentialsPayPal()
    {
        $gateway = \App\Models\Gateway::first();
        if (!empty($gateway) && $gateway->paypal_is_enable) {
            self::$clientId = $gateway->paypal_client_id;
            self::$clientSecret = $gateway->paypal_client_secret;
            self::$uriPayPal = $gateway->paypal_sandbox ? 'https://api-m.sandbox.paypal.com/' : 'https://api-m.paypal.com/';
            return true;
        }
        return false;
    }

    /**
     * Obtém o token de acesso do PayPal
     * @return bool
     */
    private static function getPayPalAccessToken()
    {
        if (self::generateCredentialsPayPal()) {
            $response = Http::withBasicAuth(self::$clientId, self::$clientSecret)
                ->asForm()
                ->post(self::$uriPayPal . 'v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                self::$accessToken = $response->json()['access_token'];
                return true;
            }
        }
        return false;
    }

    /**
     * Cria um pagamento PIX via PayPal
     * @param $request
     * @return array
     */
    public static function requestQrcodePayPal($request)
    {
        if (self::getPayPalAccessToken()) {
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
                'Authorization' => 'Bearer ' . self::$accessToken,
                'Content-Type' => 'application/json',
            ])->post(self::$uriPayPal . 'v1/payments/payment', [
                'intent' => 'sale',
                'payer' => [
                    'payment_method' => 'pix'
                ],
                'transactions' => [
                    [
                        'amount' => [
                            'total' => floatval($request->amount),
                            'currency' => 'BRL'
                        ],
                        'description' => 'Depósito'
                    ]
                ],
                'redirect_urls' => [
                    'return_url' => url('/paypal/success'),
                    'cancel_url' => url('/paypal/cancel')
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
                    'qrcode' => $responseData['links'][1]['href']
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
     * Cria um pagamento com cartão de crédito via PayPal
     * @param $request
     * @return array
     */
    public static function requestCreditCardPayPal($request)
    {
        if (self::getPayPalAccessToken()) {
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

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::$accessToken,
                'Content-Type' => 'application/json',
            ])->post(self::$uriPayPal . 'v1/payments/payment', [
                'intent' => 'sale',
                'payer' => [
                    'payment_method' => 'credit_card',
                    'funding_instruments' => [
                        [
                            'credit_card' => [
                                'number' => $request->credit_card['number'],
                                'type' => 'visa',
                                'expire_month' => substr($request->credit_card['expiry'], 0, 2),
                                'expire_year' => '20' . substr($request->credit_card['expiry'], 3, 2),
                                'cvv2' => $request->credit_card['cvc'],
                                'first_name' => auth('api')->user()->name,
                                'last_name' => auth('api')->user()->name
                            ]
                        ]
                    ]
                ],
                'transactions' => [
                    [
                        'amount' => [
                            'total' => floatval($request->amount),
                            'currency' => 'BRL'
                        ],
                        'description' => 'Depósito'
                    ]
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
                    'redirect_url' => $responseData['links'][1]['href']
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
    public static function consultStatusTransactionPayPal($request)
    {
        if (self::getPayPalAccessToken()) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::$accessToken,
                'Content-Type' => 'application/json',
            ])->get(self::$uriPayPal . 'v1/payments/payment/' . $request->idTransaction);

            if ($response->successful()) {
                $responseData = $response->json();

                if ($responseData['state'] === 'approved') {
                    if (self::finalizePaymentPayPal($request->idTransaction)) {
                        return response()->json(['status' => 'PAID']);
                    }
                }

                return response()->json(['status' => $responseData['state']]);
            }
        }

        return response()->json(['status' => 'ERROR']);
    }

    /**
     * Finaliza o pagamento
     * @param $idTransaction
     * @return bool
     */
    private static function finalizePaymentPayPal($idTransaction): bool
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