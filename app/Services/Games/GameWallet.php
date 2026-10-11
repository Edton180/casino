<?php

namespace App\Services\Games;

use App\Helpers\Core as Helper;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;

/**
 * Débito de apostas e pagamento de prêmios dos jogos locais.
 * Deve ser chamado dentro de DB::transaction (as carteiras são travadas).
 */
class GameWallet
{
    /**
     * Debita a aposta. Retorna a origem do saldo ("balance", "balance_bonus"...)
     * ou null quando não há saldo suficiente.
     */
    public function debit(User $user, float $bet, string $game, string $round, string $provider): ?string
    {
        $wallet = $this->lockedWallet($user->id);
        if (!$wallet) {
            return null;
        }

        if ($user->is_demo_agent) {
            if ((float) $wallet->balance_demo < $bet) {
                return null;
            }
            $wallet->decrement('balance_demo', $bet);

            return 'balance_demo';
        }

        $changeBonus = Helper::DiscountBalance($wallet, $bet);
        if ($changeBonus === 'no_balance') {
            return null;
        }

        Order::create([
            'user_id'        => $user->id,
            'session_id'     => $round,
            'transaction_id' => $round,
            'game'           => $game,
            'game_uuid'      => $game,
            'type'           => 'bet',
            'type_money'     => $changeBonus,
            'amount'         => $bet,
            'providers'      => $provider,
            'refunded'       => false,
            'round_id'       => $round,
            'status'         => 0,
        ]);

        return $changeBonus;
    }

    /**
     * Encerra a rodada: paga o prêmio (respeitando rollover) e o revshare do afiliado.
     */
    public function settle(User $user, string $round, float $bet, float $win, string $changeBonus): void
    {
        if ($changeBonus === 'balance_demo') {
            if ($win > 0) {
                $this->lockedWallet($user->id)?->increment('balance_demo', $win);
            }
            return;
        }

        // 'bet' paga o revshare do afiliado e avança o rollover; 'win' paga o prêmio
        Helper::generateGameHistory($user->id, $win > 0 ? 'win' : 'bet', $win, $bet, $changeBonus, $round);

        // generateGameHistory só fecha a ordem de quem tem indicador
        Order::where('transaction_id', $round)->where('status', 0)->update(
            $win > 0 ? ['status' => 1, 'type' => 'win', 'amount' => $win] : ['status' => 1]
        );
    }

    public function balance(User $user): float
    {
        $wallet = Wallet::where('user_id', $user->id)->where('active', 1)->first()
            ?? Wallet::where('user_id', $user->id)->first();

        return round((float) ($user->is_demo_agent ? $wallet?->balance_demo : $wallet?->total_balance), 2);
    }

    private function lockedWallet(int $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->where('active', 1)->lockForUpdate()->first()
            ?? Wallet::where('user_id', $userId)->lockForUpdate()->first();
    }
}
