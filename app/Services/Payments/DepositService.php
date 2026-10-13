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
    /** "Gateway" dos depósitos lançados pelo painel (PIX direto, transferência, dinheiro...). */
    public const MANUAL_GATEWAY = 'manual';

    public function __construct(private PaymentManager $payments)
    {
    }

    /**
     * Depósito lançado pelo administrador. Se aprovado, credita na hora como um depósito de gateway:
     * rollover, pontos VIP, CPA do afiliado e, se pedido, o bônus de primeiro depósito.
     */
    public function createManual(User $user, float $amount, string $type, bool $approve, bool $acceptBonus = false, ?string $proof = null): Deposit
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new PaymentException('Informe um valor maior que zero.');
        }

        $wallet = Wallet::where('user_id', $user->id)->where('active', 1)->first()
            ?? Wallet::where('user_id', $user->id)->first();
        if (!$wallet) {
            throw new PaymentException('Este usuário não tem carteira.');
        }

        $setting   = Setting::first();
        $reference = 'man' . Str::lower(Str::random(29));

        return DB::transaction(function () use ($user, $amount, $type, $approve, $acceptBonus, $proof, $wallet, $setting, $reference) {
            Transaction::create([
                'payment_id'     => $reference,
                'user_id'        => $user->id,
                'payment_method' => $type,
                'gateway'        => self::MANUAL_GATEWAY,
                'price'          => $amount,
                'currency'       => $wallet->currency ?: strtoupper($setting->currency_code ?? 'BRL'),
                'accept_bonus'   => $acceptBonus,
                'status'         => 0,
            ]);

            $deposit = Deposit::create([
                'payment_id' => $reference,
                'user_id'    => $user->id,
                'amount'     => $amount,
                'type'       => $type,
                'proof'      => $proof,
                'currency'   => $wallet->currency ?: strtoupper($setting->currency_code ?? 'BRL'),
                'symbol'     => $wallet->symbol ?: 'R$',
                'status'     => 0,
            ]);

            if ($approve) {
                $this->approve($deposit);
            }

            return $deposit->fresh();
        });
    }

    /**
     * Aprova um depósito pendente pelo painel (lançado manualmente ou de gateway cujo aviso não chegou)
     * e credita a carteira uma única vez.
     *
     * @param  bool|null  $acceptBonus  null = mantém a escolha feita no depósito
     * @param  bool  $creditApprovedWithoutCredit  credita também um depósito marcado como aprovado que nunca foi
     *                                             creditado (lançado pelo painel em versões anteriores)
     * @return bool true se creditou agora; false se o depósito já estava creditado
     */
    public function approve(Deposit $deposit, ?bool $acceptBonus = null, bool $creditApprovedWithoutCredit = false): bool
    {
        return DB::transaction(function () use ($deposit, $acceptBonus, $creditApprovedWithoutCredit) {
            $locked = Deposit::whereKey($deposit->getKey())->lockForUpdate()->first();
            if (!$locked) {
                return false;
            }
            // aprovado = creditado, a não ser que o painel peça para creditar um "aprovado sem crédito"
            if ((int) $locked->status === 1 && !($creditApprovedWithoutCredit && $locked->isApprovedWithoutCredit())) {
                return false;
            }

            $transaction = $locked->payment_id
                ? Transaction::where('payment_id', $locked->payment_id)->lockForUpdate()->first()
                : null;

            if ($transaction && (int) $transaction->status === 1) {
                $locked->update(['status' => 1]);   // o gateway já tinha creditado
                return false;
            }

            if (!$transaction) {
                // depósito sem cobrança registrada (ex.: criado pelo painel em versões anteriores)
                $reference = $locked->payment_id ?: 'man' . Str::lower(Str::random(29));
                $wallet    = Wallet::where('user_id', $locked->user_id)->first();
                $transaction = Transaction::create([
                    'payment_id'     => $reference,
                    'user_id'        => $locked->user_id,
                    'payment_method' => $locked->type ?: 'manual',
                    'gateway'        => self::MANUAL_GATEWAY,
                    'price'          => $locked->amount,
                    'currency'       => $locked->currency ?: ($wallet->currency ?? 'BRL'),
                    'accept_bonus'   => (bool) $acceptBonus,
                    'status'         => 0,
                ]);
                $locked->update(array_filter([
                    'payment_id' => $locked->payment_id ? null : $reference,
                    'currency'   => $locked->currency ? null : ($wallet->currency ?? 'BRL'),
                    'symbol'     => $locked->symbol ? null : ($wallet->symbol ?? 'R$'),
                ]));
            } elseif ($acceptBonus !== null) {
                $transaction->update(['accept_bonus' => $acceptBonus]);
            }

            // o valor do depósito é o que vale (pode ter sido corrigido no painel enquanto pendente)
            if ((float) $transaction->price !== (float) $locked->amount) {
                $transaction->update(['price' => $locked->amount]);
            }

            $this->creditWallet($transaction->fresh());

            return true;
        });
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

        if ($transaction->gateway === self::MANUAL_GATEWAY) {
            return PaymentGateway::STATUS_PENDING;   // lançado pelo painel: só o administrador aprova
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
