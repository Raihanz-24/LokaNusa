<?php

namespace App\Filament\Concerns;

use App\Models\Region;
use App\Support\RegionResolver;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Auto-isi kecamatan/desa + alamat dari klik peta (reverse geocoding Nominatim).
 *
 * Dipakai oleh halaman Create/Edit Filament yang memiliki properti `$data`.
 */
trait ReverseGeocodesAddress
{
    /**
     * Dipanggil dari sisi klien saat pengguna mengeklik peta.
     */
    public function reverseGeocode(float $latitude, float $longitude): void
    {
        $this->data['latitude'] = $latitude;
        $this->data['longitude'] = $longitude;

        $address = $this->lookupAddress($latitude, $longitude);

        if ($address === null) {
            Notification::make()
                ->title('Koordinat tersimpan')
                ->body('Alamat tidak dapat diterjemahkan otomatis (offline/limit). Isi kecamatan & desa manual.')
                ->warning()
                ->send();

            return;
        }

        $names = collect($address)
            ->filter(fn ($value): bool => is_string($value) && filled($value))
            ->values()
            ->all();

        $kecamatan = $this->matchKecamatan($names);
        $desa = $kecamatan ? $this->matchDesa($kecamatan->getKey(), $names) : null;

        if ($kecamatan !== null) {
            $this->data['region_id'] = $kecamatan->getKey();
        }

        if ($desa !== null) {
            $this->data['village_id'] = $desa->getKey();
        }

        // Isi alamat lengkap hanya bila masih kosong.
        if (blank($this->data['address'] ?? null) && filled($this->data['village_id'] ?? null)) {
            $this->data['address'] = Str::title($names[0] ?? '');
        }

        if ($kecamatan !== null) {
            Notification::make()
                ->title('Lokasi terdeteksi')
                ->body('Kecamatan: '.$kecamatan->name.($desa ? ' • Desa: '.$desa->name : ''))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Koordinat tersimpan')
                ->body('Kecamatan tidak dikenali dalam kabupaten fokus. Pilih manual.')
                ->warning()
                ->send();
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lookupAddress(float $latitude, float $longitude): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => config('app.name', 'LokaNusa').'-Admin (mailto:admin@lokanusa.test)',
                    'Accept-Language' => 'id',
                ])
                ->retry(1, 500)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 16,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $address = $response->json('address');

            return is_array($address) ? $address : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, string>  $names
     */
    private function matchKecamatan(array $names): ?Region
    {
        foreach ($names as $name) {
            if ($kecamatan = RegionResolver::findKecamatanByName($name)) {
                return $kecamatan;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $names
     */
    private function matchDesa(int $kecamatanId, array $names): ?Region
    {
        foreach ($names as $name) {
            if ($desa = RegionResolver::findDesaByName($kecamatanId, $name)) {
                return $desa;
            }
        }

        return null;
    }
}
