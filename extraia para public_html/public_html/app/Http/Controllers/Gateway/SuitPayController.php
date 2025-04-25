<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\AffiliateWithdraw;
use App\Models\SuitPayPayment;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Traits\Gateways\SuitpayTrait;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

class SuitPayController extends Controller
{
    use SuitpayTrait;


    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    public function callbackMethodPayment(Request $request)
    {
        $data = $request->all();
        \DB::table('debug')->insert(['text' => json_encode($request->all())]);

        return response()->json([], 200);
    }

    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    public function callbackMethod(Request $request)
    {
    $clientIP = $request->header('X-Forwarded-For');

    if (!$clientIP) {
        $clientIP = $request->ip();
    }
    /**
     * Callback protegido por @vinixxxxxxxxxxxxxxxxxxxxx
     *
     */
    $allowedIPs = ['162.243.162.250', '192.34.62.86', '137.184.60.127', '159.223.100.252', '157.245.93.131', '208.68.39.149'];

    if (!in_array($clientIP, $allowedIPs)) {
        return response()->json(['error' => 'HOJEEE NÃOOOO !!!.'], 403);
    }

    $data = $request->all();


        if (isset($data['idTransaction']) && $data['typeTransaction'] == 'PIX') {
            if ($data['statusTransaction'] == "PAID_OUT" || $data['statusTransaction'] == "PAYMENT_ACCEPT") {
                if (self::finalizePayment($data['idTransaction'])) {
                    
                    return response()->json([], 200);
                }
            }
        }
    }

    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    public function getQRCodePix(Request $request)
    {
        return self::requestQrcode($request);
    }

    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    public function consultStatusTransactionPix(Request $request)
    {
        return self::consultStatusTransaction($request);
    }

    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    public function confirmWithdrawalUser($id)
    {
        $withdrawal = Withdrawal::find($id);
        if(!empty($withdrawal)) {
            $suitpayment = SuitPayPayment::create([
                'withdrawal_id' => $withdrawal->id,
                'user_id'       => $withdrawal->user_id,
                'pix_key'       => $withdrawal->pix_key,
                'pix_type'      => $withdrawal->pix_type,
                'amount'        => $withdrawal->amount,
                'observation'   => 'suitpay',
            ]);

            if($suitpayment) {
                $parm = [
                    'pix_key'           => $withdrawal->pix_key,
                    'pix_type'          => $withdrawal->pix_type,
                    'amount'            => $withdrawal->amount,
                    'suitpayment_id'    => $suitpayment->id
                ];

                $resp = self::pixCashOut($parm);

                if($resp) {
                    $withdrawal->update(['status' => 1]);
                    Notification::make()
                        ->title('Saque solicitado')
                        ->body('Saque solicitado com sucesso')
                        ->success()
                        ->send();

                    return back();
                }else{
                    Notification::make()
                        ->title('Erro no saque')
                        ->body('Erro ao solicitar o saque')
                        ->danger()
                        ->send();

                    return back();
                }
            }
        }
    }

    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    public function confirmWithdrawalAffiliate($id)
    {
        $withdrawal = AffiliateWithdraw::find($id);

        if(!empty($withdrawal)) {
            $suitpayment = SuitPayPayment::create([
                'withdrawal_id' => $withdrawal->id,
                'user_id'       => $withdrawal->user_id,
                'pix_key'       => $withdrawal->pix_key,
                'pix_type'      => $withdrawal->pix_type,
                'amount'        => $withdrawal->amount,
                'observation'   => 'suitpay',
            ]);

            if($suitpayment) {
                $parm = [
                    'pix_key'           => $withdrawal->pix_key,
                    'pix_type'          => $withdrawal->pix_type,
                    'amount'            => $withdrawal->amount,
                    'suitpayment_id'    => $suitpayment->id
                ];

                $resp = self::pixCashOut($parm);

                if($resp) {
                    $withdrawal->update(['status' => 1]);
                    Notification::make()
                        ->title('Saque solicitado')
                        ->body('Saque solicitado com sucesso')
                        ->success()
                        ->send();

                    return back();
                }else{
                    Notification::make()
                        ->title('Erro no saque')
                        ->body('Erro ao solicitar o saque')
                        ->danger()
                        ->send();

                    return back();
                }
            }
        }
    }

    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    public function withdrawalFromModal($id, $action)
    {
        if($action == 'user') {
            return $this->confirmWithdrawalUser($id);
        }

        if($action == 'affiliate') {
            return $this->confirmWithdrawalAffiliate($id);
        }


    }
    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    public function cancelWithdrawalFromModal($id, $action)
    {
        if($action == 'user') {
            return $this->cancelWithdrawalUser($id);
        }

        if($action == 'affiliate') {
            return $this->cancelWithdrawalAffiliate($id);
        }
    }

    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    private function cancelWithdrawalAffiliate($id)
    {
        $withdrawal = AffiliateWithdraw::find($id);
        if(!empty($withdrawal)) {
            $wallet = Wallet::where('user_id', $withdrawal->user_id)
                ->where('currency', $withdrawal->currency)
                ->first();

            if(!empty($wallet)) {
                $wallet->increment('refer_rewards', $withdrawal->amount);

                $withdrawal->update(['status' => 2]);
                Notification::make()
                    ->title('Saque cancelado')
                    ->body('Saque cancelado com sucesso')
                    ->success()
                    ->send();

                return back();
            }
            return back();
        }
        return back();
    }

    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    private function cancelWithdrawalUser($id)
    {
        $withdrawal = Withdrawal::find($id);
        if(!empty($withdrawal)) {
            $wallet = Wallet::where('user_id', $withdrawal->user_id)
                ->where('currency', $withdrawal->currency)
                ->first();

            if(!empty($wallet)) {
                $wallet->increment('balance_withdrawal', $withdrawal->amount);

                $withdrawal->update(['status' => 2]);
                Notification::make()
                    ->title('Saque cancelado')
                    ->body('Saque cancelado com sucesso')
                    ->success()
                    ->send();

                return back();
            }
            return back();
        }
        return back();
    }
}
