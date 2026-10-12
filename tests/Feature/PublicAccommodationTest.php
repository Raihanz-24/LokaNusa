<?php

namespace Tests\Feature;

use App\Enums\AccommodationType;
use App\Models\Accommodation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAccommodationTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    public function test_daftar_penginapan_hanya_menampilkan_yang_terbit(): void
    {
        Accommodation::factory()->published()->create(['name' => 'Hotel Terbit']);
        Accommodation::factory()->create(['name' => 'Hotel Draf']);

        $this->actingAs($this->wisatawan())->get('/penginapan')
            ->assertSuccessful()
            ->assertSee('Hotel Terbit')
            ->assertDontSee('Hotel Draf');
    }

    public function test_pencarian_penginapan_berdasarkan_nama(): void
    {
        Accommodation::factory()->published()->create(['name' => 'Homestay Bromo Indah']);
        Accommodation::factory()->published()->create(['name' => 'Villa Anyer']);

        $this->actingAs($this->wisatawan())->get('/penginapan?q=Homestay')
            ->assertSuccessful()
            ->assertSee('Homestay Bromo Indah')
            ->assertDontSee('Villa Anyer');
    }

    public function test_filter_penginapan_berdasarkan_jenis(): void
    {
        Accommodation::factory()->published()->create(['name' => 'Grand Hotel', 'type' => AccommodationType::Hotel]);
        Accommodation::factory()->published()->create(['name' => 'Homestay Desa', 'type' => AccommodationType::Homestay]);

        $this->actingAs($this->wisatawan())->get('/penginapan?jenis=homestay')
            ->assertSuccessful()
            ->assertSee('Homestay Desa')
            ->assertDontSee('Grand Hotel');
    }

    public function test_filter_penginapan_berdasarkan_harga_maks(): void
    {
        Accommodation::factory()->published()->create(['name' => 'Penginapan Murah', 'price_per_night' => 150000]);
        Accommodation::factory()->published()->create(['name' => 'Penginapan Mahal', 'price_per_night' => 900000]);

        $this->actingAs($this->wisatawan())->get('/penginapan?harga_maks=200000')
            ->assertSuccessful()
            ->assertSee('Penginapan Murah')
            ->assertDontSee('Penginapan Mahal');
    }

    public function test_detail_penginapan_tampil_dengan_fasilitas(): void
    {
        $accommodation = Accommodation::factory()->published()->create([
            'name' => 'Resort Pantai Putri',
            'description' => 'Resort tepi pantai.',
            'amenities' => ['Kolam Renang', 'Sarapan'],
            'price_per_night' => 750000,
        ]);

        $this->actingAs($this->wisatawan())->get('/penginapan/'.$accommodation->slug)
            ->assertSuccessful()
            ->assertSee('Resort Pantai Putri')
            ->assertSee('Resort tepi pantai')
            ->assertSee('Kolam Renang')
            ->assertSee('750.000');
    }

    public function test_penginapan_draf_tidak_dapat_diakses_publik(): void
    {
        $accommodation = Accommodation::factory()->create(['name' => 'Belum Tayang Penginapan']);

        $this->actingAs($this->wisatawan())->get('/penginapan/'.$accommodation->slug)->assertNotFound();
    }
}
