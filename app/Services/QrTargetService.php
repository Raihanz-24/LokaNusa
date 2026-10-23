<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Accommodation;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\QrCode;
use App\Models\UmkmBusiness;
use Illuminate\Database\Eloquent\Model;

/**
 * Pemetaan tipe konten ke URL publiknya untuk QR Smart Tourism.
 *
 * Token QR sudah unik di tabel `qr_codes`, sehingga saat pemindaian cukup
 * mencari QR berdasarkan token lalu memutuskan halaman publik mana yang
 * dibuka. Konten yang belum terbit tidak pernah dialihkan (dan pemindaiannya
 * tidak dicatat).
 */
class QrTargetService
{
    /**
     * Peta model => nama rute publik.
     *
     * @return array<class-string<Model>, string>
     */
    public function routes(): array
    {
        return [
            Destination::class => 'destinations.show',
            UmkmBusiness::class => 'umkm.show',
            Accommodation::class => 'accommodations.show',
            CultureArticle::class => 'culture.show',
            Event::class => 'events.show',
        ];
    }

    /**
     * URL publik halaman detail konten.
     */
    public function publicUrl(Model $content): string
    {
        foreach ($this->routes() as $model => $route) {
            if ($content instanceof $model) {
                return route($route, $content->slug);
            }
        }

        throw new \InvalidArgumentException(
            'Jenis konten '.$content::class.' belum didukung QR Smart Tourism.'
        );
    }

    /**
     * Apakah konten mendukung QR Smart Tourism.
     */
    public function supports(Model $content): bool
    {
        foreach (array_keys($this->routes()) as $model) {
            if ($content instanceof $model) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cari QR berdasarkan token, hanya untuk konten yang sudah terbit.
     */
    public function resolve(string $token): ?QrCode
    {
        $qrCode = QrCode::query()
            ->where('token', $token)
            ->with('qrCodeable')
            ->first();

        if ($qrCode === null || $qrCode->qrCodeable === null) {
            return null;
        }

        $content = $qrCode->qrCodeable;

        if (! $this->supports($content)) {
            return null;
        }

        if ($content->status !== ContentStatus::Published) {
            return null;
        }

        return $qrCode;
    }
}
