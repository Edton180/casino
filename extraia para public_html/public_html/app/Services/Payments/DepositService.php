<?php

namespace App\Services\Payments;

use App\Helpers\Core as Helper;
use App\Models\AffiliateHistory;
use App\Models\Deposit;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewDepositNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fluxo de depósito comum a todos os gateways: criação da cobrança,
 * consulta de status e crédito (idempotente) na carteira do usuário.
 */
class DepositService
{
    public function __construct(private PaymentManager $payments)
    {
    }

    /**
     * Cria a cobrança no gateway e registra Transaction + Deposit pendentes.
     */
    public function start(User $user, string $gatewayKey, string $method, float $amount, string $document, bool $acceptBonus): array
    {
        $gateway = $this->payments->get($gatewayKey);
        if (!$gateway->isEnabled()) {
            throw new PaymentException($gateway->label() . ' está desativado.');
        }

        if ($method === 'credit_card' && !$gateway->supportsCreditCard()) {
            throw new PaymentException('Pagamento com cartão não está disponível para ' . $gateway->label() . '.');
        }

        $setting  = Setting::first();
        $wallet   = Wallet::where('user_id', $user->id)->where('active', 1)->first()
            ?? Wallet::where('user_id', $user->id)->first();
        $currency = strtoupper($setting->currency_code ?? 'BRL');

        $charge = new ChargeData(
            user: $user,
            amount: round($amount, 2),
            document: preg_replace('/\D/', '', $document),
            reference: 'dep' . Str::lower(Str::random(29)),
            currency: $currency,
            description: 'Depósito ' . ($setting->software_name ?? config('app.name')),
        );

        $result = $method === 'credit_card'
            ? $gateway->createCreditCard($charge)
            : $gateway->createPix($charge);

        DB::transaction(function () use ($user, $gatewayKey, $method, $charge, $result, $acceptBonus, $currency, $wallet) {
            Transaction::create([
                'payment_id'         => $charge->reference,
                'user_id'            => $user->id,
                'payment_method'     => $method,
                'gateway'            => $gatewayKey,
                'gateway_payment_id' => $result['gateway_payment_id'],
                'price'              => $charge->amount,
                'currency'           => $currency,
                'accept_bonus'       => $acceptBonus,
                'status'             => 0,
            ]);

            Deposit::create([
                'payment_id' => $charge->reference,
                'user_id'    => $user->id,
                'amount'     => $charge->amount,
                'type'       => $method,
                'currency'   => $wallet->currency ?? $currency,
                'symbol'     => $wallet->symbol ?? 'R$',
                'status'     => 0,
            ]);
        });

        return array_filter([
            'status'        => true,
            'gateway'       => $gatewayKey,
            'idTransaction' => $charge->reference,
            'qrcode'        => $result['qrcode'] ?? null,
            'qrcode_image'  => $result['qrcode_image'] ?? null,
            'redirect_url'  => $result['redirect_url'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Status de um depósito do próprio usuário. Consulta o gateway enquanto
     * estiver pendente e credita automaticamente quando for aprovado.
     */
    public function checkStatus(User $user, string $reference): string
    {
        $transaction = Transaction::where('payment_id', $reference)->where('user_id', $user->id)->first();
        if (!$transaction) {
            return 'NOT_FOUND';
        }

        if ((int) $transaction->status === 1) {
            return 'PAID';
        }

        return match ($this->confirm($reference)) {
            PaymentGateway::STATUS_PAID   => 'PAID',
            PaymentGateway::STATUS_FAILED => 'FAILED',
            default                       => 'PENDING',
        };
    }

    /**
     * Reconsulta o gateway e, se o pagamento estiver aprovado, credita o
     * depósito uma única vez. Seguro para ser chamado várias vezes
     * (webhook + polling do frontend).
     */
    public function confirm(string $reference): string
    {
        $transaction = Transaction::where('payment_id', $reference)->first();
        if (!$transaction || empty($transaction->gateway)) {
            return PaymentGateway::STATUS_PENDING;
        }

        if ((int) $transaction->status === 1) {
            return PaymentGateway::STATUS_PAID;
        }

        $remote = $this->payments->get($transaction->gateway)->fetchStatus($transaction);
        if ($remote['status'] !== PaymentGateway::STATUS_PAID) {
            return $remote['status'];
        }

        if ($remote['amount'] !== null && $remote['amount'] + 0.01 < (float) $transaction->price) {
            Log::critical('[payments] Valor pago menor que o valor do depósito', [
                'reference' => $reference,
                'expected'  => $transaction->price,
                'paid'      => $remote['amount'],
            ]);
            return PaymentGateway::STATUS_PENDING;
        }

        $credited = DB::transaction(function () use ($reference) {
            $transaction = Transaction::where('payment_id', $reference)->lockForUpdate()->first();
            if (!$transaction || (int) $transaction->status === 1) {
                return false; // já creditado por outra requisição
            }

            $this->creditWallet($transaction);
            return true;
        });

        if ($credited) {
            $this->notifyAdmins($transaction->fresh());
        }

        return PaymentGateway::STATUS_PAID;
    }

    private function creditWallet(Transaction $transaction): void
    {
        $setting = Setting::first();
        $user    = User::find($transaction->user_id);
        $wallet  = Wallet::where('user_id', $transaction->user_id)->lockForUpdate()->first();
        $amount  = (float) $transaction->price;

        if (!$wallet || !$user) {
            throw new PaymentException('Carteira do usuário não encontrada.');
        }

        // Bônus de primeiro depósito (percentual configurado no painel)
        $isFirstDeposit = Transaction::where('user_id', $user->id)->where('status', 1)->doesntExist();
        if ($isFirstDeposit && $transaction->accept_bonus && (float) $setting->initial_bonus > 0) {
            $bonus = Helper::porcentagem_xn($setting->initial_bonus, $amount);
            $wallet->increment('balance_bonus', $bonus);

            if (!$setting->disable_rollover) {
                $wallet->increment('balance_bonus_rollover', $bonus * (float) $setting->rollover);
            }
        }

        if ($setting->disable_rollover) {
            // sem rollover o depósito já fica disponível para saque
            $wallet->increment('balance_withdrawal', $amount);
        } else {
            $wallet->increment('balance_deposit_rollover', $amount * (float) $setting->rollover_deposit);
            $wallet->increment('balance', $amount);
        }

        Helper::payBonusVip($wallet, $amount);

        $transaction->update(['status' => 1]);
        Deposit::where('payment_id', $transaction->payment_id)->update(['status' => 1]);

        $this->payAffiliateCpa($user, $amount);
    }

    /**
     * CPA do programa de indicação: paga o afiliado quando o indicado atinge
     * o depósito mínimo (baseline) configurado para o afiliado.
     */
    private function payAffiliateCpa(User $user, float $amount): void
    {
        if (empty($user->inviter)) {
            return;
        }

        $history = AffiliateHistory::where('user_id', $user->id)
            ->where('commission_type', 'cpa')
            ->where('status', 0)
            ->lockForUpdate()
            ->first();

        $sponsor = User::find($user->inviter);
        if (!$history || !$sponsor) {
            return;
        }

        $history->increment('deposited_amount', $amount);
        $history->increment('deposited', 1);

        if ((float) $history->deposited_amount < (float) $sponsor->affiliate_baseline) {
            return;
        }

        $sponsorWallet = Wallet::where('user_id', $sponsor->id)->lockForUpdate()->first();
        if (!$sponsorWallet) {
            return;
        }

        $sponsorWallet->increment('refer_rewards', (float) $sponsor->affiliate_cpa);
        $history->update(['status' => 1, 'commission_paid' => $sponsor->affiliate_cpa]);
    }

    private function notifyAdmins(Transaction $transaction): void
    {
        try {
            $user = User::find($transaction->user_id);
            foreach (User::role('admin')->get() as $admin) {
                $admin->notify(new NewDepositNotification($user->name ?? '', $transaction->price));
            }
        } catch (\Throwable $e) {
            // falha de e-mail não pode desfazer o crédito já realizado
            Log::warning('[payments] Falha ao notificar administradores', ['error' => $e->getMessage()]);
        }
    }
}
