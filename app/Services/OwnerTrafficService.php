<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Favorite;
use App\Models\Review;
use App\Models\UmkmBusiness;
use App\Models\UmkmProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ringkasan trafik usaha milik PEMILIK (data aktual, tanpa data palsu).
 *
 * Menghitung: jumlah konten miliknya + status, rating (rata-rata & jumlah
 * ulasan terbit), favorit, serta aktivitas pemesanan (menginap / pembelian)
 * dari tabel `bookings` (bila modul pemesanan aktif).
 *
 * Semua angka di-scope HANYA ke konten milik pengguna (server-side).
 */
class OwnerTrafficService
{
    /**
     * Apakah pengguna sudah memiliki minimal satu usaha/konten.
     */
    public function hasBusiness(User $user): bool
    {
        return $user->destinations()->exists()
            || $user->umkmBusinesses()->exists()
            || $user->accommodations()->exists();
    }

    /**
     * Ringkasan lengkap untuk dasbor pemilik.
     *
     * @return array{
     *     has_business: bool,
     *     content: array<string, array{label: string, total: int, published: int}>,
     *     rating: array{average: ?float, count: int},
     *     favorites: int,
     *     stays: array{bookings: int, nights: int, revenue: int},
     *     purchases: array{bookings: int, units: int, revenue: int}
     * }
     */
    public function summary(User $user): array
    {
        $destinationIds = $user->destinations()->pluck('id')->all();
        $businessIds = $user->umkmBusinesses()->pluck('id')->all();
        $accommodationIds = $user->accommodations()->pluck('id')->all();

        return [
            'has_business' => $destinationIds !== [] || $businessIds !== [] || $accommodationIds !== [],
            'content' => [
                'destinasi' => $this->contentCount($user->destinations()),
                'umkm' => $this->contentCount($user->umkmBusinesses()),
                'penginapan' => $this->contentCount($user->accommodations()),
            ],
            'rating' => $this->ratingStats($destinationIds, $businessIds, $accommodationIds),
            'favorites' => $this->favoritesCount($destinationIds, $businessIds, $accommodationIds),
            'stays' => $this->stayStats($user),
            'purchases' => $this->purchaseStats($user),
        ];
    }

    /**
     * Daftar konten/usaha milik pengguna beserta statusnya.
     *
     * @return array<int, array{module: string, name: string, status: ContentStatus}>
     */
    public function items(User $user): array
    {
        $items = [];

        foreach ($user->destinations()->latest()->get() as $destination) {
            $items[] = ['module' => 'Destinasi', 'name' => $destination->name, 'status' => $destination->status, 'slug' => $destination->slug];
        }

        foreach ($user->umkmBusinesses()->latest()->get() as $business) {
            $items[] = ['module' => 'UMKM', 'name' => $business->name, 'status' => $business->status, 'slug' => $business->slug];
        }

        foreach ($user->accommodations()->latest()->get() as $accommodation) {
            $items[] = ['module' => 'Penginapan', 'name' => $accommodation->name, 'status' => $accommodation->status, 'slug' => $accommodation->slug];
        }

        return $items;
    }

    /**
     * @param  HasMany<Model>  $query
     * @return array{label: string, total: int, published: int}
     */
    private function contentCount(HasMany $query): array
    {
        return [
            'label' => '',
            'total' => (clone $query)->count(),
            'published' => (clone $query)->where('status', ContentStatus::Published->value)->count(),
        ];
    }

    /**
     * @param  array<int, int>  $destinationIds
     * @param  array<int, int>  $businessIds
     * @param  array<int, int>  $accommodationIds
     * @return array{average: ?float, count: int}
     */
    private function ratingStats(array $destinationIds, array $businessIds, array $accommodationIds): array
    {
        $query = $this->reviewQuery($destinationIds, $businessIds, $accommodationIds);

        $count = (clone $query)->count();

        return [
            'average' => $count > 0 ? round((float) (clone $query)->avg('rating'), 1) : null,
            'count' => $count,
        ];
    }

    /**
     * @param  array<int, int>  $destinationIds
     * @param  array<int, int>  $businessIds
     * @param  array<int, int>  $accommodationIds
     */
    private function favoritesCount(array $destinationIds, array $businessIds, array $accommodationIds): int
    {
        return Favorite::query()
            ->where(function (Builder $query) use ($destinationIds, $businessIds, $accommodationIds): void {
                $query->orWhere(fn (Builder $q) => $q->where('favoritable_type', Destination::class)->whereIn('favoritable_id', $destinationIds))
                    ->orWhere(fn (Builder $q) => $q->where('favoritable_type', UmkmBusiness::class)->whereIn('favoritable_id', $businessIds))
                    ->orWhere(fn (Builder $q) => $q->where('favoritable_type', Accommodation::class)->whereIn('favoritable_id', $accommodationIds));
            })
            ->count();
    }

    /**
     * @param  array<int, int>  $destinationIds
     * @param  array<int, int>  $businessIds
     * @param  array<int, int>  $accommodationIds
     * @return Builder<int, Review>
     */
    private function reviewQuery(array $destinationIds, array $businessIds, array $accommodationIds): Builder
    {
        return Review::query()
            ->where('status', ContentStatus::Published->value)
            ->where(function (Builder $query) use ($destinationIds, $businessIds, $accommodationIds): void {
                $query->orWhere(fn (Builder $q) => $q->where('reviewable_type', Destination::class)->whereIn('reviewable_id', $destinationIds))
                    ->orWhere(fn (Builder $q) => $q->where('reviewable_type', UmkmBusiness::class)->whereIn('reviewable_id', $businessIds))
                    ->orWhere(fn (Builder $q) => $q->where('reviewable_type', Accommodation::class)->whereIn('reviewable_id', $accommodationIds));
            });
    }

    /**
     * Aktivitas menginap (dari pesanan penginapan milik pemilik).
     *
     * @return array{bookings: int, nights: int, revenue: int}
     */
    private function stayStats(User $user): array
    {
        $query = Booking::query()
            ->paid()
            ->where('owner_id', $user->getKey())
            ->where('bookable_type', Accommodation::class);

        return [
            'bookings' => (clone $query)->count(),
            'nights' => (int) (clone $query)->sum('quantity'),
            'revenue' => (int) (clone $query)->sum('total_price'),
        ];
    }

    /**
     * Aktivitas pembelian (dari pesanan produk UMKM milik pemilik).
     *
     * @return array{bookings: int, units: int, revenue: int}
     */
    private function purchaseStats(User $user): array
    {
        $query = Booking::query()
            ->paid()
            ->where('owner_id', $user->getKey())
            ->where('bookable_type', UmkmProduct::class);

        return [
            'bookings' => (clone $query)->count(),
            'units' => (int) (clone $query)->sum('quantity'),
            'revenue' => (int) (clone $query)->sum('total_price'),
        ];
    }
}
