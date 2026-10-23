<?php

namespace App\Models;

use Database\Factories\QrCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Kode QR Smart Tourism untuk satu konten (polimorfik).
 *
 * Satu konten memiliki tepat satu QR. Token acak 16 karakter dipakai pada
 * URL publik `/q/{token}` yang mencatat pemindaian nyata.
 *
 * @property int $id
 * @property string $token
 * @property string $qr_codeable_type
 * @property int $qr_codeable_id
 */
class QrCode extends Model
{
    /** @use HasFactory<QrCodeFactory> */
    use HasFactory;

    protected $fillable = [
        'token',
        'qr_codeable_type',
        'qr_codeable_id',
    ];

    public function qrCodeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scans(): HasMany
    {
        return $this->hasMany(QrScan::class);
    }

    /**
     * Token acak yang tidak bertabrakan dengan kode lain.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::lower(Str::random(16));
        } while (self::query()->where('token', $token)->exists());

        return $token;
    }

    /**
     * URL publik yang ditanam pada gambar QR.
     */
    public function url(): string
    {
        return route('qr.scan', $this->token);
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }
}
