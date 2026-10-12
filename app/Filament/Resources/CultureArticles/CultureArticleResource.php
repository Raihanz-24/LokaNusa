<?php

namespace App\Filament\Resources\CultureArticles;

use App\Enums\Permission;
use App\Filament\Resources\CultureArticles\Pages\CreateCultureArticle;
use App\Filament\Resources\CultureArticles\Pages\EditCultureArticle;
use App\Filament\Resources\CultureArticles\Pages\ListCultureArticles;
use App\Filament\Resources\CultureArticles\Schemas\CultureArticleForm;
use App\Filament\Resources\CultureArticles\Tables\CultureArticlesTable;
use App\Models\CultureArticle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CultureArticleResource extends Resource
{
    protected static ?string $model = CultureArticle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'LokaCulture';

    protected static ?string $modelLabel = 'Artikel Budaya';

    protected static ?string $pluralModelLabel = 'Artikel Budaya';

    protected static string|\UnitEnum|null $navigationGroup = 'Budaya';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return CultureArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CultureArticlesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if ($user !== null && ! $user->managesAllContent()) {
            $query->where('author_id', $user->getKey());
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return Gate::allows(Permission::ManageCulture->value);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCultureArticles::route('/'),
            'create' => CreateCultureArticle::route('/create'),
            'edit' => EditCultureArticle::route('/{record}/edit'),
        ];
    }
}
