<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PermissionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $slug = 'permissions';

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $modelLabel = 'Permissão';

    protected static ?string $pluralModelLabel = 'Permissões';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(125)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('guard_name')
                    ->label('Guard')
                    ->options(['web' => 'web', 'api' => 'api'])
                    ->default('web')
                    ->required(),
                Forms\Components\Select::make('roles')
                    ->label('Funções')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('guard_name')->label('Guard')->badge(),
                Tables\Columns\TextColumn::make('roles.name')->label('Funções')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('attach_role')
                    ->label('Adicionar a uma função')
                    ->icon('heroicon-o-user-group')
                    ->form([
                        Forms\Components\Select::make('role')
                            ->label('Função')
                            ->options(fn () => Role::query()->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $role = Role::find($data['role']);
                        $role?->givePermissionTo($records);
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit'   => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
