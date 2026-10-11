<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Setting;
use App\Services\Payments\DepositService;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DepositController extends Controller
{
    public function __construct(
        private DepositService $deposits,
        private PaymentManager $payments,
    ) {
    }

    /**
     * Métodos de pagamento habilitados (Mercado Pago, PagSeguro, Pagar.me, Asaas, Stripe).
     */
    public function methods()
    {
        return response()->json(['methods' => $this->payments->availableMethods()]);
    }

    /**
     * Inicia um depósito via PIX ou cartão de crédito.
     */
    public function submitPayment(Request $request)
    {
        $setting = Setting::first();
        $request->merge([
            'amount' => \Helper::amountPrepare((string) $request->input('amount')),
            'cpf'    => $request->input('cpf') ?: auth('api')->user()->cpf,
        ]);

        $validator = Validator::make($request->all(), [
            'gateway'        => ['required', Rule::in(array_keys(PaymentManager::GATEWAYS))],
            'payment_method' => ['required', Rule::in(['pix', 'credit_card'])],
            'amount'         => ['required', 'numeric', 'min:' . (float) $setting->min_deposit, 'max:' . (float) $setting->max_deposit],
            'cpf'            => [Rule::requiredIf($request->input('gateway') !== 'stripe'), 'nullable', 'cpf_ou_cnpj'],
            'accept_bonus'   => ['nullable', 'boolean'],
        ], [
            'cpf.required'    => 'Informe seu CPF ou CNPJ.',
            'cpf.cpf_ou_cnpj' => 'CPF ou CNPJ inválido.',
            'amount.min'      => 'O valor mínimo de depósito é ' . $setting->min_deposit . '.',
            'amount.max'      => 'O valor máximo de depósito é ' . $setting->max_deposit . '.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $result = $this->deposits->start(
                auth('api')->user(),
                $request->input('gateway'),
                $request->input('payment_method'),
                (float) $request->input('amount'),
                (string) $request->input('cpf'),
                $request->boolean('accept_bonus', true),
            );

            return response()->json($result);
        } catch (PaymentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Consulta o status de um depósito (usado pelo polling da tela do PIX).
     */
    public function status(Request $request)
    {
        $reference = (string) $request->input('idTransaction');
        if ($reference === '') {
            return response()->json(['error' => 'Transação inválida'], 400);
        }

        try {
            $status = $this->deposits->checkStatus(auth('api')->user(), $reference);
        } catch (PaymentException $e) {
            $status = 'PENDING';
        }

        return response()->json(['status' => $status], $status === 'NOT_FOUND' ? 404 : 200);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deposits = Deposit::whereUserId(auth('api')->id())->latest()->paginate();
        return response()->json(['deposits' => $deposits], 200);
    }
}
