<?php

namespace App\Http\Controllers;

use App\Services\QrCodeService;
use App\Services\QrTargetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * QR Smart Tourism — pintu masuk publik dari pemindaian QR.
 *
 * Rute ini sengaja PUBLIK (di luar `public.auth`) agar pemindaian oleh tamu
 * tetap tercatat. Tamu lalu diarahkan ke login dengan URL tujuan tersimpan.
 */
class QrController extends Controller
{
    public function __construct(
        private readonly QrCodeService $service,
        private readonly QrTargetService $targets,
    ) {}

    /**
     * Catat pemindaian lalu alihkan ke halaman detail konten.
     */
    public function scan(Request $request, string $token): RedirectResponse
    {
        $qrCode = $this->targets->resolve($token);

        // Token tidak dikenal / konten belum terbit: jangan catat apa pun.
        if ($qrCode === null) {
            abort(404);
        }

        $this->service->record(
            $qrCode,
            $request->user()?->getKey(),
            $request->ip(),
            $request->userAgent(),
        );

        $url = $this->targets->publicUrl($qrCode->qrCodeable);

        // Tamu diarahkan ke login dengan URL tujuan agar setelah masuk
        // langsung mendarat di halaman konten.
        if ($request->user() === null) {
            $request->session()->put('url.intended', $url);

            return redirect()->route('login');
        }

        return redirect()->to($url);
    }

    /**
     * Berkas gambar QR (PNG) untuk dicetak/ditempel di lokasi.
     */
    public function image(string $token)
    {
        $qrCode = $this->targets->resolve($token);

        abort_if($qrCode === null, 404);

        $dataUri = $this->service->imageDataUri($qrCode->qrCodeable);
        $binary = base64_decode((string) substr($dataUri, strpos($dataUri, ',') + 1));

        $slug = $qrCode->qrCodeable->slug ?? (string) $qrCode->token;

        return response($binary, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-'.$slug.'.png"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
