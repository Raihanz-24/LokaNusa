<?php

namespace App\Filament\Resources\HomeBanners;

use App\Enums\Permission;
use App\Filament\Resources\HomeBanners\Pages\CreateHomeBanner;
use App\Filament\Resources\HomeBanners\Pages\EditHomeBanner;
use App\Filament\Resources\HomeBanners\Pages\ListHomeBanners;
use App\Filament\Resources\HomeBanners\Schemas\HomeBannerForm;
use App\Filament\Resources\HomeBanners\Tables\HomeBannersTable;
use App\Models\HomeBanner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class HomeBannerResource extends Resource
{
    protected static ?string $model = HomeBanner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Banner Beranda';

    protected static ?string $modelLabel = 'Banner Beranda';

    protected static ?string $pluralModelLabel = 'Banner Beranda';

    protected static string|\UnitEnum|null $navigationGroup = 'Beranda';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return HomeBannerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeBannersTable::configure($table);
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
            'index' => ListHomeBanners::route('/'),
            'create' => CreateHomeBanner::route('/create'),
            'edit' => EditHomeBanner::route('/{record}/edit'),
        ];
    }
}
