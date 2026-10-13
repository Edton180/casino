<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletChange;
use App\Services\WalletAdjuster;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Carteiras';

    protected static ?string $modelLabel = 'Carteira';

    protected static ?string $pluralModelLabel = 'Carteiras';

    protected static ?string $navigationGroup = 'Administração';

    protected static ?string $slug = 'minha-carteira';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /** Toda conta já nasce com carteira; não se cria nem se apaga carteira pelo painel. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->user->name ?? ('Carteira #' . $record->getKey());
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'user.email'];
    }

    public static function money($value, ?Wallet $wallet = null): string
    {
        return ($wallet?->symbol ?: 'R$') . ' ' . number_format((float) $value, 2, ',', '.');
    }

    /**
     * Os saldos aparecem só para leitura: para mudar, use "Ajustar saldo" (soma/retira com a carteira
     * travada no banco). Gravar o formulário inteiro sobrescrevia apostas feitas enquanto a tela estava aberta.
     */
    public static function form(Form $form): Form
    {
        $balance = fn (string $field, string $label, string $help) => Forms\Components\TextInput::make($field)
            ->label($label)
            ->helperText($help)
            ->prefix(fn (?Wallet $record) => $record?->symbol ?: 'R$')
            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.'))
            ->disabled()
            ->dehydrated(false);

        return $form
            ->schema([
                Forms\Components\Section::make('Usuário')
                    ->schema([
                        Forms\Components\Placeholder::make('user_info')
                            ->label('')
                            ->content(fn (?Wallet $record) => $record?->user
                                ? new HtmlString('<strong>' . e($record->user->name) . '</strong> · ' . e($record->user->email)
                                    . ($record->user->is_demo_agent ? ' · <span style="color:#d97706;font-weight:600">influencer</span> (joga com o saldo de influencer)' : ''))
                                : '—'),
                    ]),
                Forms\Components\Section::make('Saldos')
                    ->description('Para adicionar ou retirar saldo use o botão "Ajustar saldo" no topo da página.')
                    ->schema([
                        $balance('balance', 'Saldo para jogar', 'Depósitos e prêmios ainda presos ao rollover.'),
                        $balance('balance_withdrawal', 'Disponível para saque', 'O que o jogador pode sacar agora.'),
                        $balance('balance_bonus', 'Bônus', 'Bônus de depósito (com rollover próprio).'),
                        $balance('refer_rewards', 'Comissão de afiliado', 'Ganhos de indicação a sacar no painel do afiliado.'),
                        $balance('balance_demo', 'Saldo de influencer', 'Usado só por contas marcadas como influencer (Usuários → Influencer).'),
                        $balance('balance_deposit_rollover', 'Rollover que falta (depósito)', 'Quanto ainda precisa ser apostado para liberar o saque.'),
                    ])->columns(3),
                Forms\Components\Section::make('Últimos ajustes')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Placeholder::make('history')
                            ->label('')
                            ->content(fn (?Wallet $record) => static::historyHtml($record)),
                    ]),
            ]);
    }

    public static function historyHtml(?Wallet $wallet): HtmlString
    {
        if (!$wallet) {
            return new HtmlString('—');
        }
        $rows = WalletChange::where('user_id', $wallet->user_id)
            ->where('reason', 'like', 'Ajuste pelo painel%')
            ->latest('id')->limit(10)->get();
        if ($rows->isEmpty()) {
            return new HtmlString('<span style="opacity:.7">Nenhum ajuste feito pelo painel.</span>');
        }
        $html = '<ul style="line-height:1.8">';
        foreach ($rows as $row) {
            $delta = (float) $row->value_entry;
            $html .= '<li><span style="opacity:.7">' . e(optional($row->created_at)->format('d/m/Y H:i')) . '</span> · '
                . e(WalletAdjuster::FIELDS[$row->change] ?? $row->change) . ': '
                . '<strong style="color:' . ($delta < 0 ? '#dc2626' : '#16a34a') . '">' . ($delta < 0 ? '−' : '+') . e(static::money(abs($delta), $wallet)) . '</strong>'
                . ' → ' . e(static::money($row->value_total, $wallet)) . ' · ' . e($row->reason) . '</li>';
        }

        return new HtmlString($html . '</ul>');
    }

    /** Campos do formulário "Ajustar saldo" (usado na lista e na página da carteira). */
    public static function adjustForm(): array
    {
        return [
            Forms\Components\Select::make('field')
                ->label('Qual saldo')
                ->options(WalletAdjuster::FIELDS)
                ->default('balance')
                ->required()
                ->live()
                ->helperText(fn (Forms\Get $get) => match ($get('field')) {
                    'balance'            => 'O jogador usa para apostar; vira saldo de saque depois de cumprir o rollover.',
                    'balance_withdrawal' => 'O jogador pode sacar este valor na hora.',
                    'balance_bonus'      => 'Bônus para apostar.',
                    'refer_rewards'      => 'Comissão de indicação do afiliado.',
                    'balance_demo'       => 'Só vale para contas marcadas como influencer: elas jogam e veem este saldo no site.',
                    default              => null,
                }),
            Forms\Components\Radio::make('operation')
                ->label('Operação')
                ->options(WalletAdjuster::OPERATIONS)
                ->default('add')
                ->inline()
                ->required(),
            Forms\Components\TextInput::make('amount')
                ->label('Valor')
                ->prefix('R$')
                ->numeric()
                ->minValue(0)
                ->required(),
            Forms\Components\TextInput::make('reason')
                ->label('Motivo (fica registrado)')
                ->placeholder('Ex.: PIX recebido por fora, correção, prêmio de promoção')
                ->maxLength(60),
            Forms\Components\Toggle::make('make_influencer')
                ->label('Marcar este usuário como influencer')
                ->helperText('Sem isso o saldo de influencer não é usado no site.')
                ->default(true)
                ->visible(fn (Forms\Get $get, ?Model $record) => $get('field') === 'balance_demo'
                    && !(($record instanceof Wallet ? $record->user : ($record instanceof User ? $record : null))?->is_demo_agent)),
        ];
    }

    public static function applyAdjustment(Wallet $wallet, array $data): bool
    {
        try {
            $result = app(WalletAdjuster::class)->adjust(
                $wallet,
                (string) $data['field'],
                (string) $data['operation'],
                (float) $data['amount'],
                (string) ($data['reason'] ?? ''),
                auth()->user(),
            );
        } catch (\InvalidArgumentException $e) {
            Notification::make()->title('Saldo não alterado')->body($e->getMessage())->warning()->send();

            return false;
        }

        if (!empty($data['make_influencer']) && $data['field'] === 'balance_demo' && $wallet->user && !$wallet->user->is_demo_agent) {
            $wallet->user->forceFill(['is_demo_agent' => 1])->save();
        }

        Notification::make()
            ->title('Saldo ajustado')
            ->body(WalletAdjuster::FIELDS[$result['field']] . ' de ' . ($wallet->user->name ?? 'usuário') . ': '
                . static::money($result['before'], $wallet) . ' → ' . static::money($result['after'], $wallet))
            ->success()
            ->send();

        return true;
    }

    public static function table(Table $table): Table
    {
        $money = fn (string $field, string $label) => Tables\Columns\TextColumn::make($field)
            ->label($label)
            ->formatStateUsing(fn ($state, Wallet $record): string => static::money($state, $record))
            ->sortable();

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->description(fn (Wallet $record) => $record->user?->email)
                    ->sortable()
                    ->searchable(),
                $money('balance', 'Para jogar'),
                $money('balance_withdrawal', 'Saque'),
                $money('balance_bonus', 'Bônus'),
                $money('refer_rewards', 'Comissão'),
                $money('balance_demo', 'Influencer')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('user.is_demo_agent')
                    ->label('Influencer?')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('influencer')
                    ->label('Influencer')
                    ->queries(
                        true: fn (Builder $q) => $q->whereHas('user', fn (Builder $u) => $u->where('is_demo_agent', 1)),
                        false: fn (Builder $q) => $q->whereHas('user', fn (Builder $u) => $u->where('is_demo_agent', 0)),
                    ),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Criado a partir de'),
                        DatePicker::make('created_until')->label('Criado até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Criado a partir de ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Criado até ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust')
                    ->label('Ajustar saldo')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading(fn (Wallet $record) => 'Ajustar saldo de ' . ($record->user->name ?? 'usuário'))
                    ->form(static::adjustForm())
                    ->modalSubmitActionLabel('Aplicar')
                    ->action(fn (Wallet $record, array $data) => static::applyAdjustment($record, $data)),
                Tables\Actions\EditAction::make()->label('Ver'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\WalletResource\Pages\ListWallets::route('/'),
            'edit'  => \App\Filament\Admin\Resources\WalletResource\Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
