<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kuota harian prompt AI Trip Planner per pengguna.
 */
class TripPlannerUsage extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'prompts',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'prompts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
