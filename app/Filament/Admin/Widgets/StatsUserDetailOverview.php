<?php

namespace App\Filament\Admin\Widgets;

use App\Helpers\Core as Helper;
use App\Models\AffiliateHistory;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsUserDetailOverview extends BaseWidget
{
    public User $record;

    public function mount($record)
    {
        $this->record = $record;
    }

    /**
     * @return array|Stat[]
     */
    protected function getStats(): array
    {
        // Passo 1: Encontrar todos os usuários que têm o `inviter` igual ao usuário específico
        $userIds = DB::table('users')
            ->where('inviter', $this->record->id)
            ->pluck('id');

        // Passo 2: Calcular o total de depósitos e a quantidade de depósitos com status 1 para esses usuários
        $totalDeposited = DB::table('deposits')
            ->whereIn('user_id', $userIds)
            ->where('status', '1')
            ->sum('amount');

        $totalDepositsCount = DB::table('deposits')
            ->whereIn('user_id', $userIds)
            ->where('status', '1')
            ->count('id');

        // Calcular o total de valor de depósitos para os indicados
        $totalDepositsValue = DB::table('deposits')
            ->whereIn('user_id', $userIds)
            ->where('status', '1')
            ->sum('amount');

        $totalWithdrawn = DB::table('withdrawals')
            ->whereIn('user_id', $userIds)
            ->where('status', '1')
            ->sum('amount');        

        $revShare = $totalDeposited - $totalWithdrawn;

        $totalAfiliados = AffiliateHistory::where('inviter', $this->record->id)->sum('commission_paid');

        return [
            Stat::make('Total de Depósitos', Helper::amountFormatDecimal($totalDeposited))
                ->description('Total de Depósitos na plataforma')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total de Saques', Helper::amountFormatDecimal($totalWithdrawn))
                ->description('Total de Saques na plataforma')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('Revshare', Helper::amountFormatDecimal($revShare))
                ->description('REVSHARE')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Ganhos como Afiliado', Helper::amountFormatDecimal(Helper::formatNumber($totalAfiliados)))
                ->description('Total de Ganhos como afiliado')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Quantidade de Depósitos dos indicados', $totalDepositsCount)
                ->description('Número de Depósitos dos indicados')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
            Stat::make('Valor Total dos Depósitos dos Indicados', Helper::amountFormatDecimal($totalDepositsValue))
                ->description('Soma total dos depósitos dos indicados')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
        ];
    }
}
