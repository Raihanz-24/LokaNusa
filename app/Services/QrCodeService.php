<?php

namespace App\Services;

use App\Models\Concerns\HasQrCode;
use App\Models\QrCode;
use App\Models\QrScan;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode as QrRenderer;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Layanan QR Smart Tourism (generik untuk semua konten).
 *
 * - Membuat gambar QR (data URI PNG) untuk URL publik konten.
 * - Mencatat pemindaian nyata ke tabel `qr_scans`.
 * - Menghitung statistik pemindaian (tanpa data rekaan).
 *
 * Token disimpan pada tabel `qr_codes` (polimorfik) lewat trait
 * {@see HasQrCode}, sehingga destinasi, UMKM, penginapan, budaya, dan event
 * memakai mekanisme yang sama.
 */
class QrCodeService
{
    /**
     * Data URI PNG berisi QR yang mengarah ke `/q/{token}`.
     *
     * @param  int  $scale  Ukuran modul (px) — makin besar makin tajam.
     */
    public function imageDataUri(Model $content, int $scale = 8): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => true,
            'scale' => $scale,
            'eccLevel' => QrRenderer::ECC_M,
            'imageBase64' => true,
        ]);

        return (new QrRenderer($options))->render($this->urlFor($content));
    }

    /**
     * URL publik yang ditanam pada QR konten.
     */
    public function urlFor(Model $content): string
    {
        return $this->qrCodeFor($content)->url();
    }

    /**
     * Ambil (atau buat) QR untuk konten.
     */
    public function qrCodeFor(Model $content): QrCode
    {
        if (! method_exists($content, 'ensureQrCode')) {
            throw new \InvalidArgumentException(
                'Konten '.$content::class.' tidak mendukung QR (trait HasQrCode belum dipakai).'
            );
        }

        return $content->ensureQrCode();
    }

    /**
     * Simpan satu pemindaian. Pemindaian ganda beruntun oleh pengunjung yang
     * sama dalam 30 detik tidak dicatat dua kali (menghindari duplikasi
     * akibat refresh/preview tautan).
     */
    public function record(QrCode $qrCode, ?int $userId, ?string $ip, ?string $userAgent): bool
    {
        $since = Carbon::now()->subSeconds(30);

        $duplicate = QrScan::query()
            ->where('qr_code_id', $qrCode->getKey())
            ->where('scanned_at', '>=', $since)
            ->when(
                $userId !== null,
                fn ($query) => $query->where('user_id', $userId),
                fn ($query) => $query->whereNull('user_id')->where('ip_address', $ip),
            )
            ->exists();

        if ($duplicate) {
            return false;
        }

        QrScan::query()->create([
            'qr_code_id' => $qrCode->getKey(),
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
            'scanned_at' => now(),
        ]);

        return true;
    }

    /**
     * Statistik pemindaian sebuah konten.
     *
     * @return array{total: int, today: int, last_7_days: int, last_30_days: int, unique_visitors: int, last_scan_at: ?Carbon}
     */
    public function stats(Model $content): array
    {
        $qrCode = $this->qrCodeFor($content);
        $base = QrScan::query()->where('qr_code_id', $qrCode->getKey());

        $lastScan = (clone $base)->max('scanned_at');

        return [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->where('scanned_at', '>=', Carbon::today())->count(),
            'last_7_days' => (clone $base)->where('scanned_at', '>=', Carbon::now()->subDays(7))->count(),
            'last_30_days' => (clone $base)->where('scanned_at', '>=', Carbon::now()->subDays(30))->count(),
            'unique_visitors' => (clone $base)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'last_scan_at' => $lastScan !== null ? Carbon::parse($lastScan) : null,
        ];
    }

    /**
     * Pemindaian per hari selama `$days` hari terakhir (untuk grafik/daftar).
     *
     * @return array<int, array{date: string, label: string, count: int}>
     */
    public function dailyCounts(Model $content, int $days = 14): array
    {
        $qrCode = $this->qrCodeFor($content);
        $start = Carbon::today()->subDays($days - 1);

        $rows = QrScan::query()
            ->where('qr_code_id', $qrCode->getKey())
            ->where('scanned_at', '>=', $start)
            ->get(['scanned_at'])
            ->groupBy(fn (QrScan $scan): string => $scan->scanned_at->format('Y-m-d'))
            ->map(fn ($group): int => $group->count());

        $result = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->format('Y-m-d');

            $result[] = [
                'date' => $key,
                'label' => $date->translatedFormat('d M'),
                'count' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $result;
    }
}
