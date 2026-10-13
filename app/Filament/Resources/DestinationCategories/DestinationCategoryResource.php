<?php

namespace App\Filament\Resources\DestinationCategories;

use App\Filament\Resources\DestinationCategories\Pages\CreateDestinationCategory;
use App\Filament\Resources\DestinationCategories\Pages\EditDestinationCategory;
use App\Filament\Resources\DestinationCategories\Pages\ListDestinationCategories;
use App\Filament\Resources\DestinationCategories\Schemas\DestinationCategoryForm;
use App\Filament\Resources\DestinationCategories\Tables\DestinationCategoriesTable;
use App\Models\DestinationCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DestinationCategoryResource extends Resource
{
    protected static ?string $model = DestinationCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Kategori Destinasi';

    protected static ?string $modelLabel = 'Kategori Destinasi';

    protected static ?string $pluralModelLabel = 'Kategori Destinasi';

    protected static string|\UnitEnum|null $navigationGroup = 'Destinasi';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return DestinationCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DestinationCategoriesTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        // Kategori destinasi = data referensi; hanya pengelola seluruh konten
        // (bukan pemilik usaha) yang boleh mengelolanya.
        return Auth::user()?->managesAllContent() ?? false;
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
            'index' => ListDestinationCategories::route('/'),
            'create' => CreateDestinationCategory::route('/create'),
            'edit' => EditDestinationCategory::route('/{record}/edit'),
        ];
    }
}
