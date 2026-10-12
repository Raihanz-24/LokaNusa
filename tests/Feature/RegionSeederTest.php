<?php

namespace Tests\Feature;

use App\Models\Region;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_mengisi_wilayah_indonesia_dengan_benar(): void
    {
        $this->seed(RegionSeeder::class);

        $this->assertSame(38, Region::query()->whereNull('parent_id')->count());
        $this->assertSame(514, Region::query()->where('type', 'kabupaten_kota')->count());
        $this->assertSame(7285, Region::query()->where('type', 'kecamatan')->count());

        $bandung = Region::query()->where('code', '32.73')->first();
        $this->assertNotNull($bandung);
        $this->assertSame('kabupaten_kota', $bandung->type->value);
        $this->assertSame('Jawa Barat', $bandung->parent?->name);

        $kecamatan = Region::query()->where('code', '32.73.01')->first();
        $this->assertNotNull($kecamatan);
        $this->assertSame('Sukasari', $kecamatan->name);
        $this->assertSame('Sukasari, Kota Bandung, Jawa Barat', $kecamatan->fullName());

        // Tidak ada wilayah yatim.
        $this->assertSame(0, Region::query()->whereNotNull('parent_id')->whereDoesntHave('parent')->count());
    }

    public function test_seeder_idempoten(): void
    {
        $this->seed(RegionSeeder::class);
        $first = Region::query()->count();

        $this->seed(RegionSeeder::class);

        $this->assertSame($first, Region::query()->count());
    }
}
