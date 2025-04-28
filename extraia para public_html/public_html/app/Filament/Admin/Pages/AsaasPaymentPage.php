<?php

namespace App\Filament\Admin\Pages;

use App\Models\Gateway;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AsaasPaymentPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.pages.asaas-payment-page';

    protected static ?string $navigationLabel = 'Asaas Pagamentos';

    protected static ?string $modelLabel = 'Asaas Pagamentos';

    protected static ?string $title = 'Asaas Pagamentos';

    protected static ?string $slug = 'asaas-pagamentos';

    /**
     * @return bool
     */
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public ?array $data = [];

    /**
     * @return void
     */
    public function mount(): void
    {
        $gateway = Gateway::first();
        $this->form->fill($gateway->toArray());
    }

    /**
     * @param Form $form
     * @return Form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configurações do Asaas')
                    ->schema([
                        Toggle::make('asaas_is_enable')
                            ->label('Ativar Asaas')
                            ->helperText('Ative ou desative o gateway de pagamento Asaas')
                            ->default(false),
                        Toggle::make('asaas_sandbox')
                            ->label('Modo Sandbox')
                            ->helperText('Ative para usar o ambiente de testes do Asaas')
                            ->default(true),
                        TextInput::make('asaas_api_key')
                            ->label('API Key')
                            ->helperText('Chave de API do Asaas')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('asaas_webhook_key')
                            ->label('Webhook Key')
                            ->helperText('Chave de webhook do Asaas')
                            ->required()
                            ->maxLength(255),
                    ])
            ]);
    }

    /**
     * @return void
     */
    public function submit(): void
    {
        $data = $this->form->getState();
        $gateway = Gateway::first();

        if ($gateway->update($data)) {
            Notification::make()
                ->title('Configurações salvas com sucesso!')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Erro ao salvar configurações!')
                ->danger()
                ->send();
        }
    }
} 