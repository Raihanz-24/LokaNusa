<?php

namespace App\Console\Commands;

use App\Enums\RegionType;
use App\Models\AppSetting;
use App\Models\Region;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Impor desa/kelurahan untuk **kabupaten/kota fokus** dari API wilayah.id.
 *
 * Ringan (hanya satu kabupaten), idempoten berdasarkan `code`.
 * Membutuhkan internet. Kabupaten fokus diambil dari AppSetting
 * (`focus_region_id`) bila tidak diberikan lewat --regency.
 *
 * Contoh:
 *   php artisan wilayah:import-villages
 *   php artisan wilayah:import-villages --regency=3509   # kode BPS kabupaten
 */
class ImportVillagesCommand extends Command
{
    protected $signature = 'wilayah:import-villages
                            {--base=https://wilayah.id/api : Base URL API}
                            {--regency= : Kode kabupaten/kota (default: fokus saat ini)}';

    protected $description = 'Impor desa/kelurahan untuk kabupaten/kota fokus.';

    public function handle(): int
    {
        $base = rtrim((string) $this->option('base'), '/');

        $regencyCode = (string) $this->option('regency');

        if ($regencyCode === '') {
            $focus = AppSetting::focusRegion();

            if ($focus === null || blank($focus->code)) {
                $this->error('Kabupaten fokus belum ditetapkan. Setel di /admin/settings atau pakai --regency=KODE.');

                return self::FAILURE;
            }

            $regencyCode = $focus->code;
        }

        $regency = Region::query()->where('code', $regencyCode)->first();

        if ($regency === null) {
            $this->error("Kabupaten dengan kode {$regencyCode} tidak ditemukan di tabel regions.");

            return self::FAILURE;
        }

        $districts = Region::query()
            ->where('parent_id', $regency->id)
            ->where('type', RegionType::Kecamatan->value)
            ->orderBy('code')
            ->get();

        if ($districts->isEmpty()) {
            $this->error('Tidak ada kecamatan untuk kabupaten ini. Jalankan wilayah:import dulu.');

            return self::FAILURE;
        }

        $this->info("Mengimpor desa untuk {$regency->name} ({$districts->count()} kecamatan)...");

        $count = 0;
        $skipped = 0;

        foreach ($districts as $district) {
            foreach ($this->safeFetch("{$base}/villages/{$district->code}.json") as $village) {
                $model = $this->store($district->id, (string) $village['code'], (string) $village['name']);

                if ($model === null) {
                    $skipped++;

                    continue;
                }

                $count++;
            }
        }

        $this->info("Selesai. Desa tersimpan: {$count}.");

        if ($skipped > 0) {
            $this->warn("Dilewati: {$skipped}.");
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

    private function store(int $districtId, string $code, string $name): ?Region
    {
        $slug = Str::slug($name);

        $collision = Region::query()
            ->where('parent_id', $districtId)
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
                    'parent_id' => $districtId,
                    'type' => RegionType::DesaKelurahan,
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
