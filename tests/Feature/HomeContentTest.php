<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\HomeBanner;
use App\Models\HomeShortcut;
use App\Models\RecommendationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class HomeContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_menampilkan_banner_aktif_sesuai_urutan(): void
    {
        HomeBanner::factory()->create(['title' => 'Banner Kedua', 'caption' => 'Caption Kedua', 'sort_order' => 2]);
        HomeBanner::factory()->create(['title' => 'Banner Pertama', 'caption' => 'Caption Pertama', 'sort_order' => 1]);
        HomeBanner::factory()->create(['title' => 'Banner Nonaktif', 'is_active' => false]);

        $response = $this->get('/');

        $response->assertSuccessful()
            ->assertSeeInOrder(['Banner Pertama', 'Banner Kedua'])
            ->assertSee('Caption Pertama')
            ->assertDontSee('Banner Nonaktif');
    }

    public function test_beranda_memakai_banner_bawaan_saat_belum_diatur(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Pesona Bromo Tengger Semeru');
    }

    public function test_beranda_menampilkan_shortcut_aktif(): void
    {
        HomeShortcut::factory()->create(['label' => 'Destinasi Unggulan', 'url' => '/destinasi', 'sort_order' => 1]);
        HomeShortcut::factory()->create(['label' => 'Shortcut Nonaktif', 'is_active' => false]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Destinasi Unggulan')
            ->assertDontSee('Shortcut Nonaktif');
    }

    public function test_label_bawaan_shortcut_mengikuti_bahasa(): void
    {
        HomeShortcut::factory()->create(['label' => 'Destinasi', 'url' => '/destinasi', 'sort_order' => 1]);
        HomeShortcut::factory()->create(['label' => 'Peta', 'url' => '/peta', 'sort_order' => 2]);

        // Default bahasa Inggris: label bawaan diterjemahkan.
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Quick Access')
            ->assertSee('Destinations')
            ->assertSee('Map');

        // Versi Indonesia: label kembali ke teks Indonesia.
        $this->withSession(['locale' => 'id'])
            ->get('/')
            ->assertSuccessful()
            ->assertSee('Akses Cepat')
            ->assertSee('Destinasi')
            ->assertSee('Peta');
    }

    public function test_beranda_menampilkan_rekomendasi_kurasi_manual(): void
    {
        $destination = Destination::factory()->published()->create(['name' => 'Kawah Bromo']);

        RecommendationItem::factory()->create([
            'recommendable_type' => Destination::class,
            'recommendable_id' => $destination->getKey(),
            'sort_order' => 1,
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Recommendations for you')
            ->assertSee('Kawah Bromo');
    }

    public function test_rekomendasi_tidak_menampilkan_konten_belum_terbit(): void
    {
        $draft = Destination::factory()->create(['name' => 'Destinasi Draf']);

        RecommendationItem::factory()->create([
            'recommendable_type' => Destination::class,
            'recommendable_id' => $draft->getKey(),
        ]);

        $this->get('/')->assertSuccessful()->assertDontSee('Destinasi Draf');
    }

    public function test_tamu_dapat_membuka_beranda(): void
    {
        // Default bahasa Inggris.
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Search destinations, stays, culinary, services, etc')
            ->assertSee('Recommendations for you');

        // Versi Indonesia.
        $this->withSession(['locale' => 'id'])
            ->get('/')
            ->assertSuccessful()
            ->assertSee('Cari destinasi, penginapan, kuliner, jasa dst')
            ->assertSee('Rekomendasi untukmu');
    }

    public function test_header_menampilkan_sapaan_dan_lokasi(): void
    {
        $user = User::factory()->wisatawan()->create(['name' => 'Reyhan Pratama']);

        // Default bahasa Inggris; sapaan mengikuti locale.
        $this->actingAs($user)
            ->get('/')
            ->assertSuccessful()
            ->assertSee('Hello, Reyhan');

        // Setelah ganti ke Indonesia.
        $this->withSession(['locale' => 'id'])
            ->actingAs($user)
            ->get('/')
            ->assertSuccessful()
            ->assertSee('Halo, Reyhan');
    }

    public function test_ikon_shortcut_dirender_sesuai_nama(): void
    {
        $map = Blade::render('<x-heroicon name="Map" />');
        $this->assertStringContainsString('M9 6.75V15', $map);

        $pin = Blade::render('<x-heroicon name="o-map-pin" />');
        $this->assertStringContainsString('M15 10.5a3 3 0 1 1-6 0', $pin);
    }

    public function test_bottom_nav_menandai_item_aktif(): void
    {
        // Item aktif ditandai aria-current="page" + gaya ikon aktif (gradien).
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('aria-current="page"', false)
            ->assertSee('from-emerald-500', false);
    }
}
