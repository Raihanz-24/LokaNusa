<?php

namespace App\Models;

use App\Enums\EngagementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Rencana perjalanan wisatawan (Step 15 — Itinerary dasar).
 */
class Itinerary extends EloquentModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'start_date',
        'end_date',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Itinerary $itinerary): void {
            if (blank($itinerary->slug)) {
                $itinerary->slug = static::uniqueSlug($itinerary->title, $itinerary->getKey());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItineraryItem::class)->orderBy('day')->orderBy('sort_order');
    }

    /**
     * Jumlah hari pada rencana (minimal 1).
     */
    public function totalDays(): int
    {
        if ($this->start_date && $this->end_date) {
            return max(1, $this->start_date->diffInDays($this->end_date) + 1);
        }

        return max(1, (int) $this->items->max('day'));
    }

    /**
     * Item dikelompokkan per hari.
     *
     * @return array<int, Collection<int, ItineraryItem>>
     */
    public function itemsByDay(): array
    {
        return $this->items
            ->groupBy(fn (ItineraryItem $item): int => (int) $item->day)
            ->sortKeys()
            ->all();
    }

    /**
     * Subtotal estimasi biaya per hari (hanya kegiatan yang punya angka).
     *
     * @return array<int, int>
     */
    public function costByDay(): array
    {
        $totals = [];

        foreach ($this->items as $item) {
            $cost = $item->effectiveCost();

            if ($cost === null) {
                continue;
            }

            $day = max(1, (int) $item->day);
            $totals[$day] = ($totals[$day] ?? 0) + $cost;
        }

        ksort($totals);

        return $totals;
    }

    /**
     * Total estimasi biaya rencana (hanya kegiatan yang punya angka).
     */
    public function totalCost(): int
    {
        return array_sum($this->costByDay());
    }

    /**
     * Apakah ada minimal satu kegiatan dengan estimasi biaya.
     */
    public function hasCostEstimate(): bool
    {
        return $this->costByDay() !== [];
    }

    /**
     * Slug unik untuk judul tertentu.
     */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'rencana';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Jenis konten (EngagementType) dari sebuah model item.
     */
    public static function typeOf(EloquentModel $model): ?EngagementType
    {
        return EngagementType::fromModel($model);
    }
}
