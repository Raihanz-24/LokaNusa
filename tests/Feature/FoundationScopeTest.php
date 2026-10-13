<?php

namespace Tests\Feature;

use App\Enums\BusinessType;
use App\Enums\ContentStatus;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Accommodations\AccommodationResource;
use App\Filament\Resources\UmkmBusinesses\UmkmBusinessResource;
use App\Models\Accommodation;
use App\Models\AppSetting;
use App\Models\Region;
use App\Models\UmkmBusiness;
use App\Models\UmkmProduct;
use App\Models\User;
use App\Policies\AccommodationPolicy;
use App\Policies\UmkmBusinessPolicy;
use App\Policies\UmkmProductPolicy;
use App\Services\UmkmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FoundationScopeTest extends TestCase
{
    use RefreshDatabase;

    private function setupFocus(): Region
    {
        $kabupaten = Region::factory()->kabupatenKota()->create(['name' => 'Kabupaten Contoh']);
        AppSetting::setFocusRegion($kabupaten->id);

        return $kabupaten;
    }

    public function test_super_admin_menetapkan_kabupaten_fokus(): void
    {
        $super = User::factory()->superAdmin()->create();
        $kabupaten = Region::factory()->kabupatenKota()->create(['name' => 'Kabupaten Fokus']);

        $this->actingAs($super);

        Livewire::test(Settings::class)
            ->fillForm(['focus_region_id' => $kabupaten->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame((string) $kabupaten->id, AppSetting::get('focus_region_id'));
        $this->assertTrue(AppSetting::focusRegion()->is($kabupaten));
    }

    public function test_non_super_admin_tidak_boleh_akses_pengaturan(): void
    {
        $this->actingAs(User::factory()->adminPemerintah()->create());
        $this->assertFalse(Settings::canAccess());

        $this->actingAs(User::factory()->superAdmin()->create());
        $this->assertTrue(Settings::canAccess());
    }

    public function test_pemilik_umkm_hanya_melihat_dan_mengubah_usahanya(): void
    {
        $pemilik = User::factory()->pemilikUmkm()->create();
        $lain = User::factory()->pemilikUmkm()->create();

        $milik = UmkmBusiness::factory()->create(['owner_id' => $pemilik->id]);
        $bukanMilik = UmkmBusiness::factory()->create(['owner_id' => $lain->id]);

        $policy = new UmkmBusinessPolicy;

        $this->assertTrue($policy->update($pemilik, $milik));
        $this->assertFalse($policy->update($pemilik, $bukanMilik));
        $this->assertFalse($policy->moderate($pemilik, $milik));

        $this->actingAs($pemilik);
        $this->assertTrue(UmkmBusinessResource::canViewAny());
        $this->assertSame(1, UmkmBusinessResource::getEloquentQuery()->count());
    }

    public function test_produk_mengikuti_kepemilikan_usaha(): void
    {
        $pemilik = User::factory()->pemilikUmkm()->create();
        $lain = User::factory()->pemilikUmkm()->create();

        $usaha = UmkmBusiness::factory()->create(['owner_id' => $pemilik->id]);
        $produk = UmkmProduct::factory()->create(['umkm_business_id' => $usaha->id, 'name' => 'Kopi Robusta']);

        $policy = new UmkmProductPolicy;

        $this->assertTrue($policy->update($pemilik, $produk));
        $this->assertFalse($policy->update($lain, $produk));
    }

    public function test_kuliner_tercatat_sebagai_jenis_umkm(): void
    {
        $kuliner = UmkmBusiness::factory()->kuliner()->create();

        $this->assertSame(BusinessType::Kuliner, $kuliner->business_type);
        $this->assertTrue($kuliner->isKuliner());
    }

    public function test_pengelola_penginapan_ownership_dan_admin_moderasi(): void
    {
        $pengelola = User::factory()->pemilikPenginapan()->create();
        $lain = User::factory()->pemilikPenginapan()->create();
        $admin = User::factory()->pengelolaLokanusa()->create();

        $milik = Accommodation::factory()->create(['manager_id' => $pengelola->id]);
        $bukanMilik = Accommodation::factory()->create(['manager_id' => $lain->id]);

        $policy = new AccommodationPolicy;

        $this->assertTrue($policy->update($pengelola, $milik));
        $this->assertFalse($policy->update($pengelola, $bukanMilik));
        $this->assertFalse($policy->moderate($pengelola, $bukanMilik));
        $this->assertTrue($policy->moderate($admin, $bukanMilik));

        $this->actingAs($pengelola);
        $this->assertTrue(AccommodationResource::canViewAny());
        $this->assertSame(1, AccommodationResource::getEloquentQuery()->count());
    }

    public function test_alur_moderasi_umkm(): void
    {
        $business = UmkmBusiness::factory()->create();

        $service = app(UmkmService::class);

        $service->submitForReview($business);
        $this->assertSame(ContentStatus::Pending, $business->fresh()->status);

        $service->approve($business->fresh(), User::factory()->adminPemerintah()->create());
        $this->assertSame(ContentStatus::Published, $business->fresh()->status);
        $this->assertNotNull($business->fresh()->published_at);
    }

    public function test_wisatawan_tidak_boleh_akses_umkm(): void
    {
        $this->actingAs(User::factory()->wisatawan()->create());

        $this->assertFalse(UmkmBusinessResource::canViewAny());
        $this->get('/admin/umkm-businesses')->assertForbidden();
    }

    public function test_admin_boleh_akses_semua_modul_pondasi(): void
    {
        $this->actingAs(User::factory()->adminPemerintah()->create());

        $this->assertTrue(UmkmBusinessResource::canViewAny());
        $this->assertTrue(AccommodationResource::canViewAny());
    }
}
