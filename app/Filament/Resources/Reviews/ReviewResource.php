<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $navigationLabel = 'Ulasan Wisatawan';

    protected static ?string $modelLabel = 'Ulasan';

    protected static ?string $pluralModelLabel = 'Ulasan Wisatawan';

    protected static string|\UnitEnum|null $navigationGroup = 'Engagement';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    /**
     * Ulasan otomatis terbit; panel hanya untuk meninjau & menghapus
     * (khusus super admin, mis. konten bermuatan SARA).
     */
    public static function canViewAny(): bool
    {
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('reviewable');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }
}
