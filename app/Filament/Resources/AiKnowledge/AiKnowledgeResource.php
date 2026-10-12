<?php

namespace App\Filament\Resources\AiKnowledge;

use App\Enums\Permission;
use App\Filament\Resources\AiKnowledge\Pages\CreateAiKnowledgeEntry;
use App\Filament\Resources\AiKnowledge\Pages\EditAiKnowledgeEntry;
use App\Filament\Resources\AiKnowledge\Pages\ListAiKnowledgeEntries;
use App\Filament\Resources\AiKnowledge\Schemas\AiKnowledgeForm;
use App\Filament\Resources\AiKnowledge\Tables\AiKnowledgeTable;
use App\Models\AiKnowledgeEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class AiKnowledgeResource extends Resource
{
    protected static ?string $model = AiKnowledgeEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Basis Pengetahuan AI';

    protected static ?string $modelLabel = 'Entri Pengetahuan';

    protected static ?string $pluralModelLabel = 'Basis Pengetahuan AI';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 92;

    public static function form(Schema $schema): Schema
    {
        return AiKnowledgeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiKnowledgeTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return Gate::allows(Permission::ManageAiKnowledge->value);
    }

    public static function canCreate(): bool
    {
        return Gate::allows(Permission::ManageAiKnowledge->value);
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows(Permission::ManageAiKnowledge->value);
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows(Permission::ManageAiKnowledge->value);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiKnowledgeEntries::route('/'),
            'create' => CreateAiKnowledgeEntry::route('/create'),
            'edit' => EditAiKnowledgeEntry::route('/{record}/edit'),
        ];
    }
}
