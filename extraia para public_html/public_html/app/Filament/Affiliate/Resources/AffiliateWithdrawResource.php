<?php

namespace App\Filament\Affiliate\Resources;

use App\Filament\Affiliate\Resources\AffiliateWithdrawResource\Pages;
use App\Models\AffiliateWithdraw;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AffiliateWithdrawResource extends Resource
{
    protected static ?string $model = AffiliateWithdraw::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    /**
     * @dev @victormsalatiel
     * @return bool
     */
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('afiliado');
    }

    /**
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return '.';
    }

    /**
     * @return string
     */
    public static function getModelLabel(): string
    {
        return '.';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(AffiliateWithdraw::query()->where('user_id', auth()->id()))
            ->columns([

            ])
            ->filters([
                //
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
 
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAffiliateWithdraws::route('/'),
        ];
    }
}
