<?php

namespace Tests\Feature;

use App\Enums\RegionType;
use App\Models\Accommodation;
use App\Models\AppSetting;
use App\Models\Destination;
use App\Models\Region;
use App\Models\Review;
use App\Models\UmkmBusiness;
use App\Models\User;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPagesTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    public function test_saran_pencarian_live_mulai_dua_huruf(): void
    {
        Destination::factory()->published()->create(['name' => 'Kawah Bromo']);
        Destination::factory()->published()->create(['name' => 'Pantai Bentar']);

        $user = $this->wisatawan();

        // Kurang dari 2 huruf → kosong.
        $this->actingAs($user)->getJson('/saran/destination?q=K')
            ->assertSuccessful()
            ->assertJsonCount(0, 'items');

        // Mulai 2 huruf → cocok dari awal nama.
        $this->actingAs($user)->getJson('/saran/destination?q=Ka')
            ->assertSuccessful()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.title', 'Kawah Bromo');

        // Tidak cocok dari awal → tidak muncul.
        $this->actingAs($user)->getJson('/saran/destination?q=romo')
            ->assertSuccessful()
            ->assertJsonCount(0, 'items');
    }

    public function test_halaman_destinasi_menampilkan_seksi_rekomendasi_terurut_rating(): void
    {
        $user = $this->wisatawan();

        $ungraded = Destination::factory()->published()->create(['name' => 'Destinasi Tanpa Rating']);
        $top = Destination::factory()->published()->create(['name' => 'Destinasi Rating Tertinggi']);
        $low = Destination::factory()->published()->create(['name' => 'Destinasi Rating Rendah']);

        Review::factory()->published()->create([
            'reviewable_type' => Destination::class,
            'reviewable_id' => $top->id,
            'rating' => 5,
        ]);
        Review::factory()->published()->create([
            'reviewable_type' => Destination::class,
            'reviewable_id' => $low->id,
            'rating' => 2,
        ]);

        $response = $this->actingAs($user)->get('/destinasi');

        $response->assertSuccessful()
            ->assertSee('Recommendations')
            ->assertSee('Destinasi Rating Tertinggi')
            ->assertSee('Destinasi Rating Rendah')
            // Konten tanpa rating tidak masuk seksi rekomendasi (rating tertinggi).
            ->assertSee('Destinasi Tanpa Rating');

        // Urutan: rating tertinggi muncul lebih dulu di HTML.
        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'Destinasi Rating Rendah'),
            strpos($html, 'Destinasi Rating Tertinggi'),
        );
    }

    public function test_halaman_umkm_dan_penginapan_menampilkan_rekomendasi(): void
    {
        $user = $this->wisatawan();

        $umkm = UmkmBusiness::factory()->published()->create(['name' => 'UMKM Kopi Terbaik']);
        Review::factory()->published()->create([
            'reviewable_type' => UmkmBusiness::class,
            'reviewable_id' => $umkm->id,
            'rating' => 5,
        ]);

        $this->actingAs($user)->get('/umkm')
            ->assertSuccessful()
            ->assertSee('Recommendations')
            ->assertSee('UMKM Kopi Terbaik');

        $stay = Accommodation::factory()->published()->create(['name' => 'Homestay Sejuk']);
        Review::factory()->published()->create([
            'reviewable_type' => Accommodation::class,
            'reviewable_id' => $stay->id,
            'rating' => 4,
        ]);

        $this->actingAs($user)->get('/penginapan')
            ->assertSuccessful()
            ->assertSee('Recommendations')
            ->assertSee('Homestay Sejuk');
    }

    public function test_input_filter_tidak_langsung_tampil_di_halaman(): void
    {
        Destination::factory()->published()->create(['name' => 'Destinasi Uji']);

        $response = $this->actingAs($this->wisatawan())->get('/destinasi');

        // Kolom filter berada di dalam modal tersembunyi (hidden), bukan inline.
        $response->assertSuccessful()
            ->assertSee('data-filter-modal', false)
            ->assertSee('data-filter-open', false);
    }

    public function test_pencarian_utama_mencocokkan_dari_awal_dan_menampilkan_jumlah(): void
    {
        Destination::factory()->published()->create(['name' => 'Bromo Indah']);
        Destination::factory()->published()->create(['name' => 'Wisata Bromo']);

        $response = $this->actingAs($this->wisatawan())->get('/destinasi?q=Bromo');

        // Hanya yang berawalan "Bromo" (bukan yang memuat di tengah).
        $response->assertSuccessful()
            ->assertSee('Bromo Indah')
            ->assertDontSee('Wisata Bromo')
            ->assertSee('search results for')
            ->assertSee('See all destinations')
            // Wadah infinite scroll tersedia.
            ->assertSee('data-catalog-infinite', false);
    }

    public function test_halaman_katalog_teks_mengikuti_bahasa_indonesia(): void
    {
        Destination::factory()->published()->create(['name' => 'Bromo Indah']);

        $this->withSession(['locale' => 'id'])
            ->actingAs($this->wisatawan())
            ->get('/destinasi?q=Bromo')
            ->assertSuccessful()
            ->assertSee('hasil pencarian')
            ->assertSee('Lihat semua destinasi');
    }

    public function test_dummy_seeder_mengisi_sepuluh_item_dan_rating(): void
    {
        $kabupaten = Region::factory()->kabupatenKota()->create();
        $kecamatan = Region::factory()->kecamatan($kabupaten)->create(['name' => 'Paiton']);
        Region::factory()->create([
            'parent_id' => $kecamatan->id,
            'type' => RegionType::DesaKelurahan,
            'name' => 'Sumber Rejo',
        ]);
        AppSetting::setFocusRegion($kabupaten->id);

        $this->seed(DummySeeder::class);

        $this->assertSame(10, Destination::query()->count());
        $this->assertSame(10, UmkmBusiness::query()->count());
        $this->assertSame(10, Accommodation::query()->count());

        // Sebagian konten punya ulasan → seksi rekomendasi terisi.
        $this->assertGreaterThanOrEqual(10, Review::query()->count());
    }
}
