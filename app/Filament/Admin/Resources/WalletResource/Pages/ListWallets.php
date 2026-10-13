<?php

namespace App\Filament\Admin\Resources\WalletResource\Pages;

use App\Filament\Admin\Resources\WalletResource;
use Filament\Resources\Pages\ListRecords;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    public function getSubheading(): ?string
    {
        return 'Para dar ou tirar saldo de um jogador use "Ajustar saldo". Para lançar um depósito recebido fora do site use Depósitos → Lançar depósito.';
    }
}
