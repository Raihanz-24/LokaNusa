<?php

namespace App\Http\Controllers;

use App\Enums\ContentStatus;
use App\Models\Destination;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * QR Smart Tourism — pintu masuk publik dari pemindaian QR.
 */
class QrController extends Controller
{
    public function __construct(private readonly QrCodeService $service) {}

    /**
     * Catat pemindaian lalu alihkan ke halaman detail destinasi.
     */
    public function scan(Request $request, string $token): RedirectResponse
    {
        $destination = Destination::published()
            ->where('qr_token', $token)
            ->first();

        // Token tidak dikenal / konten belum terbit: jangan catat apa pun.
        if ($destination === null) {
            abort(404);
        }

        $this->service->record(
            $destination,
            $request->user()?->getKey(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('destinations.show', $destination->slug);
    }

    /**
     * Berkas gambar QR (PNG) untuk dicetak/ditempel di lokasi wisata.
     */
    public function image(Destination $destination)
    {
        abort_unless($destination->status === ContentStatus::Published, 404);

        $dataUri = $this->service->imageDataUri($destination);
        $binary = base64_decode((string) substr($dataUri, strpos($dataUri, ',') + 1));

        return response($binary, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-'.$destination->slug.'.png"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
