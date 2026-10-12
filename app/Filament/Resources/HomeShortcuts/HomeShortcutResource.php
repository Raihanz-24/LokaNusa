<?php

namespace App\Filament\Resources\HomeShortcuts;

use App\Enums\Permission;
use App\Filament\Resources\HomeShortcuts\Pages\CreateHomeShortcut;
use App\Filament\Resources\HomeShortcuts\Pages\EditHomeShortcut;
use App\Filament\Resources\HomeShortcuts\Pages\ListHomeShortcuts;
use App\Filament\Resources\HomeShortcuts\Schemas\HomeShortcutForm;
use App\Filament\Resources\HomeShortcuts\Tables\HomeShortcutsTable;
use App\Models\HomeShortcut;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class HomeShortcutResource extends Resource
{
    protected static ?string $model = HomeShortcut::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Shortcut Beranda';

    protected static ?string $modelLabel = 'Shortcut Beranda';

    protected static ?string $pluralModelLabel = 'Shortcut Beranda';

    protected static string|\UnitEnum|null $navigationGroup = 'Beranda';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return HomeShortcutForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeShortcutsTable::configure($table);
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
            'index' => ListHomeShortcuts::route('/'),
            'create' => CreateHomeShortcut::route('/create'),
            'edit' => EditHomeShortcut::route('/{record}/edit'),
        ];
    }
}
