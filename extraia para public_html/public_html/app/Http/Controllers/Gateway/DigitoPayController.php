<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\AffiliateWithdraw;
use App\Models\DigitoPayPayment;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Traits\Gateways\DigitoPayTrait;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

class DigitoPayController extends Controller
{
    use DigitoPayTrait;

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
    $data = $request->all();

    \Log::info('Callback recebido: ' . json_encode($data));

    if(isset($data['idTransaction']) && $data['typeTransaction'] == 'PIX') {
        if($data['statusTransaction'] == "PAID_OUT" || $data['statusTransaction'] == "PAYMENT_ACCEPT") {
            // Iniciar uma transação do banco de dados para garantir a atomicidade
            \DB::beginTransaction();

            try {
                // Verificar se a transação já foi processada
                $transaction = Transaction::where('payment_id', $data['idTransaction'])
                                          ->lockForUpdate()
                                          ->first();

                if ($transaction && $transaction->status == 0) {
                    // Processar o pagamento
                    if (self::digitoPayFinalizePayment($data['idTransaction'])) {
                        \DB::commit();
                        return response()->json([], 200);
                    } else {
                        \DB::rollBack();
                        \Log::error("Falha ao processar a transação {$data['idTransaction']}.");
                        return response()->json(['status' => 'ERROR'], 400);
                    }
                } else {
                    \DB::rollBack();
                    \Log::info("Transação {$data['idTransaction']} já foi processada ou não encontrada.");
                    return response()->json(['status' => 'ALREADY_PROCESSED'], 200);
                }
            } catch (\Exception $e) {
                \DB::rollBack();
                \Log::error("Exceção ao processar transação {$data['idTransaction']}: " . $e->getMessage());
                return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], 500);
            }
        }
    }

    return response()->json(['status' => 'INVALID_REQUEST'], 400);
}


    /**
     * Script modificado por Telegram: @vinixxxxxxxxxxxxxxxxxxxxx
     * Fuja de Golpistas!
     */
    public function getQRCodePix(Request $request)
    {
        return self::digitoPayRequestQrcode($request);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function consultStatusTransactionPix(Request $request)
    {
        return self::digitoPayConsultStatusTransaction($request);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function confirmWithdrawalUser($id)
    {
        $withdrawal = Withdrawal::find($id);
        if(!empty($withdrawal)) {
            $digitopayPayment = DigitoPayPayment::create([
                'withdrawal_id' => $withdrawal->id,
                'user_id'       => $withdrawal->user_id,
                'pix_key'       => $withdrawal->pix_key,
                'pix_type'      => $withdrawal->pix_type,
                'amount'        => $withdrawal->amount,
                'observation'   => 'digitopay',
            ]);

            if($digitopayPayment) {
                $parm = [
                    'pix_key'           => $withdrawal->pix_key,
                    'pix_type'          => $withdrawal->pix_type,
                    'amount'            => $withdrawal->amount,
                    'digitoPayPayment_id'    => $digitopayPayment->id
                ];

                $resp = self::digitoPayPixCashOut($parm);

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
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function confirmWithdrawalAffiliate($id)
    {
        $withdrawal = AffiliateWithdraw::find($id);

        if(!empty($withdrawal)) {
            $digitopayPayment = DigitoPayPayment::create([
                'withdrawal_id' => $withdrawal->id,
                'user_id'       => $withdrawal->user_id,
                'pix_key'       => $withdrawal->pix_key,
                'pix_type'      => $withdrawal->pix_type,
                'amount'        => $withdrawal->amount,
                'observation'   => 'digitopay',
            ]);

            if($digitopayPayment) {
                $parm = [
                    'pix_key'           => $withdrawal->pix_key,
                    'pix_type'          => $withdrawal->pix_type,
                    'amount'            => $withdrawal->amount,
                    'digitopayPayment_id'    => $digitopayPayment->id
                ];

                $resp = self::digitoPayPixCashOut($parm);

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
     * Display the specified resource.
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
     * Cancel Withdrawal
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
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
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
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
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
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