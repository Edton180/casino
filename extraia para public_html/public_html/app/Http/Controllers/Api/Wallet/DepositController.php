<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Traits\Gateways\AsaasTrait;
use App\Traits\Gateways\MercadoPagoTrait;
use App\Traits\Gateways\PayPalTrait;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    use AsaasTrait, MercadoPagoTrait, PayPalTrait;

    /**
     * @param Request $request
     * @return array|false[]
     */
    public function submitPayment(Request $request)
    {
        \Log::info($request->gateway);
        \Log::info($request->payment_method);

        switch ($request->gateway) {
            case 'asaas':
                if ($request->payment_method === 'credit_card') {
                    return self::requestCreditCardAsaas($request);
                }
                return self::requestQrcodeAsaas($request);

            case 'mercadopago':
                if ($request->payment_method === 'credit_card') {
                    return self::requestCreditCardMP($request);
                }
                return self::requestQrcodeMP($request);

            case 'paypal':
                if ($request->payment_method === 'credit_card') {
                    return self::requestCreditCardPayPal($request);
                }
                return self::requestQrcodePayPal($request);

            default:
                return ['status' => false, 'message' => 'Gateway não encontrado'];
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function consultStatusTransactionPix(Request $request)
    {
        switch ($request->gateway) {
            case 'asaas':
                return self::consultStatusTransactionAsaas($request);
            case 'mercadopago':
                return self::consultStatusTransactionMP($request);
            case 'paypal':
                return self::consultStatusTransactionPayPal($request);
            default:
                return response()->json(['status' => 'ERROR']);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deposits = Deposit::whereUserId(auth('api')->id())->paginate();
        return response()->json(['deposits' => $deposits], 200);
    }
}
