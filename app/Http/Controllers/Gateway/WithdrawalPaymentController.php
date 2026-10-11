<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\AffiliateWithdraw;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\Payments\Gateways\AsaasGateway;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentManager;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Aprovação e cancelamento de saques pelo painel administrativo.
 *
 * - Com "PIX automático" habilitado no Asaas, o saque é enviado via
 *   transferência PIX do Asaas.
 * - Caso contrário o saque é apenas marcado como pago (o administrador faz
 *   o PIX manualmente pelo banco/gateway de sua preferência).
 */
class WithdrawalPaymentController extends Controller
{
    public function approve(string $id, string $action, PaymentManager $payments)
    {
        $withdrawal = $this->find($id, $action);
        if (!$withdrawal || (int) $withdrawal->status !== 0) {
            return $this->notify('Saque não encontrado ou já processado', false);
        }

        /** @var AsaasGateway $asaas */
        $asaas = $payments->get('asaas');

        if ($withdrawal->type === 'pix' && $asaas->supportsPixPayout()) {
            // marca como "em processamento" antes de chamar a API para evitar pagamento duplo
            $locked = $withdrawal->newQuery()->whereKey($withdrawal->id)->where('status', 0)->update(['status' => 3]);
            if (!$locked) {
                return $this->notify('Saque já está sendo processado', false);
            }

            try {
                $transferId = $asaas->pixTransfer(
                    (float) $withdrawal->amount,
                    (string) $withdrawal->pix_key,
                    (string) $withdrawal->pix_type,
                    'Saque #' . $withdrawal->id
                );
            } catch (PaymentException $e) {
                $withdrawal->update(['status' => 0]);
                return $this->notify($e->getMessage(), false);
            }

            $withdrawal->update(['status' => 1, 'payment_id' => $transferId]);
            return $this->notify('PIX de saque enviado pelo Asaas', true);
        }

        $withdrawal->update(['status' => 1]);
        return $this->notify('Saque marcado como pago. Lembre-se de realizar a transferência manualmente.', true);
    }

    public function cancel(string $id, string $action)
    {
        $refunded = DB::transaction(function () use ($id, $action) {
            $withdrawal = $this->find($id, $action, true);
            if (!$withdrawal || (int) $withdrawal->status !== 0) {
                return false;
            }

            $wallet = Wallet::where('user_id', $withdrawal->user_id)->lockForUpdate()->first();
            if (!$wallet) {
                return false;
            }

            // saque de afiliado devolve para comissões; saque comum para a carteira de saque
            $wallet->increment($action === 'affiliate' ? 'refer_rewards' : 'balance_withdrawal', (float) $withdrawal->amount);
            $withdrawal->update(['status' => 2]);

            return true;
        });

        return $this->notify($refunded ? 'Saque cancelado e valor devolvido' : 'Saque não encontrado ou já processado', $refunded);
    }

    private function find(string $id, string $action, bool $lock = false): Withdrawal|AffiliateWithdraw|null
    {
        $query = $action === 'affiliate' ? AffiliateWithdraw::query() : Withdrawal::query();

        return ($lock ? $query->lockForUpdate() : $query)->find($id);
    }

    private function notify(string $message, bool $success)
    {
        $notification = Notification::make()->title($success ? 'Saque' : 'Erro no saque')->body($message);
        ($success ? $notification->success() : $notification->danger())->send();

        return back();
    }
}
