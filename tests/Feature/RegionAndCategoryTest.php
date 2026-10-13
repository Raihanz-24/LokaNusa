<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\DestinationCategories\Pages\CreateDestinationCategory;
use App\Filament\Resources\DestinationCategories\Pages\ListDestinationCategories;
use App\Filament\Resources\Regions\RegionResource;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegionAndCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function adminPemerintah(): User
    {
        return User::factory()->adminPemerintah()->create();
    }

    public function test_slug_otomatis_dibuat_dari_nama(): void
    {
        $region = Region::factory()->provinsi()->create(['name' => 'Jawa Timur', 'slug' => null]);

        $this->assertSame('jawa-timur', $region->slug);
    }

    public function test_wilayah_bertingkat_dan_full_name(): void
    {
        $provinsi = Region::factory()->provinsi()->create(['name' => 'Jawa Timur']);
        $kabupaten = Region::factory()->kabupatenKota($provinsi)->create(['name' => 'Malang']);
        $kecamatan = Region::factory()->kecamatan($kabupaten)->create(['name' => 'Paiton']);

        $this->assertTrue($kecamatan->parent->is($kabupaten));
        $this->assertTrue($kabupaten->parent->is($provinsi));
        $this->assertSame('Paiton, Malang, Jawa Timur', $kecamatan->fullName());
    }

    public function test_halaman_master_data_wilayah_tidak_ditampilkan_di_panel(): void
    {
        // Wilayah adalah data referensi; sengaja tidak diekspos sebagai halaman admin.
        $this->assertFalse(class_exists(RegionResource::class));

        $this->actingAs(User::factory()->superAdmin()->create());
        $this->get('/admin/regions')->assertNotFound();
    }

    public function test_kategori_destinasi_crud(): void
    {
        $this->actingAs($this->adminPemerintah());

        Livewire::test(CreateDestinationCategory::class)
            ->fillForm([
                'name' => 'Wisata Alam',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('destination_categories', ['name' => 'Wisata Alam', 'slug' => 'wisata-alam']);

        Livewire::test(ListDestinationCategories::class)->assertSuccessful();
    }

    public function test_pemilik_destinasi_tidak_boleh_kelola_kategori(): void
    {
        // Kategori = data referensi; pemilik usaha tidak mengelolanya.
        $this->actingAs(User::factory()->role(UserRole::PemilikDestinasi)->create());

        $this->get('/admin/destination-categories')->assertForbidden();
    }

    public function test_pengelola_lokanusa_boleh_kelola_kategori(): void
    {
        $this->actingAs(User::factory()->pengelolaLokanusa()->create());

        $this->get('/admin/destination-categories')->assertSuccessful();
    }

    public function test_wisatawan_tidak_boleh_akses_kategori(): void
    {
        $this->actingAs(User::factory()->wisatawan()->create());

        $this->get('/admin/destination-categories')->assertForbidden();
    }
}
