<?php

namespace App\Filament\Resources\Reviews;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

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
     * Ulasan otomatis terbit; panel dipakai moderator untuk meninjau,
     * menyembunyikan, dan menghapus ulasan yang melanggar.
     *
     * Akses mengikuti permission moderasi (Pengelola LokaNusa & Admin
     * Pemerintah), bukan lagi khusus Developer — perbaikan D-051.
     */
    public static function canViewAny(): bool
    {
        return Gate::allows(Permission::ModerateContent->value);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Hanya moderator yang boleh menyembunyikan/menghapus ulasan.
     * Pemilik konten tidak boleh memoderasi ulasannya sendiri.
     */
    public static function canDelete(Model $record): bool
    {
        return Gate::allows(Permission::ModerateContent->value);
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('reviewable');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canViewAny()) {
            return null;
        }

        $count = Review::query()
            ->where('status', ContentStatus::Pending->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }
}
