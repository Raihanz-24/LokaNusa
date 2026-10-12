<?php

namespace App\Filament\Resources\UmkmBusinesses;

use App\Enums\Permission;
use App\Filament\Resources\UmkmBusinesses\Pages\CreateUmkmBusiness;
use App\Filament\Resources\UmkmBusinesses\Pages\EditUmkmBusiness;
use App\Filament\Resources\UmkmBusinesses\Pages\ListUmkmBusinesses;
use App\Filament\Resources\UmkmBusinesses\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\UmkmBusinesses\Schemas\UmkmBusinessForm;
use App\Filament\Resources\UmkmBusinesses\Tables\UmkmBusinessesTable;
use App\Models\UmkmBusiness;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UmkmBusinessResource extends Resource
{
    protected static ?string $model = UmkmBusiness::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'UMKM & Kuliner';

    protected static ?string $modelLabel = 'UMKM';

    protected static ?string $pluralModelLabel = 'UMKM & Kuliner';

    protected static string|\UnitEnum|null $navigationGroup = 'UMKM';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return UmkmBusinessForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UmkmBusinessesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if ($user !== null && ! $user->managesAllContent()) {
            $query->where('owner_id', $user->getKey());
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return Gate::allows(Permission::ManageUmkm->value);
    }

    /**
     * @return array<string, string>
     */
    public static function getRelations(): array
    {
        return [
            'products' => ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUmkmBusinesses::route('/'),
            'create' => CreateUmkmBusiness::route('/create'),
            'edit' => EditUmkmBusiness::route('/{record}/edit'),
        ];
    }
}
