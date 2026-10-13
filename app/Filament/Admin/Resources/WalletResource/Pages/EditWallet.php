<?php

namespace App\Filament\Admin\Resources\WalletResource\Pages;

use App\Filament\Admin\Resources\WalletResource;
use App\Models\Wallet;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWallet extends EditRecord
{
    protected static string $resource = WalletResource::class;

    public function getTitle(): string
    {
        return 'Carteira de ' . ($this->getRecord()->user->name ?? 'usuário');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('adjust')
                ->label('Ajustar saldo')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->modalHeading('Ajustar saldo')
                ->form(WalletResource::adjustForm())
                ->modalSubmitActionLabel('Aplicar')
                ->action(function (array $data): void {
                    /** @var Wallet $wallet */
                    $wallet = $this->getRecord();
                    if (WalletResource::applyAdjustment($wallet, $data)) {
                        $wallet->refresh();   // mostra os saldos novos e o ajuste no histórico
                        $this->fillForm();
                    }
                }),
        ];
    }

    /** Os saldos só mudam pelo "Ajustar saldo"; esta página não tem botão de salvar. */
    protected function getFormActions(): array
    {
        return [];
    }
}
