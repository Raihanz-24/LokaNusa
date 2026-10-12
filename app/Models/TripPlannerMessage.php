<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu pesan dalam percakapan AI Trip Planner milik pengguna.
 */
class TripPlannerMessage extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'content',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }
}
