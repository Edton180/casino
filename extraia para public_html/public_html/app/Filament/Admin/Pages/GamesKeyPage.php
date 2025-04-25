<?php

namespace App\Filament\Admin\Pages;
use Illuminate\Support\HtmlString;
use App\Models\GamesKey;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

class GamesKeyPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.games-key-page';

    protected static ?string $title = 'Chaves dos Jogos';

    // protected static ?string $slug = 'chaves-dos-jogos';

    /**
     * @dev @victormsalatiel
     * @return bool
     */
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }


    public ?array $data = [];
    public ?GamesKey $setting;

    /**
     * @return void
     */
    public function mount(): void
    {
        $gamesKey = GamesKey::first();
        $gamesKey->apipragmatic40_url = "https:/api.promolu.shop";
        $gamesKey->apipg12_url = "https://api.betgain.com.br/api/v1";
        
        if(!empty($gamesKey)) {
            $this->setting = $gamesKey;
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
               
               
                 //PLAY FIVER ADICIONAR ESSA LINHA DE CÓDIGO
                Section::make('PLAYFIVER API')
                ->description(new HtmlString('
                    <div style="display: flex; align-items: center;">
                        Nossa API fornece diversos jogos de slots e ao vivo. :
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
                           href="https://playfiver.app" 
                           target="_blank">
                            PAINEL PLAYFIVER
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
                           href="https://t.me/playfiver" 
                           target="_blank">
                            GRUPO TELEGRAM
                        </a>
                    </div>
                '))
                ->schema([
                Section::make('CHAVES DE ACESSO PLAYFIVER')
                        ->description('Você pode obter suas chaves de acesso no painel da Playfiver ao criar o seu agente.')
                        ->schema([
                            TextInput::make('playfiver_code')
                                ->label('CÓDIGO DO AGENTE')
                                ->placeholder('Digite aqui o código do agente')
                                ->maxLength(191),

                            TextInput::make('playfiver_token')
                                ->label('AGENTE TOKEN')
                                ->placeholder('Digite aqui o token do agente')
                                ->maxLength(191),
                            TextInput::make('playfiver_secret')
                                ->label('AGENTE SECRETO')
                                ->placeholder('Digite aqui o código secreto do agente')
                                ->maxLength(191),    
                        ])->columns(3),
                ]),

                //PLAY FIVER FIM

               
               
               
               
               
                Section::make(' API 40 Jogos Pragmatic')
                    ->description('Ajustes de credenciais para Center Gaming Pragmatic')
                    ->schema([
                        TextInput::make('apipragmatic40_url')
                            ->label('URL')
                            ->placeholder('Digite aqui a URL')
                            ->maxLength(191),
                        TextInput::make('apipragmatic40_secret')
                            ->label('Agent Secret')
                            ->placeholder('Digite aqui o código secreto do agente')
                            ->maxLength(191),
                        TextInput::make('apipragmatic40_code')
                            ->label('Agent Code')
                            ->placeholder('Digite aqui o código do agente')
                            ->maxLength(191),
                        TextInput::make('apipragmatic40_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o token do agente')
                            ->maxLength(191),
                    ])
                    ->columns(3),
                    Section::make('API 12 Jogos Gaming PG')
                    ->description('Ajustes de credenciais para Center Gaming PG')
                    ->schema([
                        TextInput::make('apipg12_url')
                            ->label('URL')
                            ->placeholder('Digite aqui a URL')
                            ->maxLength(191),
                        TextInput::make('apipg12_secret')
                            ->label('Agent Secret')
                            ->placeholder('Digite aqui o código secreto do agente')
                            ->maxLength(191),
                        TextInput::make('apipg12_code')
                            ->label('Agent Code')
                            ->placeholder('Digite aqui o código do agente')
                            ->maxLength(191),
                        TextInput::make('apipg12_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o token do agente')
                            ->maxLength(191),
                    ])
                    ->columns(3),
                Section::make('System Server')
                    ->description('Ajustes de credenciais para a System Server')
                    ->schema([
                        TextInput::make('system_url')
                            ->label('URL')
                            ->placeholder('Digite aqui a URL')
                            ->maxLength(191),
                        TextInput::make('system_secret')
                            ->label('Agent Secret')
                            ->placeholder('Digite aqui o código secreto do agente')
                            ->maxLength(191),
                        TextInput::make('system_code')
                            ->label('Agent Code')
                            ->placeholder('Digite aqui o código do agente')
                            ->maxLength(191),
                        TextInput::make('system_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o token do agente')
                            ->maxLength(191),
                    ])
                    ->columns(3),
                Section::make('PlayConnection')
                  ->description('Para adquirir creditos acesse https://playconnectapi.com')
                  ->schema([
                    TextInput::make('playconnect_code')
                    ->label('Agent Code')
                    ->placeholder('Digite aqui o Agent Code')
                    ->maxLength(191),
                    TextInput::make('playconnect_token')
                      ->label('Agent Token')
                      ->placeholder('Digite aqui o Agent Token')
                      ->maxLength(191),
                      TextInput::make('playconnect_secret_key')
                        ->label('Agent Secret')
                        ->placeholder('Digite aqui a Agente Secret')
                        ->maxLength(191),
                   ]) ->columns(3),
                Section::make('Shark Connect API')
                    ->description('Ajustes de credenciais para a Shark Connect')
                    ->schema([
                        TextInput::make('venix_agent_code')
                            ->label('Agent Code')
                            ->placeholder('Digite aqui o Agent Code')
                            ->maxLength(191),
                        TextInput::make('venix_agent_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o Agent Token')
                            ->maxLength(191),
                        TextInput::make('venix_agent_secret')
                            ->label('Agent Secret')
                            ->placeholder('Digite aqui a Agente Secret')
                            ->maxLength(191),
                    ])
                    ->columns(3),
                //Section::make('PlayIGaming Aggregator API')
                //    ->description('Ajustes de credenciais para a PlayIGaming Aggregator Telegram: @playgamingB')
                //    ->schema([
                //        TextInput::make('pig_agent_code')
                //            ->label('Agent Code')
                //            ->placeholder('Digite aqui o Agent Code')
                //            ->maxLength(191),
                //        TextInput::make('pig_agent_token')
                //            ->label('Agent Token')
                //            ->placeholder('Digite aqui o Agent Token')
                //            ->maxLength(191),
                //        TextInput::make('pig_agent_secret')
                //            ->label('Agent Secret')
                //            ->placeholder('Digite aqui a Agente Secret')
                //            ->maxLength(191),
                //    ])
                //    ->columns(3),
                //Section::make('Play iGaming API')
                //    ->description('Compre direto com o representante Oficial Telegram: @playgamingB')
                //    ->schema([
                //        TextInput::make('play_gaming_hall')
                //            ->label('Hall')
                //            ->placeholder('Digite aqui sua Hall')
                //            ->maxLength(191),
                //        TextInput::make('play_gaming_key')
                //            ->label('Key')
                //            ->placeholder('Digite aqui a sua Key')
                //            ->maxLength(191),
                //        TextInput::make('play_gaming_login')
                //            ->label('Login')
                //            ->placeholder('Digite aqui o Login')
                //            ->maxLength(191),
                //    ])
                //    ->columns(3),

                Section::make('EverGame API')
                    ->description('Ajustes de credenciais para a EverGame')
                    ->schema([
                        TextInput::make('evergame_agent_code')
                            ->label('Agent Code')
                            ->placeholder('Digite aqui o Agent Code')
                            ->maxLength(191),
                        TextInput::make('evergame_agent_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o Agent Token')
                            ->maxLength(191),
                        TextInput::make('evergame_api_endpoint')
                            ->label('Api Endpoint')
                            ->placeholder('Digite aqui a API Endpoint')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Games2Api')
                    ->description('Ajustes de credenciais para a Games2Api')
                    ->schema([
                        TextInput::make('games2_agent_code')
                            ->label('Agent Code')
                            ->placeholder('Digite aqui o Agent Code')
                            ->maxLength(191),
                        TextInput::make('games2_agent_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o Agent Token')
                            ->maxLength(191),
                        TextInput::make('games2_agent_secret_key')
                            ->label('Agent Secret Key')
                            ->placeholder('Digite aqui o Agent Secret Key')
                            ->maxLength(191),
                        TextInput::make('games2_api_endpoint')
                            ->label('Api Endpoint')
                            ->placeholder('Digite aqui a API Endpoint')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('World Slot API')
                    ->description('Ajustes de credenciais para a World Slot')
                    ->schema([
                        TextInput::make('worldslot_agent_code')
                            ->label('Agent Code')
                            ->placeholder('Digite aqui o Agent Code')
                            ->maxLength(191),
                        TextInput::make('worldslot_agent_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o Agent Token')
                            ->maxLength(191),
                        TextInput::make('worldslot_agent_secret_key')
                            ->label('Agent Secret Key')
                            ->placeholder('Digite aqui o Agent Secret Key')
                            ->maxLength(191),
                        TextInput::make('worldslot_api_endpoint')
                            ->label('Api Endpoint')
                            ->placeholder('Digite aqui a API Endpoint')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Slotegrator API')
                    ->description('Ajustes de credenciais para a Slotegrator')
                    ->schema([
                        TextInput::make('merchant_url')
                            ->label('Merchant URL')
                            ->placeholder('Digite aqui a URL da API')
                            ->maxLength(191),
                        TextInput::make('merchant_id')
                            ->label('Merchant ID')
                            ->placeholder('Digite aqui a Merchant ID')
                            ->maxLength(191),
                        TextInput::make('merchant_key')
                            ->placeholder('Digite aqui a Merchant Key')
                            ->label('Merchant Key')
                            ->maxLength(191),
                    ])
                    ->columns(3),



                Section::make('Salsa API')
                    ->description('Ajustes de credenciais para a Salsa. Site do provedor: https://salsatechnology.com/')
                    ->schema([
                        TextInput::make('salsa_base_uri')
                            ->label('Salsa URI')
                            ->placeholder('Digite aqui a Base URL Salsa')
                            ->maxLength(191),
                        TextInput::make('salsa_pn')
                            ->label('Salsa PN')
                            ->placeholder('Digite aqui o PN')
                            ->maxLength(191),
                        TextInput::make('salsa_key')
                            ->label('Salsa Key')
                            ->placeholder('Digite aqui o Salsa Key')
                            ->maxLength(191),
                    ])
                    ->columns(3),

                Section::make('Vibra Gaming API')
                    ->description('Ajustes de credenciais para a Vibra Gaming Casino. Site do provedor: https://vibragaming.com/')
                    ->schema([
                        TextInput::make('vibra_site_id')
                            ->label('Vibra Site ID')
                            ->placeholder('Digite aqui o Vibra Site ID')
                            ->maxLength(191),
                        TextInput::make('vibra_game_mode')
                            ->label('Vibra Game Mode')
                            ->placeholder('Digite o Vibra Game Mode')
                            ->maxLength(191),
                    ])
                    ->columns(2),

                Section::make('Fivers API')
                    ->description('Ajustes de credenciais para a Fivers')
                    ->schema([
                        TextInput::make('agent_code')
                            ->label('Agent Code')
                            ->placeholder('Digite aqui o Agent Code')
                            ->maxLength(191),
                        TextInput::make('agent_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o Agent Token')
                            ->maxLength(191),
                        TextInput::make('agent_secret_key')
                            ->label('Agent Secret Key')
                            ->placeholder('Digite aqui o Agent Secret Key')
                            ->maxLength(191),
                        TextInput::make('api_endpoint')
                            ->label('Api Endpoint')
                            ->placeholder('Digite aqui a API Endpoint')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                              	Section::make('GitSlotPark PGSoft')
                    ->description('Ajustes de credenciais para GitSlotPark PGSoft')
                    ->schema([
                        TextInput::make('gitslotpark_pg_agent')
                            ->label('Agent id')
                            ->placeholder('Digite aqui o Agent ID')
                            ->password()
                            ->maxLength(191),
                        TextInput::make('gitslotpark_pg_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o Agent Token')
                            ->password()
                            ->maxLength(191),
                        TextInput::make('gitslotpark_pg_secret')
                            ->label('Agent Secret Key')
                            ->placeholder('Digite aqui o Agent Secret Key')
                            ->password()
                            ->maxLength(191),
                        TextInput::make('gitslotpark_pg_endpoint')
                            ->label('Api Endpoint')
                            ->placeholder('Digite aqui a API Endpoint')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
              
              
              	Section::make('GitSlotPark Pragmatic')
                    ->description('Ajustes de credenciais para GitSlotPark Pragmatic')
                    ->schema([
                        TextInput::make('gitslotpark_pp_agent')
                            ->label('Agent id')
                            ->placeholder('Digite aqui o Agent ID')
                            ->password()
                            ->maxLength(191),
                        TextInput::make('gitslotpark_pp_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o Agent Token')
                            ->password()
                            ->maxLength(191),
                        TextInput::make('gitslotpark_pp_secret')
                            ->label('Agent Secret Key')
                            ->placeholder('Digite aqui o Agent Secret Key')
                            ->password()
                            ->maxLength(191),
                        TextInput::make('gitslotpark_pp_endpoint')
                            ->label('Api Endpoint')
                            ->placeholder('Digite aqui a API Endpoint')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
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

            $setting = GamesKey::first();
            if(!empty($setting)) {
                if($setting->update($this->data)) {
                    Notification::make()
                        ->title('Chaves Alteradas')
                        ->body('Suas chaves foram alteradas com sucesso!')
                        ->success()
                        ->send();
                }
            }else{
                if(GamesKey::create($this->data)) {
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
