<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Ulasan wisatawan (polymorphic) dengan moderasi ContentStatus.
 *
 * Alur: pending (dikirim) → published (disetujui) | rejected (ditolak).
 */
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comment',
        'status',
        'published_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published->value);
    }
}
