<?php

namespace App\Console\Commands;

use App\Enums\RegionType;
use App\Models\Region;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Impor data wilayah Indonesia (provinsi → kabupaten/kota → kecamatan)
 * dari API statis wilayah.id ke tabel `regions`.
 *
 * Idempoten: baris dicocokkan berdasarkan `code`. Jalankan berulang aman.
 *
 * Contoh:
 *   php artisan wilayah:import                 # sampai kecamatan
 *   php artisan wilayah:import --depth=kabupaten
 *   php artisan wilayah:import --fresh         # hapus wilayah dulu (hati-hati)
 */
class ImportWilayahCommand extends Command
{
    protected $signature = 'wilayah:import
                            {--base=https://wilayah.id/api : Base URL API}
                            {--depth=kecamatan : Kedalaman (provinsi|kabupaten|kecamatan)}
                            {--fresh : Hapus semua wilayah sebelum impor}';

    protected $description = 'Impor data wilayah Indonesia (provinsi, kabupaten/kota, kecamatan).';

    public function handle(): int
    {
        $base = rtrim((string) $this->option('base'), '/');
        $depth = (string) $this->option('depth');

        if (! in_array($depth, ['provinsi', 'kabupaten', 'kecamatan'], true)) {
            $this->error('Kedalaman tidak valid. Pilihan: provinsi, kabupaten, kecamatan.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('Menghapus seluruh data wilayah yang ada...');
            Region::query()->delete();
        }

        try {
            $provinces = $this->fetch("{$base}/provinces.json");
        } catch (Throwable $e) {
            $this->error('Gagal mengambil data provinsi: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Mengimpor '.count($provinces).' provinsi...');
        $regencyCount = 0;
        $districtCount = 0;
        $skipped = 0;

        foreach ($provinces as $province) {
            $provinsiModel = $this->store(
                RegionType::Provinsi,
                null,
                (string) $province['code'],
                (string) $province['name'],
            );

            if ($depth === 'provinsi') {
                continue;
            }

            $regencies = $this->safeFetch("{$base}/regencies/{$province['code']}.json");

            foreach ($regencies as $regency) {
                $regencyModel = $this->store(
                    RegionType::KabupatenKota,
                    $provinsiModel->id,
                    (string) $regency['code'],
                    (string) $regency['name'],
                );

                if ($regencyModel === null) {
                    $skipped++;

                    continue;
                }

                $regencyCount++;

                if ($depth !== 'kecamatan') {
                    continue;
                }

                foreach ($this->safeFetch("{$base}/districts/{$regency['code']}.json") as $district) {
                    $districtModel = $this->store(
                        RegionType::Kecamatan,
                        $regencyModel->id,
                        (string) $district['code'],
                        (string) $district['name'],
                    );

                    if ($districtModel === null) {
                        $skipped++;

                        continue;
                    }

                    $districtCount++;
                }
            }
        }

        $this->info('Selesai. Provinsi: '.count($provinces).", Kab/Kota: {$regencyCount}, Kecamatan: {$districtCount}.");

        if ($skipped > 0) {
            $this->warn("Dilewati (duplikasi slug dalam induk yang sama): {$skipped}.");
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetch(string $url): array
    {
        $response = Http::timeout(60)->retry(2, 2000)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()} untuk {$url}");
        }

        $data = $response->json('data');

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function safeFetch(string $url): array
    {
        try {
            return $this->fetch($url);
        } catch (Throwable $e) {
            $this->warn("Lewati {$url}: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Simpan satu wilayah (idempoten berdasarkan kode). Mengembalikan null
     * bila slug duplikat di bawah induk yang sama (dilewati agar impor tetap jalan).
     */
    private function store(RegionType $type, ?int $parentId, string $code, string $name): ?Region
    {
        $slug = Str::slug($name);

        // Pastikan slug unik di bawah induk yang sama.
        $collision = Region::query()
            ->where('parent_id', $parentId)
            ->where('slug', $slug)
            ->where('code', '!=', $code)
            ->exists();

        if ($collision) {
            $slug .= '-'.str_replace('.', '', $code);
        }

        try {
            return Region::query()->updateOrCreate(
                ['code' => $code],
                [
                    'parent_id' => $parentId,
                    'type' => $type,
                    'name' => $name,
                    'slug' => $slug,
                    'is_active' => true,
                ],
            );
        } catch (Throwable) {
            return null;
        }
    }
}
