<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletChange;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Ajustes de saldo feitos pelo painel: somar, retirar ou definir um dos saldos da carteira.
 * A conta é feita com a carteira travada no banco, então uma aposta que aconteça ao mesmo tempo
 * não é sobrescrita (o formulário antigo gravava todos os saldos com os valores de quando a tela abriu),
 * e cada ajuste fica registrado em wallet_changes com o motivo e quem fez.
 */
class WalletAdjuster
{
    /** Saldos que o painel pode ajustar → nome mostrado. */
    public const FIELDS = [
        'balance'            => 'Saldo para jogar',
        'balance_withdrawal' => 'Saldo disponível para saque',
        'balance_bonus'      => 'Bônus',
        'refer_rewards'      => 'Comissão de afiliado',
        'balance_demo'       => 'Saldo de influencer',
    ];

    public const OPERATIONS = [
        'add'      => 'Adicionar',
        'subtract' => 'Retirar',
        'set'      => 'Definir o valor',
    ];

    /**
     * @return array{field: string, before: float, after: float, delta: float}
     */
    public function adjust(Wallet $wallet, string $field, string $operation, float $amount, string $reason = '', ?User $by = null): array
    {
        if (!array_key_exists($field, self::FIELDS)) {
            throw new InvalidArgumentException('Escolha qual saldo ajustar.');
        }
        if (!array_key_exists($operation, self::OPERATIONS)) {
            throw new InvalidArgumentException('Escolha se é para adicionar, retirar ou definir o valor.');
        }
        $amount = round($amount, 2);
        if ($amount < 0 || ($operation !== 'set' && $amount <= 0)) {
            throw new InvalidArgumentException('Informe um valor maior que zero.');
        }

        return DB::transaction(function () use ($wallet, $field, $operation, $amount, $reason, $by) {
            $locked = Wallet::whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();
            $before = round((float) $locked->getAttribute($field), 2);
            $after  = round(match ($operation) {
                'add'      => $before + $amount,
                'subtract' => $before - $amount,
                default    => $amount,
            }, 2);

            if ($after < 0) {
                throw new InvalidArgumentException('Não dá para retirar mais do que o usuário tem: ' . self::FIELDS[$field] . ' atual é ' . number_format($before, 2, ',', '.') . '.');
            }

            $locked->forceFill([$field => $after])->save();

            $text = 'Ajuste pelo painel' . ($by ? ' (' . $by->name . ')' : '') . (trim($reason) !== '' ? ': ' . trim($reason) : '');
            WalletChange::create([
                'reason'      => mb_substr($text, 0, 100),
                'change'      => $field,
                'value_entry' => round($after - $before, 2),
                'value_total' => $after,
                'user_id'     => $locked->user_id,
            ]);

            return ['field' => $field, 'before' => $before, 'after' => $after, 'delta' => round($after - $before, 2)];
        });
    }
}
