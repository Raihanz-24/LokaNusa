<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasFavorites;
use App\Models\Concerns\HasItineraryItems;
use App\Models\Concerns\HasReviews;
use Database\Factories\DestinationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Destination extends Model
{
    /** @use HasFactory<DestinationFactory> */
    use HasFactory, HasFavorites, HasItineraryItems, HasReviews;

    protected $fillable = [
        'name',
        'slug',
        'qr_token',
        'description',
        'region_id',
        'village_id',
        'destination_category_id',
        'manager_id',
        'status',
        'published_at',
        'address',
        'latitude',
        'longitude',
        'opening_hours',
        'facilities',
        'gallery',
        'ticket_price',
        'rejection_reason',
        'is_recommended',
        'recommendation_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'opening_hours' => 'array',
            'facilities' => 'array',
            'gallery' => 'array',
            'ticket_price' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_recommended' => 'boolean',
            'recommendation_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Destination $destination): void {
            if (blank($destination->qr_token)) {
                $destination->qr_token = self::generateQrToken();
            }
        });

        static::saving(function (Destination $destination): void {
            if (blank($destination->slug)) {
                $destination->slug = Str::slug($destination->name);
            }

            if ($destination->status === ContentStatus::Published && $destination->published_at === null) {
                $destination->published_at = now();
            }
        });
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'village_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DestinationCategory::class, 'destination_category_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Token QR publik (dibuat otomatis saat konten pertama kali disimpan).
     */
    public function ensureQrToken(): string
    {
        if (blank($this->qr_token)) {
            $this->qr_token = self::generateQrToken();
            $this->saveQuietly();
        }

        return (string) $this->qr_token;
    }

    /**
     * Token acak yang tidak bertabrakan dengan destinasi lain.
     */
    public static function generateQrToken(): string
    {
        do {
            $token = Str::lower(Str::random(16));
        } while (self::query()->where('qr_token', $token)->exists());

        return $token;
    }

    /**
     * URL publik yang ditanam pada QR.
     */
    public function qrUrl(): string
    {
        return route('qr.scan', $this->ensureQrToken());
    }

    public function scans(): HasMany
    {
        return $this->hasMany(QrScan::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published->value);
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published;
    }
}
