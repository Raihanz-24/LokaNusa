<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Ditambahkan pada model konten yang dapat diulas (destinasi/UMKM/penginapan/event).
 */
trait HasReviews
{
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function publishedReviews(): MorphMany
    {
        return $this->reviews()->where('status', ContentStatus::Published->value);
    }

    public function reviewCount(): int
    {
        return $this->publishedReviews()->count();
    }

    /**
     * Rata-rata rating ulasan terbit (null bila belum ada).
     */
    public function averageRating(): ?float
    {
        $average = $this->publishedReviews()->avg('rating');

        return $average === null ? null : round((float) $average, 1);
    }
}
