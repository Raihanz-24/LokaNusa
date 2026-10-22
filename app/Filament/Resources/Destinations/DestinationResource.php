<?php

namespace App\Filament\Resources\Destinations;

use App\Enums\Permission;
use App\Filament\Resources\Destinations\Pages\CreateDestination;
use App\Filament\Resources\Destinations\Pages\DestinationQrStats;
use App\Filament\Resources\Destinations\Pages\EditDestination;
use App\Filament\Resources\Destinations\Pages\ListDestinations;
use App\Filament\Resources\Destinations\Schemas\DestinationForm;
use App\Filament\Resources\Destinations\Tables\DestinationsTable;
use App\Models\Destination;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Destinasi';

    protected static ?string $modelLabel = 'Destinasi';

    protected static ?string $pluralModelLabel = 'Destinasi';

    protected static string|\UnitEnum|null $navigationGroup = 'Destinasi';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return DestinationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DestinationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Pengelola hanya melihat destinasi yang ia kelola.
        $user = Auth::user();

        if ($user !== null && ! $user->managesAllContent()) {
            $query->where('manager_id', $user->getKey());
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return Gate::allows(Permission::ManageDestinations->value);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDestinations::route('/'),
            'create' => CreateDestination::route('/create'),
            'edit' => EditDestination::route('/{record}/edit'),
            'qr' => DestinationQrStats::route('/{record}/qr'),
        ];
    }
}
