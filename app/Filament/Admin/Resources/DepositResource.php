<?php

namespace App\Filament\Admin\Resources;

use App\Models\Deposit;
use App\Models\User;
use App\Services\Payments\DepositService;
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

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Depositos';

    protected static ?string $modelLabel = 'Depósito';

    protected static ?string $pluralModelLabel = 'Depósitos';

    protected static ?string $navigationGroup = 'Administração';

    protected static ?string $slug = 'todos-depositos';

    protected static ?int $navigationSort = 2;

    /** Formas de pagamento de um depósito lançado pelo painel. */
    public const TYPES = [
        'pix'         => 'PIX',
        'transfer'    => 'Transferência bancária',
        'cash'        => 'Dinheiro',
        'credit_card' => 'Cartão',
        'manual'      => 'Outro / manual',
    ];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 0)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('status', 0)->count() > 5 ? 'success' : 'warning';
    }

    /** Usuário escolhido pelo nome, e-mail ou CPF (sem carregar todos os usuários na tela). */
    public static function userSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('user_id')
            ->label('Usuário')
            ->placeholder('Digite o nome, e-mail ou CPF')
            ->searchable()
            ->getSearchResultsUsing(fn (string $search): array => User::query()
                ->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%"))
                ->orderBy('name')
                ->limit(50)
                ->get(['id', 'name', 'email'])
                ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} ({$u->email})"])
                ->all())
            ->getOptionLabelUsing(fn ($value): ?string => ($u = User::find($value)) ? "{$u->name} ({$u->email})" : null)
            ->required();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Depósito')
                    ->description(fn (?Deposit $record) => $record === null
                        ? 'Lança um depósito recebido fora do site (PIX direto, transferência, dinheiro). Aprovado, ele é creditado como um depósito pelo site: entra no saldo para jogar com o rollover configurado, conta pontos VIP e paga o CPA do afiliado.'
                        : ((int) $record->status !== 1
                            ? 'Depósito pendente: use "Aprovar e creditar" no topo da página quando o pagamento estiver confirmado.'
                            : ($record->isApprovedWithoutCredit()
                                ? 'Atenção: marcado como aprovado numa versão anterior do painel, mas o valor nunca entrou na carteira. Use "Creditar agora" no topo da página (confira antes se o saldo já foi compensado à mão).'
                                : 'Depósito aprovado e já creditado na carteira. Para tirar ou corrigir saldo, use Carteiras → Ajustar saldo.')))
                    ->schema([
                        static::userSelect()
                            ->disabled(fn (?Deposit $record) => $record !== null),
                        Forms\Components\TextInput::make('amount')
                            ->label('Valor')
                            ->prefix(fn (?Deposit $record) => $record?->symbol ?: 'R$')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->disabled(fn (?Deposit $record) => $record !== null && (int) $record->status === 1 && !$record->isApprovedWithoutCredit()),
                        Forms\Components\Select::make('type')
                            ->label('Forma de pagamento')
                            ->options(fn (?Deposit $record) => static::TYPES + ($record && $record->type && !isset(static::TYPES[$record->type]) ? [$record->type => $record->type] : []))
                            ->default('pix')
                            ->required(),
                        Forms\Components\FileUpload::make('proof')
                            ->label('Comprovante (opcional)')
                            ->image()
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('approve')
                            ->label('Aprovar agora e creditar o saldo')
                            ->helperText('Desligado, o depósito fica pendente para você aprovar depois.')
                            ->default(true)
                            ->live()
                            ->visibleOn('create'),
                        Forms\Components\Toggle::make('accept_bonus')
                            ->label('Dar o bônus de primeiro depósito (se for o primeiro do usuário)')
                            ->helperText('Usa o percentual de Configurações → Pagamentos → Bônus Inicial.')
                            ->default(false)
                            ->visible(fn (string $operation, Forms\Get $get): bool => $operation === 'create' && (bool) $get('approve')),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user')
                ->withExists(['transaction as credited' => fn (Builder $q) => $q->where('status', 1)]))
            ->columns([
                Tables\Columns\TextColumn::make('payment_id')
                    ->label('ID Pag.')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn (Deposit $record): string => ($record->symbol ?: 'R$') . ' ' . number_format((float) $record->amount, 2, ',', '.'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Forma')
                    ->formatStateUsing(fn (?string $state): string => static::TYPES[$state] ?? (string) $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn ($state, Deposit $record): string => (int) $state !== 1 ? 'Pendente'
                        : ($record->isApprovedWithoutCredit() ? 'Aprovado sem crédito' : 'Aprovado'))
                    ->color(fn ($state, Deposit $record): string => (int) $state !== 1 ? 'warning'
                        : ($record->isApprovedWithoutCredit() ? 'danger' : 'success'))
                    ->tooltip(fn (Deposit $record): ?string => $record->isApprovedWithoutCredit()
                        ? 'Marcado como aprovado numa versão anterior do painel, mas o valor nunca entrou na carteira.'
                        : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Situação')
                    ->options([0 => 'Pendente', 1 => 'Aprovado']),
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
                static::approveTableAction(),
                static::creditLegacyTableAction(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalDescription('Apagar um depósito aprovado NÃO retira o saldo já creditado (para isso use Carteiras → Ajustar saldo).'),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Lançar depósito'),
            ]);
    }

    /** Aprovar e creditar um depósito pendente direto da lista. */
    public static function approveTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('approve')
            ->label('Aprovar')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (Deposit $record): bool => (int) $record->status === 0)
            ->requiresConfirmation()
            ->modalHeading('Aprovar e creditar este depósito?')
            ->modalDescription(fn (Deposit $record): string => 'O valor de ' . ($record->symbol ?: 'R$') . ' ' . number_format((float) $record->amount, 2, ',', '.')
                . ' entra na carteira de ' . ($record->user->name ?? 'usuário') . '. Use só depois de confirmar que o pagamento foi recebido.')
            ->form([
                Forms\Components\Toggle::make('accept_bonus')
                    ->label('Dar o bônus de primeiro depósito (se for o primeiro do usuário)')
                    ->default(false),
            ])
            ->modalSubmitActionLabel('Aprovar e creditar')
            ->action(fn (Deposit $record, array $data) => static::approveAndNotify($record, (bool) ($data['accept_bonus'] ?? false)));
    }

    /** Depósito "aprovado" por uma versão anterior do painel que nunca creditou a carteira. */
    public static function creditLegacyTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('creditLegacy')
            ->label('Creditar agora')
            ->icon('heroicon-o-banknotes')
            ->color('danger')
            ->visible(fn (Deposit $record): bool => $record->isApprovedWithoutCredit())
            ->requiresConfirmation()
            ->modalHeading('Creditar este depósito agora?')
            ->modalDescription(fn (Deposit $record): string => static::legacyWarning($record))
            ->form([
                Forms\Components\Toggle::make('accept_bonus')
                    ->label('Dar o bônus de primeiro depósito (se for o primeiro do usuário)')
                    ->default(false),
            ])
            ->modalSubmitActionLabel('Creditar')
            ->action(fn (Deposit $record, array $data) => static::approveAndNotify($record, (bool) ($data['accept_bonus'] ?? false), true));
    }

    public static function legacyWarning(Deposit $record): string
    {
        return 'Este depósito foi marcado como aprovado numa versão anterior do painel, que não creditava a carteira: os '
            . ($record->symbol ?: 'R$') . ' ' . number_format((float) $record->amount, 2, ',', '.') . ' nunca entraram no saldo de '
            . ($record->user->name ?? 'usuário') . '. Antes, confira a carteira: se você já compensou o valor à mão, não credite de novo.';
    }

    public static function approveAndNotify(Deposit $record, ?bool $acceptBonus = null, bool $creditApprovedWithoutCredit = false): void
    {
        try {
            $credited = app(DepositService::class)->approve($record, $acceptBonus, $creditApprovedWithoutCredit);
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Não consegui aprovar o depósito')->body($e->getMessage())->danger()->send();

            return;
        }

        $credited
            ? Notification::make()->title('Depósito aprovado')->body('Saldo creditado na carteira de ' . ($record->user->name ?? 'usuário') . '.')->success()->send()
            : Notification::make()->title('Este depósito já estava creditado')->warning()->send();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Admin\Resources\DepositResource\Pages\ListDeposits::route('/'),
            'create' => \App\Filament\Admin\Resources\DepositResource\Pages\CreateDeposit::route('/create'),
            'edit'   => \App\Filament\Admin\Resources\DepositResource\Pages\EditDeposit::route('/{record}/edit'),
        ];
    }
}
