<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use App\Models\Setting;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\Toggle;

class DefaultSetting extends Page implements HasForms
{
    use HasPageSidebar, InteractsWithForms;

    protected static string $resource = SettingResource::class;

    protected static string $view = 'filament.resources.setting-resource.pages.default-setting';

    /**
     * @dev @victormsalatiel
     * @param Model $record
     * @return bool
     */
    public static function canView(Model $record): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /**
     * @return string|Htmlable
     */
    public function getTitle(): string | Htmlable
    {
        return __('Padrão');
    }

    public Setting $record;
    public ?array $data = [];

    /**
     * @dev victormsalatiel - Meu instagram
     * @return void
     */
    public function mount(): void
    {
        $setting = Setting::first();
        $this->record = $setting;
        $this->form->fill($setting->toArray());
    }

    /**
     * @return void
     */
    public function save()
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

            $setting = Setting::find($this->record->id);

            $favicon   = $this->data['software_favicon'];
            $logoWhite = $this->data['software_logo_white'];
            $logoBlack = $this->data['software_logo_black'];
            $bannergiros = $this->data['software_girosbanner'];
            $carregamento = $this->data['software_carregamento'];
            $bannerjogos = $this->data['software_bannerjogos'];

            $softwareBackground = $this->data['software_background'];
                $softwareLogin = $this->data['software_login'];
                $softwareRegistro = $this->data['software_registro'];
                $softwareAplicativo = $this->data['software_aplicativo'];

                if (is_array($softwareLogin) || is_object($softwareLogin)) {
                    if(!empty($softwareLogin)) {
                        $this->data['software_login'] = $this->uploadFile($softwareLogin);
                    }
                }
                if (is_array($softwareRegistro) || is_object($softwareRegistro)) {
                    if(!empty($softwareRegistro)) {
                        $this->data['software_registro'] = $this->uploadFile($softwareRegistro);
                    }
                }
                
            if (is_array($softwareBackground) || is_object($softwareBackground)) {
                if(!empty($softwareBackground)) {
                    $this->data['software_background'] = $this->uploadFile($softwareBackground);

                    if(is_array($this->data['software_background'])) {
                        unset($this->data['software_background']);
                    }
                }
            }

            if (is_array($favicon) || is_object($favicon)) {
                if(!empty($favicon)) {
                    $this->data['software_favicon'] = $this->uploadFile($favicon);

                    if(is_array($this->data['software_favicon'])) {
                        unset($this->data['software_favicon']);
                    }
                }
            }

            if (is_array($logoWhite) || is_object($logoWhite)) {
                if(!empty($logoWhite)) {
                    $this->data['software_logo_white'] = $this->uploadFile($logoWhite);

                    if(is_array($this->data['software_logo_white'])) {
                        unset($this->data['software_logo_white']);
                    }
                }
            }

            if (is_array($logoBlack) || is_object($logoBlack)) {
                if(!empty($logoBlack)) {
                    $this->data['software_logo_black'] = $this->uploadFile($logoBlack);

                    if(is_array($this->data['software_logo_black'])) {
                        unset($this->data['software_logo_black']);
                    }
                }
            }
            if (is_array($bannergiros) || is_object($bannergiros)) {
                if(!empty($bannergiros)) {
                    $this->data['software_girosbanner'] = $this->uploadFile($bannergiros);

                    if(is_array($this->data['software_girosbanner'])) {
                        unset($this->data['software_girosbanner']);
                    }
                }
            }
            if (is_array($bannerjogos) || is_object($bannerjogos)) {
                if(!empty($bannerjogos)) {
                    $this->data['software_bannerjogos'] = $this->uploadFile($bannerjogos);

                    if(is_array($this->data['software_bannerjogos'])) {
                        unset($this->data['software_bannerjogos']);
                    }
                }
            }
            if (is_array($carregamento) || is_object($carregamento)) {
                if(!empty($carregamento)) {
                    $this->data['software_carregamento'] = $this->uploadFile($carregamento);

                    if(is_array($this->data['software_carregamento'])) {
                        unset($this->data['software_carregamento']);
                    }
                }
            }
                        if (is_array($softwareAplicativo) || is_object($softwareAplicativo)) {
                if(!empty($softwareAplicativo)) {
                    $this->data['software_aplicativo'] = $this->uploadFile($softwareAplicativo);

                    if(is_array($this->data['software_aplicativo'])) {
                        unset($this->data['software_aplicativo']);
                    }
                }
            }
            $envs = DotenvEditor::load(base_path('.env'));

