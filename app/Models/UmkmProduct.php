<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasBookings;
use Database\Factories\UmkmProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UmkmProduct extends Model
{
    /** @use HasFactory<UmkmProductFactory> */
    use HasBookings, HasFactory;

    protected $fillable = [
        'umkm_business_id',
        'name',
        'slug',
        'description',
        'price',
        'unit',
        'image',
        'is_available',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'is_available' => 'boolean',
            'price' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (UmkmProduct $product): void {
            if (blank($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(UmkmBusiness::class, 'umkm_business_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published->value);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }
}
