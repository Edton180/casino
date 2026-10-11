<?php

namespace App\Filament\Admin\Pages;
use Illuminate\Support\HtmlString;
use App\Models\Gateway;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

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
                Section::make('Mercado Pago')
                    ->description(new HtmlString('PIX e cartão (Checkout Pro). Credenciais em <a class="underline" href="https://www.mercadopago.com.br/developers/panel/app" target="_blank">Suas integrações</a>.<br>Webhook: <b>' . url('/webhooks/mercadopago', [], true) . '</b> (evento "Pagamentos")'))
                    ->schema([
                        Toggle::make('mp_is_enable')->label('Ativar Mercado Pago')->inline(false),
                        Toggle::make('mp_credit_card_enable')->label('Ativar cartão de crédito')->inline(false),
                        TextInput::make('mp_access_token')->label('Access Token')->placeholder('APP_USR-... (produção) ou TEST-... (teste)')->password()->revealable()->maxLength(255),
                        TextInput::make('mp_public_key')->label('Public Key')->placeholder('APP_USR-...')->maxLength(255),
                        TextInput::make('mp_webhook_key')->label('Assinatura secreta do webhook (opcional)')->password()->revealable()->maxLength(255),
                    ])->columns(2),

                Section::make('PagSeguro / PagBank')
                    ->description(new HtmlString('PIX (API de Pedidos) e cartão (Checkout PagBank). Gere o token em <a class="underline" href="https://minhaconta.pagbank.com.br/" target="_blank">Minha Conta PagBank</a>.<br>Webhook: <b>' . url('/webhooks/pagseguro', [], true) . '</b> (enviado automaticamente em cada cobrança)'))
                    ->schema([
                        Toggle::make('pagseguro_is_enable')->label('Ativar PagSeguro')->inline(false),
                        Toggle::make('pagseguro_credit_card_enable')->label('Ativar cartão de crédito')->inline(false),
                        Toggle::make('pagseguro_sandbox')->label('Modo sandbox')->default(true)->inline(false),
                        TextInput::make('pagseguro_token')->label('Token')->password()->revealable()->columnSpanFull(),
                    ])->columns(3),

                Section::make('Pagar.me')
                    ->description(new HtmlString('PIX e cartão (Checkout Pagar.me) — API v5. Chaves em <a class="underline" href="https://dash.pagar.me/" target="_blank">Dashboard Pagar.me</a> > Configurações > Chaves.<br>Webhook: <b>' . url('/webhooks/pagarme', [], true) . '</b> (eventos order.paid e charge.paid)'))
                    ->schema([
                        Toggle::make('pagarme_is_enable')->label('Ativar Pagar.me')->inline(false),
                        Toggle::make('pagarme_credit_card_enable')->label('Ativar cartão de crédito')->inline(false),
                        TextInput::make('pagarme_secret_key')->label('Secret Key')->placeholder('sk_...')->password()->revealable()->maxLength(255)->columnSpanFull(),
                        TextInput::make('pagarme_webhook_user')->label('Usuário do webhook (opcional)')->maxLength(255),
                        TextInput::make('pagarme_webhook_password')->label('Senha do webhook (opcional)')->password()->revealable()->maxLength(255),
                    ])->columns(2),

                Section::make('Asaas')
                    ->description(new HtmlString('PIX, cartão (fatura Asaas) e saques automáticos via PIX. Chave em <a class="underline" href="https://www.asaas.com/" target="_blank">Asaas</a> > Integrações.<br>Webhook: <b>' . url('/webhooks/asaas', [], true) . '</b> (eventos PAYMENT_RECEIVED e PAYMENT_CONFIRMED)'))
                    ->schema([
                        Toggle::make('asaas_is_enable')->label('Ativar Asaas')->inline(false),
                        Toggle::make('asaas_credit_card_enable')->label('Ativar cartão de crédito')->inline(false),
                        Toggle::make('asaas_sandbox')->label('Modo sandbox')->default(true)->inline(false),
                        Toggle::make('asaas_pix_payout_enable')->label('Pagar saques via PIX Asaas')->helperText('Ao aprovar um saque no painel, o PIX é enviado automaticamente pelo Asaas.')->inline(false),
                        TextInput::make('asaas_api_key')->label('API Key')->placeholder('$aact_...')->password()->revealable()->columnSpanFull(),
                        TextInput::make('asaas_webhook_key')->label('Token de autenticação do webhook (opcional)')->password()->revealable()->maxLength(255)->columnSpanFull(),
                    ])->columns(4),

                Section::make('Stripe')
                    ->description(new HtmlString('Cartão (e PIX em contas brasileiras) via Stripe Checkout. Chaves em <a class="underline" href="https://dashboard.stripe.com/apikeys" target="_blank">Dashboard Stripe</a>.<br>Webhook: <b>' . url('/webhooks/stripe', [], true) . '</b> (eventos checkout.session.completed e checkout.session.async_payment_succeeded). O "Signing secret" (whsec_...) é obrigatório.'))
                    ->schema([
                        Toggle::make('stripe_is_enable')->label('Ativar Stripe')->inline(false),
                        Toggle::make('stripe_pix_enable')->label('Ativar PIX no Stripe')->helperText('Requer conta Stripe Brasil com PIX habilitado.')->inline(false),
                        Toggle::make('stripe_production')->label('Produção')->inline(false),
                        TextInput::make('stripe_public_key')->label('Publishable Key')->placeholder('pk_...')->maxLength(255),
                        TextInput::make('stripe_secret_key')->label('Secret Key')->placeholder('sk_...')->password()->revealable()->maxLength(255),
                        TextInput::make('stripe_webhook_key')->label('Webhook Signing Secret')->placeholder('whsec_...')->password()->revealable()->maxLength(255),
                    ])->columns(3),
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

            $data = array_intersect_key($this->data, array_flip((new Gateway())->getFillable()));

            $setting = Gateway::first();
            if(!empty($setting)) {
                if($setting->update($data)) {
                    Notification::make()
                        ->title('Chaves Alteradas')
                        ->body('Suas chaves foram alteradas com sucesso!')
                        ->success()
                        ->send();
                }
            }else{
                if(Gateway::create($data)) {
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
