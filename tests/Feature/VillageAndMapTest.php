<?php

namespace Tests\Feature;

use App\Enums\RegionType;
use App\Filament\Resources\Destinations\Pages\CreateDestination;
use App\Models\AppSetting;
use App\Models\Destination;
use App\Models\Region;
use App\Models\UmkmBusiness;
use App\Models\User;
use App\Support\RegionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VillageAndMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_desa_dan_pencarian_nama(): void
    {
        $kabupaten = Region::factory()->kabupatenKota()->create();
        $kecamatan = Region::factory()->kecamatan($kabupaten)->create(['name' => 'Paiton']);
        Region::factory()->create(['parent_id' => $kecamatan->id, 'type' => RegionType::DesaKelurahan, 'name' => 'Sumber Rejo']);

        AppSetting::setFocusRegion($kabupaten->id);

        $this->assertCount(1, RegionResolver::desaOptions($kecamatan->id));
        $this->assertNotEmpty(RegionResolver::desaIds($kecamatan->id));

        $found = RegionResolver::findKecamatanByName('Kecamatan Paiton');
        $this->assertNotNull($found);
        $this->assertSame($kecamatan->id, $found->id);

        $desa = RegionResolver::findDesaByName($kecamatan->id, 'Kelurahan Sumber Rejo');
        $this->assertNotNull($desa);
        $this->assertSame('Sumber Rejo', $desa->name);
    }

    public function test_dropdown_desa_bergantung_pada_kecamatan(): void
    {
        $kabupaten = Region::factory()->kabupatenKota()->create();
        $kecamatan = Region::factory()->kecamatan($kabupaten)->create(['name' => 'Kecamatan A']);
        $kecamatanLain = Region::factory()->kecamatan($kabupaten)->create(['name' => 'Kecamatan B']);
        Region::factory()->create(['parent_id' => $kecamatan->id, 'type' => RegionType::DesaKelurahan, 'name' => 'Desa Dalam']);
        Region::factory()->create(['parent_id' => $kecamatanLain->id, 'type' => RegionType::DesaKelurahan, 'name' => 'Desa Luar']);

        AppSetting::setFocusRegion($kabupaten->id);

        $this->actingAs(User::factory()->superAdmin()->create());

        Livewire::test(CreateDestination::class)
            ->fillForm(['region_id' => $kecamatan->id])
            ->assertSuccessful()
            ->assertSee('Desa Dalam');
    }

    public function test_halaman_peta_menampilkan_penanda_lokasi_terbit(): void
    {
        Destination::factory()->published()->create([
            'name' => 'Pantai Peta',
            'latitude' => -7.98,
            'longitude' => 112.63,
        ]);
        UmkmBusiness::factory()->published()->create([
            'name' => 'Warung Peta',
            'latitude' => -7.99,
            'longitude' => 112.64,
        ]);
        // Draft tidak boleh muncul.
        Destination::factory()->create(['name' => 'Destinasi Draf Peta', 'latitude' => -7, 'longitude' => 112]);

        $this->actingAs(User::factory()->wisatawan()->create())->get('/peta')
            ->assertSuccessful()
            ->assertSee('Pantai Peta')
            ->assertSee('Warung Peta')
            ->assertDontSee('Destinasi Draf Peta');
    }

    public function test_filter_peta_berdasarkan_tipe(): void
    {
        Destination::factory()->published()->create(['name' => 'Destinasi Filter', 'latitude' => -7.9, 'longitude' => 112.6]);
        UmkmBusiness::factory()->published()->create(['name' => 'Umkm Filter', 'latitude' => -7.8, 'longitude' => 112.5]);

        $this->actingAs(User::factory()->wisatawan()->create())->get('/peta?tipe=destinasi')
            ->assertSuccessful()
            ->assertSee('Destinasi Filter')
            ->assertDontSee('Umkm Filter');
    }
}
