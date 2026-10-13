<?php

namespace App\Filament\Admin\Resources\DepositResource\Pages;

use App\Filament\Admin\Resources\DepositResource;
use App\Models\User;
use App\Services\Payments\DepositService;
use App\Services\Payments\PaymentException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDeposit extends CreateRecord
{
    protected static string $resource = DepositResource::class;

    protected static ?string $title = 'Lançar depósito';

    /**
     * O depósito passa pelo mesmo fluxo de um depósito feito no site: com "Aprovar agora"
     * ele já é creditado na carteira (antes só o registro era criado e o saldo não mudava).
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = User::findOrFail($data['user_id']);

        try {
            return app(DepositService::class)->createManual(
                $user,
                (float) $data['amount'],
                (string) ($data['type'] ?? 'manual'),
                (bool) ($data['approve'] ?? true),
                (bool) ($data['accept_bonus'] ?? false),
                $data['proof'] ?? null,
            );
        } catch (PaymentException $e) {
            Notification::make()->title('Não consegui lançar o depósito')->body($e->getMessage())->danger()->send();
            $this->halt();
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        $deposit = $this->getRecord();
        $value = ($deposit->symbol ?: 'R$') . ' ' . number_format((float) $deposit->amount, 2, ',', '.');

        return Notification::make()
            ->success()
            ->title((int) $deposit->status === 1 ? 'Depósito creditado' : 'Depósito lançado como pendente')
            ->body((int) $deposit->status === 1
                ? "{$value} entrou na carteira de {$deposit->user->name}."
                : "{$value} para {$deposit->user->name}. Aprove quando o pagamento estiver confirmado.");
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()->label('Lançar depósito');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->label('Lançar e fazer outro');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
