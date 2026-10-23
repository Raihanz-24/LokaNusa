<?php

namespace App\Models\Concerns;

use App\Models\QrCode;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Konten yang dapat diberi QR Smart Tourism.
 *
 * Token QR disimpan terpusat di tabel `qr_codes` (polimorfik), sehingga
 * destinasi, UMKM, penginapan, budaya, dan event memakai mekanisme yang
 * sama. QR dibuat malas (saat pertama dibutuhkan) agar tidak menambah
 * beban pada proses simpan konten.
 */
trait HasQrCode
{
    public function qrCode(): MorphOne
    {
        return $this->morphOne(QrCode::class, 'qr_codeable');
    }

    /**
     * Ambil (atau buat) QR untuk konten ini.
     */
    public function ensureQrCode(): QrCode
    {
        $existing = $this->qrCode;

        if ($existing !== null) {
            return $existing;
        }

        /** @var QrCode $qrCode */
        $qrCode = $this->qrCode()->create([
            'token' => QrCode::generateToken(),
        ]);

        $this->setRelation('qrCode', $qrCode);

        return $qrCode;
    }

    /**
     * Token QR (dibuat bila belum ada).
     */
    public function qrToken(): string
    {
        return $this->ensureQrCode()->token;
    }

    /**
     * URL publik yang ditanam pada QR.
     */
    public function qrUrl(): string
    {
        return $this->ensureQrCode()->url();
    }
}
