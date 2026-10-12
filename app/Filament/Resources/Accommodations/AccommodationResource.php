<?php

namespace App\Filament\Resources\Accommodations;

use App\Enums\Permission;
use App\Filament\Resources\Accommodations\Pages\CreateAccommodation;
use App\Filament\Resources\Accommodations\Pages\EditAccommodation;
use App\Filament\Resources\Accommodations\Pages\ListAccommodations;
use App\Filament\Resources\Accommodations\Schemas\AccommodationForm;
use App\Filament\Resources\Accommodations\Tables\AccommodationsTable;
use App\Models\Accommodation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AccommodationResource extends Resource
{
    protected static ?string $model = Accommodation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $navigationLabel = 'Penginapan';

    protected static ?string $modelLabel = 'Penginapan';

    protected static ?string $pluralModelLabel = 'Penginapan';

    protected static string|\UnitEnum|null $navigationGroup = 'Penginapan';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return AccommodationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccommodationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if ($user !== null && ! $user->managesAllContent()) {
            $query->where('manager_id', $user->getKey());
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return Gate::allows(Permission::ManageAccommodations->value);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccommodations::route('/'),
            'create' => CreateAccommodation::route('/create'),
            'edit' => EditAccommodation::route('/{record}/edit'),
        ];
    }
}
