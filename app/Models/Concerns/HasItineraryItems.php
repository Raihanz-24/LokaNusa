<?php

namespace App\Models\Concerns;

use App\Models\ItineraryItem;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Ditambahkan pada model konten yang dapat dimasukkan ke rencana perjalanan.
 */
trait HasItineraryItems
{
    public function itineraryItems(): MorphMany
    {
        return $this->morphMany(ItineraryItem::class, 'item');
    }
}
