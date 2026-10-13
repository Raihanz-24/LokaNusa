<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Notifikasi dalam aplikasi (in-app) milik seorang pengguna.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property ?string $body
 * @property ?string $url
 * @property ?string $icon
 * @property ?Carbon $read_at
 */
class AppNotification extends Model
{
    use HasFactory;

    /**
     * Kategori notifikasi (kolom `type`).
     */
    public const TYPE_MODERATION = 'moderation';

    public const TYPE_EVENT = 'event';

    public const TYPE_HIGHLIGHT = 'highlight';

    public const TYPE_AI = 'ai';

    public const TYPE_BOOKING = 'booking';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'url',
        'icon',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * @param  array<int, string>  $types
     */
    public function scopeOfTypes(Builder $query, array $types): Builder
    {
        return $query->whereIn('type', $types);
    }
}
