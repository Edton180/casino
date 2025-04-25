<?php

namespace App\Filament\Admin\Pages;
use Illuminate\Support\HtmlString;
use App\Models\Gateway;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class GatewayPage extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.gateway-page';

    public ?array $data = [];
    public Gateway $setting;

    /**
     * @dev @victormsalatiel
     * @return bool
     */
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /**
     * @return void
     */
    public function mount(): void
    {
        $gateway = Gateway::first();
        if(!empty($gateway)) {
            $this->setting = $gateway;
            $this->form->fill($this->setting->toArray());
        }else{
            $this->form->fill();
        }
    }

    /**
     * @param Form $form
     * @return Form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                
Section::make('BSPAY E PIXUP')
->description(new HtmlString('
                <div style="display: flex; align-items: center;">
                    Precisa de uma conta na Digito Pay? Responda o formulário de contato e solicite sua conta.:
                    <a class="dark:text-white" 
                    style="
                            font-size: 14px;
                            font-weight: 600;
                            width: 127px;
                            display: flex;
                            background-color: #000000;
                            padding: 10px;
                            border-radius: 11px;
                            justify-content: center;
                            margin-left: 10px;
                    " 
                    href="https://dashboard.pixupbr.com/" 
                    target="_blank">
                        Dashboard
                    </a>
                    <a class="dark:text-white" 
                    style="
                            font-size: 14px;
                            font-weight: 600;
                            width: 127px;
                            display: flex;
                            background-color: #000000;
                            padding: 10px;
                            border-radius: 11px;
                            justify-content: center;
                            margin-left: 10px;
                    " 
                    href="https://wa.me/557189320292" 
                    target="_blank">
                        Gerente
                    </a>
                </div>
    <b>Seu Webhook:  ' . url("/bspay/callback", [], true) . "</b>"))
    ->schema([
        TextInput::make('bspay_uri')
            ->label('CLIENTE URL')
            ->placeholder('Digite a url da api')
            ->maxLength(191)
            ->columnSpanFull(),
        TextInput::make('bspay_cliente_id')
            ->label('CLIENTE ID')
            ->placeholder('Digite o client ID')
            ->maxLength(191)
            ->columnSpanFull(),
        TextInput::make('bspay_cliente_secret')
            ->label('CLIENTE SECRETO')
            ->placeholder('Digite o client secret')
            ->maxLength(191)
            ->columnSpanFull(),
    ]),
                
                Section::make('DigitoPay')
                    ->description('Ajustes de credenciais para a DigitoPay')
                    ->schema([
                        TextInput::make('digitopay_uri')
                            ->label('Client URI')
                            ->placeholder('Digite a url da api')
                            ->default('https://si5n56mrnjzvt5gr2f536ildr40sqzke.lambda-url.sa-east-1.on.aws/')
                            ->maxLength(191),
                        TextInput::make('digitopay_cliente_id')
                            ->label('Client ID')
                            ->placeholder('Digite o client ID')
                            ->maxLength(191),
                        TextInput::make('digitopay_cliente_secret')
                            ->label('Client Secret')
                            ->placeholder('Digite o client secret')
                            ->maxLength(191),
                    ])->columns(3),
        

                Section::make('Suitpay')
                    ->description('Ajustes de credenciais para a Suitpay')
                    ->schema([
                        TextInput::make('suitpay_uri')
                            ->label('Client URI')
                            ->placeholder('Digite a url da api')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('suitpay_cliente_id')
                            ->label('Client ID')
                            ->placeholder('Digite o client ID')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('suitpay_cliente_secret')
                            ->label('Client Secret')
                            ->placeholder('Digite o client secret')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }


    /**
     * @return void
     */
    public function submit(): void
    {
        try {
            if(env('APP_DEMO')) {
                Notification::make()
                    ->title('Atenção')
                    ->body('Você não pode realizar está alteração na versão demo')
                    ->danger()
                    ->send();
                return;
            }

            $setting = Gateway::first();
            if(!empty($setting)) {
                if($setting->update($this->data)) {
                    if(!empty($this->data['stripe_public_key'])) {
                        $envs = DotenvEditor::load(base_path('.env'));

                        $envs->setKeys([
                            'STRIPE_KEY' => $this->data['stripe_public_key'],
                            'STRIPE_SECRET' => $this->data['stripe_secret_key'],
                            'STRIPE_WEBHOOK_SECRET' => $this->data['stripe_webhook_key'],
                        ]);

                        $envs->save();
                    }

                    Notification::make()
                        ->title('Chaves Alteradas')
                        ->body('Suas chaves foram alteradas com sucesso!')
                        ->success()
                        ->send();
                }
            }else{
                if(Gateway::create($this->data)) {
                    Notification::make()
                        ->title('Chaves Criadas')
                        ->body('Suas chaves foram criadas com sucesso!')
                        ->success()
                        ->send();
                }
            }


        } catch (Halt $exception) {
            Notification::make()
                ->title('Erro ao alterar dados!')
                ->body('Erro ao alterar dados!')
                ->danger()
                ->send();
        }
    }
}
