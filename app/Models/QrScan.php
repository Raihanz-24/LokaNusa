<?php

namespace App\Models;

use Database\Factories\QrScanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Catatan satu pemindaian QR (Smart Tourism).
 *
 * Setiap baris = satu kunjungan nyata lewat QR; dipakai untuk statistik
 * pemindaian (tanpa data rekaan).
 *
 * @property int $id
 * @property int $qr_code_id
 * @property ?int $user_id
 * @property ?string $ip_address
 * @property ?string $user_agent
 * @property Carbon $scanned_at
 */
class QrScan extends Model
{
    /** @use HasFactory<QrScanFactory> */
    use HasFactory;

    protected $fillable = [
        'qr_code_id',
        'user_id',
        'ip_address',
        'user_agent',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSince(Builder $query, Carbon $since): Builder
    {
        return $query->where('scanned_at', '>=', $since);
    }
}
