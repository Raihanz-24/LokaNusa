<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan pemakaian token AI untuk satu panggilan.
 *
 * Dipakai admin untuk memantau total token yang dihabiskan seluruh pengguna,
 * serta per pengguna bila diperlukan.
 */
class AiUsage extends Model
{
    protected $fillable = [
        'user_id',
        'context',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
