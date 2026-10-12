<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Item "Rekomendasi untukmu" pada beranda (polymorphic lintas modul:
 * destinasi, UMKM, penginapan).
 */
class RecommendationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'recommendable_type',
        'recommendable_id',
        'sort_order',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function recommendable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
