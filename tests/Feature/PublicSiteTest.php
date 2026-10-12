<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    public function test_beranda_tampil_dengan_destinasi_rekomendasi(): void
    {
        Destination::factory()->published()->create([
            'name' => 'Pantai Indah',
            'is_recommended' => true,
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('LokaNusa')
            ->assertSee('Recommendations for you')
            ->assertSee('Pantai Indah');
    }

    public function test_beranda_tidak_menampilkan_destinasi_terbit_yang_tidak_direkomendasikan(): void
    {
        Destination::factory()->published()->create(['name' => 'Pantai Biasa']);

        $this->get('/')->assertSuccessful()->assertDontSee('Pantai Biasa');
    }

    public function test_beranda_tidak_menampilkan_destinasi_draft(): void
    {
        Destination::factory()->create(['name' => 'Destinasi Rahasia']);

        $this->get('/')->assertSuccessful()->assertDontSee('Destinasi Rahasia');
    }

    public function test_daftar_destinasi_hanya_menampilkan_yang_terbit(): void
    {
        Destination::factory()->published()->create(['name' => 'Gunung Terbit']);
        Destination::factory()->create(['name' => 'Gunung Draf']);

        $this->actingAs($this->wisatawan())
            ->get('/destinasi')
            ->assertSuccessful()
            ->assertSee('Gunung Terbit')
            ->assertDontSee('Gunung Draf');
    }

    public function test_pencarian_destinasi_berdasarkan_nama(): void
    {
        Destination::factory()->published()->create(['name' => 'Air Terjun Sekumpul']);
        Destination::factory()->published()->create(['name' => 'Pantai Kuta']);

        $this->actingAs($this->wisatawan())
            ->get('/destinasi?q=Air')
            ->assertSuccessful()
            ->assertSee('Air Terjun Sekumpul')
            ->assertDontSee('Pantai Kuta');
    }

    public function test_filter_berdasarkan_kategori(): void
    {
        $kategori = DestinationCategory::factory()->create(['name' => 'Wisata Alam', 'slug' => 'wisata-alam']);
        Destination::factory()->published()->create(['name' => 'Danau Toba', 'destination_category_id' => $kategori->id]);
        Destination::factory()->published()->create(['name' => 'Museum Kota']);

        $this->actingAs($this->wisatawan())
            ->get('/destinasi?kategori=wisata-alam')
            ->assertSuccessful()
            ->assertSee('Danau Toba')
            ->assertDontSee('Museum Kota');
    }

    public function test_detail_destinasi_tampil(): void
    {
        $destination = Destination::factory()->published()->create([
            'name' => 'Candi Borobudur',
            'description' => 'Candi Buddha terbesar di dunia.',
        ]);

        $this->actingAs($this->wisatawan())
            ->get('/destinasi/'.$destination->slug)
            ->assertSuccessful()
            ->assertSee('Candi Borobudur')
            ->assertSee('Candi Buddha terbesar');
    }

    public function test_destinasi_draf_tidak_dapat_diakses_publik(): void
    {
        $destination = Destination::factory()->create(['name' => 'Belum Tayang']);

        $this->actingAs($this->wisatawan())
            ->get('/destinasi/'.$destination->slug)
            ->assertNotFound();
    }
}
