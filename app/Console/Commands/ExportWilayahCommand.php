<?php

namespace App\Console\Commands;

use App\Models\Region;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * Mengekspor tabel `regions` menjadi berkas JSON ringkas (offline bundle)
 * yang dipakai oleh RegionSeeder untuk seed tanpa internet.
 *
 * Struktur tiap baris: [code, name, type, parent_code].
 */
class ExportWilayahCommand extends Command
{
    protected $signature = 'wilayah:export
                            {--path=database/data/wilayah.json : Lokasi berkas keluaran}';

    protected $description = 'Ekspor data wilayah ke berkas JSON (untuk seeding offline).';

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));

        $regions = Region::query()
            ->orderByRaw("FIELD(type, 'provinsi', 'kabupaten_kota', 'kecamatan', 'desa_kelurahan')")
            ->orderBy('code')
            ->get(['code', 'name', 'type', 'parent_id']);

        $parentCodes = Region::query()->pluck('code', 'id');

        $rows = $regions->map(fn (Region $region): array => [
            $region->code,
            $region->name,
            $region->type->value,
            $region->parent_id ? ($parentCodes[$region->parent_id] ?? null) : null,
        ])->all();

        $payload = [
            'source' => 'wilayah.id',
            'generated_at' => now()->toIso8601String(),
            'count' => count($rows),
            'rows' => $rows,
        ];

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('Berkas wilayah diekspor: '.$path.' ('.count($rows).' baris)');

        $sqlPath = preg_replace('/\.json$/', '.sql', $path) ?: $path.'.sql';
        $this->writeSql($sqlPath, $regions, $parentCodes);

        $this->info('Dump SQL (import via phpMyAdmin setelah migrate): '.$sqlPath);

        return self::SUCCESS;
    }

    /**
     * Tulis dump SQL (DELETE + INSERT) untuk tabel `regions`.
     * Diasumsikan tabel sudah dibuat oleh migrasi.
     *
     * @param  Collection<int, Region>  $regions
     * @param  Collection<int, string>  $parentCodes
     */
    private function writeSql(string $path, $regions, $parentCodes): void
    {
        $now = now()->toDateTimeString();
        $lines = [
            '-- LokaNusa: data wilayah Indonesia (wilayah.id)',
            '-- Jalankan migrasi (`php artisan migrate`) lebih dulu, lalu impor file ini.',
            'SET NAMES utf8mb4;',
            'SET FOREIGN_KEY_CHECKS=0;',
            'TRUNCATE TABLE `regions`;',
        ];

        foreach ($regions as $region) {
            $parent = $region->parent_id ? ($parentCodes[$region->parent_id] ?? null) : null;
            $parentId = $parent !== null
                ? '(SELECT id FROM `regions` WHERE `code` = '.$this->quote($parent).' LIMIT 1)'
                : 'NULL';

            $lines[] = sprintf(
                'INSERT INTO `regions` (`parent_id`,`type`,`name`,`slug`,`code`,`is_active`,`created_at`,`updated_at`) VALUES (%s,%s,%s,%s,%s,1,%s,%s);',
                $parentId,
                $this->quote($region->type->value),
                $this->quote($region->name),
                $this->quote($region->slug),
                $this->quote($region->code),
                $this->quote($now),
                $this->quote($now),
            );
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        File::put($path, implode(PHP_EOL, $lines).PHP_EOL);
    }

    private function quote(?string $value): string
    {
        return "'".str_replace("'", "''", (string) $value)."'";
    }
}
