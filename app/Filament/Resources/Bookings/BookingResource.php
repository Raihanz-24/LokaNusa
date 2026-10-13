<?php

namespace App\Filament\Resources\Bookings;

use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use App\Support\Features;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Pemesanan (demo) — penginapan & produk UMKM.
 *
 * Pengelola melihat semua; pemilik usaha hanya melihat pesanan untuk konten
 * miliknya (kolom `owner_id`).
 */
class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Pemesanan';

    protected static ?string $modelLabel = 'Pemesanan';

    protected static ?string $pluralModelLabel = 'Pemesanan';

    protected static string|\UnitEnum|null $navigationGroup = 'Engagement';

    protected static ?int $navigationSort = 20;

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('bookable', 'user', 'owner');

        $user = Auth::user();

        if ($user !== null && ! $user->managesAllContent()) {
            $query->where('owner_id', $user->getKey());
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        if (! Features::booking()) {
            return false;
        }

        $user = Auth::user();

        return $user !== null && ($user->managesAllContent() || $user->isOwner());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Features::booking();
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->managesAllContent() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
        ];
    }
}
