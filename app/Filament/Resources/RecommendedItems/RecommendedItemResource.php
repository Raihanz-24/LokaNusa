<?php

namespace App\Filament\Resources\RecommendedItems;

use App\Enums\Permission;
use App\Filament\Resources\RecommendedItems\Pages\CreateRecommendedItem;
use App\Filament\Resources\RecommendedItems\Pages\EditRecommendedItem;
use App\Filament\Resources\RecommendedItems\Pages\ListRecommendedItems;
use App\Filament\Resources\RecommendedItems\Schemas\RecommendedItemForm;
use App\Filament\Resources\RecommendedItems\Tables\RecommendedItemsTable;
use App\Models\RecommendationItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class RecommendedItemResource extends Resource
{
    protected static ?string $model = RecommendationItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Rekomendasi Beranda';

    protected static ?string $modelLabel = 'Rekomendasi Beranda';

    protected static ?string $pluralModelLabel = 'Rekomendasi Beranda';

    protected static string|\UnitEnum|null $navigationGroup = 'Beranda';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return RecommendedItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecommendedItemsTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return Gate::allows(Permission::ManageSettings->value);
    }

    public static function canCreate(): bool
    {
        return Gate::allows(Permission::ManageSettings->value);
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows(Permission::ManageSettings->value);
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows(Permission::ManageSettings->value);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecommendedItems::route('/'),
            'create' => CreateRecommendedItem::route('/create'),
            'edit' => EditRecommendedItem::route('/{record}/edit'),
        ];
    }
}
