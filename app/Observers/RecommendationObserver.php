<?php

namespace App\Observers;

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\Event;
use App\Models\UmkmBusiness;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;

/**
 * Memicu notifikasi in-app ketika admin menandai konten sebagai
 * "Rekomendasi" (`is_recommended` berubah dari non-aktif menjadi aktif).
 *
 * Pendekatan observer dipilih agar pemicu berlaku dari mana pun flag diubah
 * (form Filament, aksi tabel, atau kode lain) — logika bisnis tetap di service.
 */
class RecommendationObserver
{
    public function updated(Model $content): void
    {
        if (! $this->supports($content)) {
            return;
        }

        // Hanya saat flag baru diaktifkan (bukan saat dimatikan).
        if (! $content->wasChanged('is_recommended') || ! $content->is_recommended) {
            return;
        }

        app(NotificationService::class)->contentHighlighted($content);
    }

    private function supports(Model $content): bool
    {
        return $content instanceof Destination
            || $content instanceof UmkmBusiness
            || $content instanceof Accommodation
            || $content instanceof Event;
    }
}
