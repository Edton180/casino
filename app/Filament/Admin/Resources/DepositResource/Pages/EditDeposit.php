<?php

namespace App\Filament\Admin\Resources\DepositResource\Pages;

use App\Filament\Admin\Resources\DepositResource;
use App\Models\Deposit;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDeposit extends EditRecord
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Aprovar e creditar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => (int) $this->getRecord()->status === 0)
                ->requiresConfirmation()
                ->modalHeading('Aprovar e creditar este depósito?')
                ->modalDescription('O valor entra na carteira do usuário. Use só depois de confirmar que o pagamento foi recebido.')
                ->form([
                    Forms\Components\Toggle::make('accept_bonus')
                        ->label('Dar o bônus de primeiro depósito (se for o primeiro do usuário)')
                        ->default(false),
                ])
                ->modalSubmitActionLabel('Aprovar e creditar')
                ->action(function (array $data): void {
                    $this->save(false, false);   // valor/forma corrigidos na tela entram antes de creditar
                    DepositResource::approveAndNotify($this->getRecord()->refresh(), (bool) ($data['accept_bonus'] ?? false));
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
            Actions\Action::make('creditLegacy')
                ->label('Creditar agora')
                ->icon('heroicon-o-banknotes')
                ->color('danger')
                ->visible(fn (): bool => $this->getRecord()->isApprovedWithoutCredit())
                ->requiresConfirmation()
                ->modalHeading('Creditar este depósito agora?')
                ->modalDescription(fn (): string => DepositResource::legacyWarning($this->getRecord()))
                ->form([
                    Forms\Components\Toggle::make('accept_bonus')
                        ->label('Dar o bônus de primeiro depósito (se for o primeiro do usuário)')
                        ->default(false),
                ])
                ->modalSubmitActionLabel('Creditar')
                ->action(function (array $data): void {
                    $this->save(false, false);   // valor corrigido na tela entra antes de creditar
                    DepositResource::approveAndNotify($this->getRecord()->refresh(), (bool) ($data['accept_bonus'] ?? false), true);
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
            Actions\DeleteAction::make()
                ->modalDescription(fn (): string => (int) $this->getRecord()->status === 1
                    ? 'Apagar um depósito aprovado NÃO retira o saldo já creditado (para isso use Carteiras → Ajustar saldo).'
                    : 'O depósito pendente será apagado.'),
        ];
    }

    /** Depósito já creditado: só a forma de pagamento e o comprovante mudam (o valor já está na carteira). */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Deposit $record */
        $credited = (int) $record->status === 1 && !$record->isApprovedWithoutCredit();
        $allowed = $credited ? ['type', 'proof'] : ['amount', 'type', 'proof'];
        $record->update(array_intersect_key($data, array_flip($allowed)));

        return $record;
    }
}
