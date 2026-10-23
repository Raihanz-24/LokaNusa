<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\UmkmBusiness;
use App\Services\QrCodeService;
use App\Services\QrTargetService;

/**
 * Papan QR siap cetak (A4/A5) untuk ditempel di lokasi wisata.
 *
 * Halaman ini publik karena tujuannya dicetak oleh pengelola lapangan; QR
 * yang sama juga bisa diunduh sebagai PNG lewat `/q/{token}/gambar`.
 */
class QrPosterController extends Controller
{
    public function __construct(
        private readonly QrCodeService $service,
        private readonly QrTargetService $targets,
    ) {}

    public function show(string $token)
    {
        $qrCode = $this->targets->resolve($token);

        abort_if($qrCode === null, 404);

        $content = $qrCode->qrCodeable;

        return view('qr.poster', [
            'title' => (string) ($content->name ?? $content->title ?? 'Konten'),
            'label' => match (true) {
                $content instanceof Destination => 'Destinasi Wisata',
                $content instanceof UmkmBusiness => 'UMKM & Kuliner',
                $content instanceof Accommodation => 'Penginapan',
                $content instanceof CultureArticle => 'Budaya',
                $content instanceof Event => 'Event',
                default => 'LokaNusa',
            },
            'qrImage' => $this->service->imageDataUri($content, 12),
            'qrUrl' => $this->service->urlFor($content),
            'publicUrl' => $this->targets->publicUrl($content),
        ]);
    }
}
