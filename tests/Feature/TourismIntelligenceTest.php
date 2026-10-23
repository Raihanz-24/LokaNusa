<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Filament\Pages\TourismIntelligence;
use App\Models\Accommodation;
use App\Models\AiUsage;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\QrScan;
use App\Models\Review;
use App\Models\TripPlannerMessage;
use App\Models\UmkmBusiness;
use App\Models\User;
use App\Services\TourismIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tourism Intelligence (D-053): analitik nyata + ekspor CSV.
 */
class TourismIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_peran_pengelola_semua_konten_yang_bisa_akses(): void
    {
        $this->actingAs(User::factory()->pengelolaLokanusa()->create());
        $this->assertTrue(TourismIntelligence::canAccess());

        $this->actingAs(User::factory()->adminPemerintah()->create());
        $this->assertTrue(TourismIntelligence::canAccess());

        $this->actingAs(User::factory()->superAdmin()->create());
        $this->assertTrue(TourismIntelligence::canAccess());

        $this->actingAs(User::factory()->pemilikDestinasi()->create());
        $this->assertFalse(TourismIntelligence::canAccess());

        $this->actingAs(User::factory()->wisatawan()->create());
        $this->assertFalse(TourismIntelligence::canAccess());
    }

    public function test_ringkasan_menghitung_data_nyata(): void
    {
        $user = User::factory()->wisatawan()->create();
        $destination = Destination::factory()->published()->create();

        QrScan::factory()->count(4)->create([
            'qr_code_id' => $destination->ensureQrCode()->getKey(),
            'scanned_at' => now(),
        ]);

        Review::factory()->count(2)
            ->for($destination, 'reviewable')
            ->create(['rating' => 4, 'status' => ContentStatus::Published, 'published_at' => now()]);

        Favorite::query()->create([
            'user_id' => $user->getKey(),
            'favoritable_type' => Destination::class,
            'favoritable_id' => $destination->getKey(),
        ]);

        $summary = app(TourismIntelligenceService::class)->summary(30);

        $this->assertSame(4, $summary['qr_scans']);
        $this->assertSame(2, $summary['reviews']);
        $this->assertSame(4.0, (float) $summary['rating_average']);
        $this->assertSame(1, $summary['favorites']);
    }

    public function test_data_di_luar_periode_tidak_ikut_dihitung(): void
    {
        $destination = Destination::factory()->published()->create();

        QrScan::factory()->create([
            'qr_code_id' => $destination->ensureQrCode()->getKey(),
            'scanned_at' => now()->subDays(40),
        ]);

        $service = app(TourismIntelligenceService::class);

        $this->assertSame(0, $service->summary(7)['qr_scans']);
        $this->assertSame(1, $service->summary(90)['qr_scans']);
    }

    public function test_tren_pemindaian_mengembalikan_jumlah_hari(): void
    {
        $destination = Destination::factory()->published()->create();
        QrScan::factory()->create([
            'qr_code_id' => $destination->ensureQrCode()->getKey(),
            'scanned_at' => now(),
        ]);

        $trend = app(TourismIntelligenceService::class)->scanTrend(14);

        $this->assertCount(14, $trend);
        $this->assertSame(1, array_sum(array_column($trend, 'count')));
    }

    public function test_konten_paling_banyak_dipindai(): void
    {
        $populer = Destination::factory()->published()->create(['name' => 'Bromo']);
        $sepi = Destination::factory()->published()->create(['name' => 'Pantai']);

        QrScan::factory()->count(3)->create(['qr_code_id' => $populer->ensureQrCode()->getKey()]);
        QrScan::factory()->count(1)->create(['qr_code_id' => $sepi->ensureQrCode()->getKey()]);

        $top = app(TourismIntelligenceService::class)->topScanned(30);

        $this->assertSame('Bromo', $top[0]['title']);
        $this->assertSame(3, $top[0]['scans']);
    }

    public function test_rating_tertinggi_hanya_dari_ulasan_terbit(): void
    {
        $baik = Destination::factory()->published()->create(['name' => 'Destinasi Bagus']);
        $buruk = Destination::factory()->published()->create(['name' => 'Destinasi Buruk']);

        Review::factory()->for($baik, 'reviewable')->create([
            'rating' => 5, 'status' => ContentStatus::Published, 'published_at' => now(),
        ]);
        Review::factory()->for($buruk, 'reviewable')->create([
            'rating' => 1, 'status' => ContentStatus::Published, 'published_at' => now(),
        ]);
        // Ulasan pending diabaikan.
        Review::factory()->for($buruk, 'reviewable')->create([
            'rating' => 1, 'status' => ContentStatus::Pending,
        ]);

        $top = app(TourismIntelligenceService::class)->topRated();

        $this->assertSame('Destinasi Bagus', $top[0]['title']);
        $this->assertSame(5.0, $top[0]['rating']);
        $this->assertSame(1, $top[0]['reviews']);
    }

    public function test_paling_banyak_difavoritkan(): void
    {
        $populer = Accommodation::factory()->published()->create(['name' => 'Homestay Bromo']);

        Favorite::query()->create([
            'user_id' => User::factory()->wisatawan()->create()->getKey(),
            'favoritable_type' => Accommodation::class,
            'favoritable_id' => $populer->getKey(),
        ]);

        Favorite::query()->create([
            'user_id' => User::factory()->wisatawan()->create()->getKey(),
            'favoritable_type' => Accommodation::class,
            'favoritable_id' => $populer->getKey(),
        ]);

        Favorite::query()->create([
            'user_id' => User::factory()->wisatawan()->create()->getKey(),
            'favoritable_type' => Accommodation::class,
            'favoritable_id' => $populer->getKey(),
        ]);

        $top = app(TourismIntelligenceService::class)->topFavorited();

        $this->assertSame('Homestay Bromo', $top[0]['title']);
        $this->assertSame(3, $top[0]['favorites']);
    }

    public function test_topik_ai_terpopuler_menyaring_kata_umum(): void
    {
        $user = User::factory()->wisatawan()->create();

        TripPlannerMessage::query()->create([
            'user_id' => $user->getKey(),
            'role' => 'user',
            'content' => 'saya ingin rencana perjalanan ke bromo dan pantai',
        ]);

        TripPlannerMessage::query()->create([
            'user_id' => $user->getKey(),
            'role' => 'user',
            'content' => 'bromo itu indah, mau ke bromo lagi',
        ]);

        $keywords = collect(app(TourismIntelligenceService::class)->topAiKeywords(30))
            ->pluck('total', 'keyword');

        $this->assertSame(3, $keywords['bromo']);
        $this->assertArrayNotHasKey('saya', $keywords->all());
        $this->assertArrayNotHasKey('ingin', $keywords->all());
    }

    public function test_rincian_konten_per_modul(): void
    {
        Destination::factory()->published()->create();
        Destination::factory()->create(['status' => ContentStatus::Pending]);
        UmkmBusiness::factory()->published()->create();
        Accommodation::factory()->create(['status' => ContentStatus::Rejected]);
        CultureArticle::factory()->published()->create();
        Event::factory()->published()->create();

        $breakdown = app(TourismIntelligenceService::class)->contentBreakdown();

        $this->assertSame(2, $breakdown['destinasi']['total']);
        $this->assertSame(1, $breakdown['destinasi']['published']);
        $this->assertSame(1, $breakdown['destinasi']['pending']);
        $this->assertSame(1, $breakdown['penginapan']['rejected']);
        $this->assertSame(1, $breakdown['budaya']['published']);
    }

    public function test_halaman_intelligence_dapat_dibuka_pengelola(): void
    {
        $this->actingAs(User::factory()->pengelolaLokanusa()->create())
            ->get(TourismIntelligence::getUrl())
            ->assertOk()
            ->assertSee('Tourism Intelligence')
            ->assertSee('Unduh Laporan CSV');
    }

    public function test_halaman_intelligence_menolak_pemilik(): void
    {
        $this->actingAs(User::factory()->pemilikUmkm()->create())
            ->get(TourismIntelligence::getUrl())
            ->assertForbidden();
    }

    public function test_filter_periode_mengubah_data(): void
    {
        $destination = Destination::factory()->published()->create();
        QrScan::factory()->create([
            'qr_code_id' => $destination->ensureQrCode()->getKey(),
            'scanned_at' => now()->subDays(20),
        ]);

        $this->actingAs(User::factory()->pengelolaLokanusa()->create());

        Livewire::test(TourismIntelligence::class)
            ->call('setPeriod', 7)
            ->assertSet('days', 7)
            ->assertSet('summary.qr_scans', 0)
            ->call('setPeriod', 30)
            ->assertSet('summary.qr_scans', 1);
    }

    public function test_ekspor_csv_mengembalikan_berkas(): void
    {
        $destination = Destination::factory()->published()->create(['name' => 'Bromo']);
        QrScan::factory()->create([
            'qr_code_id' => $destination->ensureQrCode()->getKey(),
            'scanned_at' => now(),
        ]);

        $this->actingAs(User::factory()->pengelolaLokanusa()->create());

        $response = Livewire::test(TourismIntelligence::class)
            ->call('exportCsv');

        $response->assertFileDownloaded();
    }

    public function test_baris_laporan_memuat_metrik_utama(): void
    {
        $destination = Destination::factory()->published()->create();
        QrScan::factory()->create(['qr_code_id' => $destination->ensureQrCode()->getKey()]);

        $rows = app(TourismIntelligenceService::class)->reportRows(30);

        $labels = array_column($rows, 0);

        $this->assertContains('Total konten terbit', $labels);
        $this->assertContains('Pemindaian QR (periode)', $labels);
        $this->assertContains('Token AI terpakai', $labels);
        $this->assertContains('Destinasi', $labels);
    }

    public function test_token_ai_periode_dihitung(): void
    {
        AiUsage::query()->create([
            'user_id' => User::factory()->wisatawan()->create()->getKey(),
            'context' => 'trip-planner',
            'model' => 'deepseek-v4.1-flash',
            'prompt_tokens' => 100,
            'completion_tokens' => 200,
            'total_tokens' => 300,
        ]);

        $summary = app(TourismIntelligenceService::class)->summary(30);

        $this->assertSame(300, $summary['ai_tokens']);
    }
}
