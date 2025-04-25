<?php

namespace App\Filament\Affiliate\Widgets;

use App\Models\AffiliateHistory;
use App\Models\User;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AffiliateWidgets extends BaseWidget
{
    protected static ?int $navigationSort = -2;

    /**
     * @return array|Stat[]
     */
    protected function getCards(): array
    {
        $inviterId      = auth()->user()->id;
        $usersIds       = User::where('inviter', $inviterId)->get()->pluck('id');
        $usersTotal     = User::where('inviter', $inviterId)->count();
        $comissaoTotal  = Wallet::whereIn('user_id', $usersIds)->sum('balance_withdrawal');
        $mycomission    = Wallet::where('user_id', $inviterId)->sum('refer_rewards');
$ftd = AffiliateHistory::query()
    ->where('inviter', $inviterId)
    ->where('deposited_amount', '>', 0) // Considera depósitos maiores que 0
    ->where('deposited_amount', '<', 29) // Considera depósitos menores que 29 reais    
    ->count(\DB::raw('DISTINCT user_id'));
    
$cpaCount = AffiliateHistory::query()
    ->where('inviter', $inviterId)
    ->where('commission_type', 'CPA') // Considera apenas comissões do tipo CPA
    ->where('status', 'pago') // Considera apenas comissões que foram pagas
    ->count();
    
// Soma dos valores de comissões do tipo CPA
$cpaTotal = AffiliateHistory::query()
    ->where('inviter', $inviterId)
    ->where('commission_type', 'CPA')
    ->whereNotNull('commission_paid') // Considera apenas registros com valor na coluna commission_paid
    ->sum('commission_paid');

// Soma dos valores de comissões do tipo RevShare
$revShareTotal = AffiliateHistory::query()
    ->where('inviter', $inviterId)
    ->where('commission_type', 'RevShare')
    ->whereNotNull('commission_paid') // Considera apenas registros com valor na coluna commission_paid
    ->sum('commission_paid');

    
    $total = $cpaTotal + $revShareTotal;

    
        // \Log::info('inviterId: '.$inviterId);
        // \Log::info('usersIds: '.$usersIds);
        // \Log::info('usersTotal: '.$usersTotal);
        // \Log::info('comissaoTotal: '.$comissaoTotal);

        return [
            Stat::make('Saldo Disponível', \Helper::amountFormatDecimal($mycomission))
                ->description('Saldo Disponível para saque')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Faturado', \Helper::amountFormatDecimal($total))
                ->description('Todos Seus Ganhos')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Saldo dos indicados', \Helper::amountFormatDecimal($comissaoTotal))
                ->description('Saldo geral dos indicados.')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Cadastros', $usersTotal)
                ->description('Usuários cadastrados com meu link')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Clientes que depositaram', $ftd)
                ->description('Clientes que depositaram ')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

        ];
    }

    /**
     * @return bool
     */
    public static function canView(): bool
    {
        return auth()->user()->hasRole('afiliado');
    }
}
