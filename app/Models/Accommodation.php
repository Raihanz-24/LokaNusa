<?php

namespace App\Models;

use App\Enums\AccommodationType;
use App\Enums\ContentStatus;
use App\Models\Concerns\HasBookings;
use App\Models\Concerns\HasFavorites;
use App\Models\Concerns\HasItineraryItems;
use App\Models\Concerns\HasReviews;
use Database\Factories\AccommodationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Accommodation extends Model
{
    /** @use HasFactory<AccommodationFactory> */
    use HasBookings, HasFactory, HasFavorites, HasItineraryItems, HasReviews;

    protected $fillable = [
        'manager_id',
        'name',
        'slug',
        'type',
        'description',
        'region_id',
        'village_id',
        'address',
        'latitude',
        'longitude',
        'phone',
        'amenities',
        'gallery',
        'check_in_time',
        'check_out_time',
        'price_per_night',
        'status',
        'published_at',
        'rejection_reason',
        'is_recommended',
        'recommendation_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccommodationType::class,
            'status' => ContentStatus::class,
            'amenities' => 'array',
            'gallery' => 'array',
            'published_at' => 'datetime',
            'price_per_night' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_recommended' => 'boolean',
            'recommendation_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Accommodation $accommodation): void {
            if (blank($accommodation->slug)) {
                $accommodation->slug = Str::slug($accommodation->name);
            }

            if ($accommodation->status === ContentStatus::Published && $accommodation->published_at === null) {
                $accommodation->published_at = now();
            }
        });
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'village_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published->value);
    }

    public function scopeOfType(Builder $query, AccommodationType $type): Builder
    {
        return $query->where('type', $type->value);
    }
}
