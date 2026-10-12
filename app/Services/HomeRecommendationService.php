<?php

namespace App\Services;

use App\Enums\EngagementType;
use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\RecommendationItem;
use App\Models\UmkmBusiness;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Menggabungkan item "Rekomendasi untukmu" dari kurasi manual admin
 * (RecommendationItem) dengan flag otomatis pada tiap modul.
 *
 * Hasil dinormalisasi ke array siap-tampil (kartu) agar Blade tidak
 * perlu tahu detail setiap model.
 */
class HomeRecommendationService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function cards(int $limit = 8): Collection
    {
        $cards = collect();

        // 1) Kurasi manual (sumber utama).
        RecommendationItem::query()
            ->ordered()
            ->with('recommendable')
            ->get()
            ->each(function (RecommendationItem $item) use ($cards): void {
                $model = $item->recommendable;

                if ($model === null || ! self::isVisible($model)) {
                    return;
                }

                $cards->push(self::map($model));
            });

        // 2) Fallback: flag "rekomendasi" pada tiap modul.
        if ($cards->count() < $limit) {
            $cards = $cards
                ->merge(self::flaggedDestinations())
                ->merge(self::flaggedUmkm())
                ->merge(self::flaggedAccommodations());
        }

        return $cards
            ->unique(fn (array $card): string => $card['type'].'#'.$card['id'])
            ->take($limit)
            ->values();
    }

    /**
     * Daftar "Rekomendasi" halaman katalog: konten dengan rating tertinggi
     * (hanya yang sudah punya ulasan), maksimal $limit item.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function topRated(EngagementType $type, int $limit = 10): Collection
    {
        $class = $type->modelClass();

        return $class::query()
            ->published()
            ->with('region')
            ->withCount(['publishedReviews as reviews_count'])
            ->withAvg(['publishedReviews as reviews_avg'], 'rating')
            ->whereHas('publishedReviews')
            ->orderByDesc('reviews_avg')
            ->orderByDesc('reviews_count')
            ->limit($limit)
            ->get()
            ->map(fn (object $model): array => self::map($model));
    }

    /**
     * Apakah model terlihat publik (terbit).
     */
    private static function isVisible(object $model): bool
    {
        return method_exists($model, 'isPublished') && $model->isPublished();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function flaggedDestinations(): Collection
    {
        return Destination::query()->published()->where('is_recommended', true)
            ->with('category')
            ->withCount(['publishedReviews as reviews_count'])
            ->withAvg(['publishedReviews as reviews_avg'], 'rating')
            ->orderBy('recommendation_order')->orderByDesc('published_at')->limit(8)->get()
            ->map(fn (Destination $model): array => self::map($model));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function flaggedUmkm(): Collection
    {
        return UmkmBusiness::query()->published()->where('is_recommended', true)
            ->withCount(['publishedReviews as reviews_count'])
            ->withAvg(['publishedReviews as reviews_avg'], 'rating')
            ->orderBy('recommendation_order')->orderByDesc('published_at')->limit(8)->get()
            ->map(fn (UmkmBusiness $model): array => self::map($model));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function flaggedAccommodations(): Collection
    {
        return Accommodation::query()->published()->where('is_recommended', true)
            ->withCount(['publishedReviews as reviews_count'])
            ->withAvg(['publishedReviews as reviews_avg'], 'rating')
            ->orderBy('recommendation_order')->orderByDesc('published_at')->limit(8)->get()
            ->map(fn (Accommodation $model): array => self::map($model));
    }

    /**
     * Normalisasi sebuah model menjadi kartu.
     *
     * @return array<string, mixed>
     */
    public static function map(object $model): array
    {
        $reviewCount = $model->reviews_count ?? $model->reviewCount();
        $reviewAvg = ($model->reviews_avg ?? null) !== null
            ? round((float) $model->reviews_avg, 1)
            : $model->averageRating();

        return match (true) {
            $model instanceof Destination => [
                'id' => $model->getKey(),
                'type' => 'destinasi',
                'type_label' => $model->category?->name ?? __('ui.nav_destinations'),
                'title' => $model->name,
                'url' => route('destinations.show', $model->slug),
                'image' => self::imageUrl($model->gallery),
                'meta' => $model->region?->fullName(),
                'price' => $model->ticket_price === null || $model->ticket_price === 0
                    ? __('ui.free')
                    : 'Rp'.number_format($model->ticket_price, 0, ',', '.'),
                'review_count' => $reviewCount,
                'review_avg' => $reviewAvg,
            ],
            $model instanceof UmkmBusiness => [
                'id' => $model->getKey(),
                'type' => 'umkm',
                'type_label' => $model->business_type?->translatedLabel() ?? __('ui.nav_umkm'),
                'title' => $model->name,
                'url' => route('umkm.show', $model->slug),
                'image' => self::imageUrl($model->gallery),
                'meta' => $model->region?->fullName(),
                'price' => null,
                'review_count' => $reviewCount,
                'review_avg' => $reviewAvg,
            ],
            $model instanceof Accommodation => [
                'id' => $model->getKey(),
                'type' => 'penginapan',
                'type_label' => $model->type?->translatedLabel() ?? __('ui.nav_accommodations'),
                'title' => $model->name,
                'url' => route('accommodations.show', $model->slug),
                'image' => self::imageUrl($model->gallery),
                'meta' => $model->region?->fullName(),
                'price' => $model->price_per_night === null
                    ? null
                    : 'Rp'.number_format($model->price_per_night, 0, ',', '.').__('ui.per_night'),
                'review_count' => $reviewCount,
                'review_avg' => $reviewAvg,
            ],
            default => [
                'id' => $model->getKey(),
                'type' => 'lainnya',
                'type_label' => __('ui.recommendation'),
                'title' => 'Rekomendasi',
                'url' => route('home'),
                'image' => null,
                'meta' => null,
                'price' => null,
                'review_count' => 0,
                'review_avg' => null,
            ],
        };
    }

    /**
     * @param  array<int, string>|null  $gallery
     */
    private static function imageUrl(?array $gallery): ?string
    {
        $cover = collect($gallery ?? [])->first();

        return $cover ? Storage::disk('public')->url($cover) : null;
    }
}
