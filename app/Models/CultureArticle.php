<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\CultureType;
use App\Models\Concerns\HasQrCode;
use Database\Factories\CultureArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CultureArticle extends Model
{
    /** @use HasFactory<CultureArticleFactory> */
    use HasFactory, HasQrCode;

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'type',
        'excerpt',
        'content',
        'cover_image',
        'region_id',
        'village_id',
        'status',
        'published_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => CultureType::class,
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CultureArticle $article): void {
            if (blank($article->slug)) {
                $article->slug = Str::slug($article->title);
            }

            if ($article->status === ContentStatus::Published && $article->published_at === null) {
                $article->published_at = now();
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
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

    public function scopeOfType(Builder $query, CultureType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags((string) $this->content));

        return max(1, (int) ceil($words / 200));
    }
}
