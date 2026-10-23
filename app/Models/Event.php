<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\EventType;
use App\Models\Concerns\HasFavorites;
use App\Models\Concerns\HasItineraryItems;
use App\Models\Concerns\HasQrCode;
use App\Models\Concerns\HasReviews;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasFavorites, HasItineraryItems, HasQrCode, HasReviews;

    protected $fillable = [
        'organizer_id',
        'title',
        'slug',
        'event_type',
        'description',
        'starts_at',
        'ends_at',
        'location_name',
        'region_id',
        'village_id',
        'latitude',
        'longitude',
        'is_free',
        'ticket_price',
        'organizer_name',
        'contact',
        'cover_image',
        'gallery',
        'status',
        'published_at',
        'rejection_reason',
        'is_recommended',
        'recommendation_order',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'status' => ContentStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'is_free' => 'boolean',
            'ticket_price' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'gallery' => 'array',
            'is_recommended' => 'boolean',
            'recommendation_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if (blank($event->slug)) {
                $event->slug = Str::slug($event->title);
            }

            if ($event->status === ContentStatus::Published && $event->published_at === null) {
                $event->published_at = now();
            }
        });
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
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

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('starts_at', '>=', now())
                ->orWhere('ends_at', '>=', now());
        });
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('ends_at')->where('starts_at', '<', now())
                ->orWhere('ends_at', '<', now());
        });
    }

    /**
     * Event masih berlangsung atau akan datang.
     */
    public function isUpcoming(): bool
    {
        return ($this->ends_at ?? $this->starts_at) === null
            || ($this->ends_at ?? $this->starts_at)->isFuture();
    }

    public function isOngoing(): bool
    {
        return $this->starts_at !== null
            && $this->starts_at->isPast()
            && $this->ends_at !== null
            && $this->ends_at->isFuture();
    }

    /**
     * Label harga tiket.
     */
    public function priceLabel(): string
    {
        if ($this->is_free) {
            return __('ui.free');
        }

        return $this->ticket_price === null
            ? __('ui.paid')
            : 'Rp'.number_format($this->ticket_price, 0, ',', '.');
    }
}
