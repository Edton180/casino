<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use App\Models\Gateway;
use App\Models\Setting;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GatewayPage extends Page implements HasForms
{
    use HasPageSidebar, InteractsWithForms;

    protected static string $resource = SettingResource::class;

    protected static string $view = 'filament.resources.setting-resource.pages.gateway-page';

    /**
     * @return string|Htmlable
     */
    public function getTitle(): string | Htmlable
    {
        return 'Gateways';
    }

    private const TOGGLES = ['mp_is_enable', 'pagseguro_is_enable', 'pagarme_is_enable', 'asaas_is_enable', 'stripe_is_enable', 'paypal_is_enable'];

    public Setting $record;
    public ?array $data = [];

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
     * @dev victormsalatiel - Meu instagram
     * @return void
     */
    public function mount(): void
    {
        $this->record = Setting::first();

        // Os gateways ficam na tabela "gateways" (mesma tela de credenciais)
        $gateway = Gateway::first();
        $this->form->fill($gateway ? $gateway->only(self::TOGGLES) : []);
    }

    /**
     * @dev victormsalatiel - Meu instagram
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

            $data = array_intersect_key($this->data, array_flip(self::TOGGLES));
            $gateway = Gateway::first() ?? new Gateway();

            if($gateway->fill($data)->save()) {
                Cache::forget('setting');

                Notification::make()
                    ->title('Dados alterados')
                    ->body('Dados alterados com sucesso!')
                    ->success()
                    ->send();

                redirect(route('filament.admin.resources.settings.payment', ['record' => $this->record->id]));

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
                Section::make('Gateways')
                    ->description('Ative ou desative os gateways de pagamento. As credenciais ficam em Pagamentos > Gateway de Pagamentos.')
                    ->schema([
                        Toggle::make('mp_is_enable')
                            ->label('Mercado Pago'),
                        Toggle::make('pagseguro_is_enable')
                            ->label('PagSeguro / PagBank'),
                        Toggle::make('pagarme_is_enable')
                            ->label('Pagar.me'),
                        Toggle::make('asaas_is_enable')
                            ->label('Asaas'),
                        Toggle::make('stripe_is_enable')
                            ->label('Stripe'),
                        Toggle::make('paypal_is_enable')
                            ->label('PayPal'),
                    ])->columns(2)
            ])
            ->statePath('data') ;
    }
}
