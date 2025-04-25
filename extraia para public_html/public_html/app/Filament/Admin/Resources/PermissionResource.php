<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PermissionResource\Pages\CreatePermission;
use App\Filament\Admin\Resources\PermissionResource\Pages\EditPermission;
use App\Filament\Admin\Resources\PermissionResource\Pages\ListPermissions;
use App\Filament\Admin\Resources\PermissionResource\Pages\ViewPermission;
use App\Filament\Admin\Resources\PermissionResource\RelationManager\RoleRelationManager;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    /**
     * @dev @victormsalatiel
     * @return bool
     */
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

   
}
