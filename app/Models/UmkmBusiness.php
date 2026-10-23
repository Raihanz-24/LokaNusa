<?php

namespace App\Models;

use App\Enums\BusinessType;
use App\Enums\ContentStatus;
use App\Models\Concerns\HasFavorites;
use App\Models\Concerns\HasItineraryItems;
use App\Models\Concerns\HasQrCode;
use App\Models\Concerns\HasReviews;
use Database\Factories\UmkmBusinessFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UmkmBusiness extends Model
{
    /** @use HasFactory<UmkmBusinessFactory> */
    use HasFactory, HasFavorites, HasItineraryItems, HasQrCode, HasReviews;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'business_type',
        'description',
        'region_id',
        'village_id',
        'address',
        'latitude',
        'longitude',
        'phone',
        'opening_hours',
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
            'business_type' => BusinessType::class,
            'status' => ContentStatus::class,
            'opening_hours' => 'array',
            'gallery' => 'array',
            'published_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_recommended' => 'boolean',
            'recommendation_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (UmkmBusiness $business): void {
            if (blank($business->slug)) {
                $business->slug = Str::slug($business->name);
            }

            if ($business->status === ContentStatus::Published && $business->published_at === null) {
                $business->published_at = now();
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'village_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(UmkmProduct::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published->value);
    }

    public function scopeOfType(Builder $query, BusinessType $type): Builder
    {
        return $query->where('business_type', $type->value);
    }

    public function isKuliner(): bool
    {
        return $this->business_type === BusinessType::Kuliner;
    }
}
