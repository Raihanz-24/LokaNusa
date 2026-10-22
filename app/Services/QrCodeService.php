<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\QrScan;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Carbon;

/**
 * Layanan QR Smart Tourism.
 *
 * - Membuat gambar QR (data URI PNG) untuk URL publik destinasi.
 * - Mencatat pemindaian nyata ke tabel `qr_scans`.
 * - Menghitung statistik pemindaian (tanpa data rekaan).
 */
class QrCodeService
{
    /**
     * Data URI PNG berisi QR yang mengarah ke `/q/{token}`.
     *
     * @param  int  $scale  Ukuran modul (px) — makin besar makin tajam.
     */
    public function imageDataUri(Destination $destination, int $scale = 8): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => true,
            'scale' => $scale,
            'eccLevel' => QRCode::ECC_M,
            'imageBase64' => true,
        ]);

        return (new QRCode($options))->render($destination->qrUrl());
    }

    /**
     * Simpan satu pemindaian. Pemindaian ganda beruntun oleh pengunjung yang
     * sama dalam 30 detik tidak dicatat dua kali (menghindari duplikasi
     * akibat refresh/preview tautan).
     */
    public function record(Destination $destination, ?int $userId, ?string $ip, ?string $userAgent): bool
    {
        $since = Carbon::now()->subSeconds(30);

        $duplicate = QrScan::query()
            ->where('destination_id', $destination->getKey())
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
            'destination_id' => $destination->getKey(),
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
            'scanned_at' => now(),
        ]);

        return true;
    }

    /**
     * Statistik pemindaian sebuah destinasi.
     *
     * @return array{total: int, today: int, last_7_days: int, last_30_days: int, unique_visitors: int, last_scan_at: ?Carbon}
     */
    public function stats(Destination $destination): array
    {
        $base = QrScan::query()->where('destination_id', $destination->getKey());

        return [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->where('scanned_at', '>=', Carbon::today())->count(),
            'last_7_days' => (clone $base)->where('scanned_at', '>=', Carbon::now()->subDays(7))->count(),
            'last_30_days' => (clone $base)->where('scanned_at', '>=', Carbon::now()->subDays(30))->count(),
            'unique_visitors' => (clone $base)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'last_scan_at' => (clone $base)->max('scanned_at') !== null
                ? Carbon::parse((clone $base)->max('scanned_at'))
                : null,
        ];
    }

    /**
     * Pemindaian per hari selama `$days` hari terakhir (untuk grafik/daftar).
     *
     * @return array<int, array{date: string, label: string, count: int}>
     */
    public function dailyCounts(Destination $destination, int $days = 14): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $rows = QrScan::query()
            ->where('destination_id', $destination->getKey())
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
