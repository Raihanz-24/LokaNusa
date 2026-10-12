<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings;
use App\Filament\Resources\Accommodations\Pages\CreateAccommodation;
use App\Filament\Resources\Destinations\Pages\CreateDestination;
use App\Filament\Resources\UmkmBusinesses\Pages\CreateUmkmBusiness;
use App\Models\AppSetting;
use App\Models\DestinationCategory;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFormsRenderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_halaman_pengaturan_render(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/settings')->assertSuccessful();

        Livewire::test(Settings::class)->assertSuccessful();
    }

    public function test_form_destinasi_render_dengan_dropdown_kecamatan_dan_peta(): void
    {
        $kabupaten = Region::factory()->kabupatenKota()->create(['name' => 'Kabupaten Uji']);
        Region::factory()->kecamatan($kabupaten)->create(['name' => 'Kecamatan Uji']);
        AppSetting::setFocusRegion($kabupaten->id);

        DestinationCategory::factory()->create();

        $this->actingAs($this->admin());

        Livewire::test(CreateDestination::class)
            ->assertSuccessful()
            ->assertSee('Kecamatan Uji')
            ->assertSee('Klik peta');
    }

    public function test_form_umkm_dan_penginapan_render(): void
    {
        $kabupaten = Region::factory()->kabupatenKota()->create();
        AppSetting::setFocusRegion($kabupaten->id);

        $this->actingAs($this->admin());

        Livewire::test(CreateUmkmBusiness::class)->assertSuccessful();
        Livewire::test(CreateAccommodation::class)->assertSuccessful();
    }

    public function test_dropdown_kecamatan_hanya_menampilkan_wilayah_fokus(): void
    {
        $kabupatenA = Region::factory()->kabupatenKota()->create(['name' => 'Kabupaten A']);
        $kabupatenB = Region::factory()->kabupatenKota()->create(['name' => 'Kabupaten B']);
        Region::factory()->kecamatan($kabupatenA)->create(['name' => 'Kecamatan Dalam']);
        Region::factory()->kecamatan($kabupatenB)->create(['name' => 'Kecamatan Luar']);

        AppSetting::setFocusRegion($kabupatenA->id);

        $this->actingAs($this->admin());

        Livewire::test(CreateUmkmBusiness::class)
            ->assertSuccessful()
            ->assertSee('Kecamatan Dalam')
            ->assertDontSee('Kecamatan Luar');
    }

    public function test_lokasi_maps_wajib_diisi(): void
    {
        $kabupaten = Region::factory()->kabupatenKota()->create();
        $kecamatan = Region::factory()->kecamatan($kabupaten)->create();
        AppSetting::setFocusRegion($kabupaten->id);

        $this->actingAs($this->admin());

        // UMKM tanpa koordinat harus gagal validasi.
        Livewire::test(CreateUmkmBusiness::class)
            ->fillForm([
                'name' => 'Usaha Tanpa Lokasi',
                'business_type' => 'produk',
                'region_id' => $kecamatan->id,
                'latitude' => null,
                'longitude' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['latitude', 'longitude']);
    }
}
