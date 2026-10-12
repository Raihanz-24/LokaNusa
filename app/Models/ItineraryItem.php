<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Satu butir pada rencana perjalanan (polymorphic ke destinasi/UMKM/
 * penginapan/event).
 */
class ItineraryItem extends Model
{
    protected $fillable = [
        'itinerary_id',
        'item_type',
        'item_id',
        'day',
        'sort_order',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }
}