            $envs->setKeys([
                'APP_NAME' => $this->data['software_name'],
            ]);

            $envs->save();

            if($setting->update($this->data)) {
                Cache::put('setting', $setting);

                Notification::make()
                    ->title('Dados alterados')
                    ->body('Dados alterados com sucesso!')
                    ->success()
                    ->send();

                redirect(route('filament.admin.resources.settings.index'));

            }
        } catch (Halt $exception) {
            return;
        }
    }

    /**
     * @dev victormsalatiel - Meu instagram
     * @param Form $form
     * @return Form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Ajuste Visual')
                    ->description('Formulário ajustar o visual da plataforma')
                    ->schema([
                        Group::make()->schema([
                            TextInput::make('software_name')
                                ->label('Nome')
                                ->placeholder('Digite o nome do site')
                                ->required()
                                ->maxLength(191),
                            TextInput::make('software_description')
                                ->placeholder('Texto da barra superior deslogado')
                                ->label('Barra Superior (sem logar)')
                                ->maxLength(191),
                            TextInput::make('software_description2')
                                ->placeholder('Texto da barra superior logado')
                                ->label('Barra Superior (Logado)')
                                ->maxLength(191),
                                
                        ])->columns(2),
                    Section::make('Aplicativo')
                    ->description('Configurações de Aplicativo')
                    ->schema([
                        Toggle::make('turn_on_aplicativo')
                            ->inline(false)
                            ->label('Ativar Botão e Pagina Do APP'),
                        TextInput::make('aplicativo_link')
                                ->placeholder('LINK DO APP')
                                ->label('Link De Download')
                                ->maxLength(191),
                    ])->columns(2),    
                    Section::make('Futebol')
                    ->description('Configurações de Futebol')
                    ->schema([
                        Toggle::make('turn_on_football')
                            ->inline(false)
                            ->label('Ativar Futebol'),
                        TextInput::make('football_link')
                                ->placeholder('LINK DO FOOTBALL')
                                ->label('LINK DO FOOTBALL')
                                ->maxLength(191),
                    ])->columns(2),
                    Section::make('Cassino Winners')
                    ->description('Ativar ou desativar o cassino winners')
                    ->schema([
                        Toggle::make('cassinowinner')
                            ->inline(false)
                            ->label('Ativar Carrossel vencedores'),

                    ])->columns(1),

                        Group::make()->schema([
                            FileUpload::make('software_favicon')
                                ->label('Favicon')
                                ->placeholder('Carregue um favicon')
                                ->image(),
                            FileUpload::make('software_carregamento')
                                ->label('Logo Tela De Carregamento')
                                ->placeholder('Carregue a logo')
                                ->image(),

                            Group::make()->schema([
                                FileUpload::make('software_logo_white')
                                    ->label('Logo Branca')
                                    ->placeholder('Carregue uma logo branca')
                                    ->image()
                                    ->columnSpanFull(),
                                FileUpload::make('software_logo_black')
                                    ->label('Logo Escura')
                                    ->placeholder('Carregue uma logo escura')
                                    ->image()
                                    ->columnSpanFull(),
                                FileUpload::make('software_girosbanner')
                                    ->label('Banner Giros-Gratis')
                                    ->placeholder('Carregue o Banner Giros-Gratis')
                                    ->image()
                                    ->columnSpanFull(),
                                FileUpload::make('software_login')
                                ->label('Banner login')
                                ->placeholder('Carregue o banner')
                                ->image(),
                            FileUpload::make('software_registro')
                                ->label('Banner Registro')
                                ->placeholder('Carregue o banner')
                                ->image(),
                            FileUpload::make('software_bannerjogos')
                                ->label('Banner Tela Jogos')
                                ->placeholder('Carregue o banner')
                                ->image(),
                            FileUpload::make('software_aplicativo')
                                ->label('Banner Tela De Download APP')
                                ->placeholder('OPCIONAL')
                                ->image(),
                            ])
                        ])->columns(2),
                        
                    ])
            ])
            ->statePath('data') ;
    }

    /**
     * @dev victormsalatiel - Meu instagram
     * @param $array
     * @return mixed|void
     */
    private function uploadFile($array)
    {
        if(!empty($array) && is_array($array) || !empty($array) && is_object($array)) {
            foreach ($array as $k => $temporaryFile) {
                if ($temporaryFile instanceof TemporaryUploadedFile) {
                    $path = \Helper::upload($temporaryFile);
                    if($path) {
                        return $path['path'];
                    }
                }else{
                    return $temporaryFile;
                }
            }
        }
    }
}
