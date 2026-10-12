<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Seed data wilayah Indonesia dari berkas offline `database/data/wilayah.json`.
 *
 * Idempoten (upsert berdasarkan `code`) — aman dijalankan berulang.
 * Diproses per tingkat (provinsi → kab/kota → kecamatan) agar induk selalu
 * tersedia lebih dulu. Slug dibuat unik di bawah induk yang sama.
 *
 * Untuk memperbarui data: `php artisan wilayah:import` lalu `wilayah:export`.
 */
class RegionSeeder extends Seeder
{
    /** @var array<int, string> */
    private array $levelOrder = ['provinsi', 'kabupaten_kota', 'kecamatan', 'desa_kelurahan'];

    public function run(): void
    {
        $path = database_path('data/wilayah.json');

        if (! File::exists($path)) {
            $this->command?->warn("Berkas wilayah tidak ditemukan: {$path}. Lewati.");

            return;
        }

        $rows = json_decode((string) File::get($path), true)['rows'] ?? [];

        // Kelompokkan per tingkat.
        $byType = [];
        foreach ($rows as $row) {
            $byType[$row[2]][] = $row;
        }

        $now = now()->toDateTimeString();
        $total = 0;

        foreach ($this->levelOrder as $level) {
            $group = $byType[$level] ?? [];

            if ($group === []) {
                continue;
            }

            // Induk (tingkat sebelumnya) sudah tersimpan → peta kode → id.
            $codeToId = DB::table('regions')->pluck('id', 'code')->all();
            $slugCounters = [];
            $payload = [];

            foreach ($group as [$code, $name, $type, $parentCode]) {
                $parentId = $parentCode ? ($codeToId[$parentCode] ?? null) : null;
                $slug = $this->uniqueSlug($name, $parentCode, $slugCounters);

                $payload[] = [
                    'parent_id' => $parentId,
                    'type' => $type,
                    'name' => $name,
                    'slug' => $slug,
                    'code' => $code,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($payload, 1000) as $chunk) {
                DB::table('regions')->upsert(
                    $chunk,
                    ['code'],
                    ['parent_id', 'type', 'name', 'slug', 'is_active', 'updated_at'],
                );
            }

            $total += count($payload);
            $this->command?->info('Level '.$level.': '.count($payload).' baris (total '.$total.').');
        }

        $this->command?->info("Wilayah di-seed total: {$total} baris.");
    }

    /**
     * Hasilkan slug unik di bawah induk yang sama (tambahkan suffix bila bentrok).
     *
     * @param  array<string, int>  $counters
     */
    private function uniqueSlug(string $name, ?string $parentCode, array &$counters): string
    {
        $base = Str::slug($name) ?: 'wilayah';
        $key = ($parentCode ?? 'root').'|'.$base;
        $seen = $counters[$key] ?? 0;
        $counters[$key] = $seen + 1;

        return $seen === 0 ? $base : "{$base}-".($seen + 1);
    }
}
