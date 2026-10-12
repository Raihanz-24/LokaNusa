<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Accommodation;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\UmkmBusiness;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_dashboard_dapat_dibuka_admin(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_statistik_konten_menghitung_total_dan_terbit(): void
    {
        Destination::factory()->count(2)->create(['status' => ContentStatus::Published]);
        Destination::factory()->create(['status' => ContentStatus::Draft]);
        UmkmBusiness::factory()->create(['status' => ContentStatus::Published]);
        Accommodation::factory()->create(['status' => ContentStatus::Pending]);
        CultureArticle::factory()->create(['status' => ContentStatus::Published]);
        Event::factory()->create(['status' => ContentStatus::Pending]);

        $stats = app(DashboardService::class)->contentStats();

        $this->assertSame(3, $stats['destinasi']['total']);
        $this->assertSame(2, $stats['destinasi']['published']);
        $this->assertSame(1, $stats['umkm']['published']);
        $this->assertSame(0, $stats['penginapan']['published']);
        $this->assertSame(1, $stats['budaya']['published']);
        $this->assertSame(0, $stats['event']['published']);
    }

    public function test_menghitung_konten_menunggu_moderasi(): void
    {
        Destination::factory()->create(['status' => ContentStatus::Pending]);
        UmkmBusiness::factory()->create(['status' => ContentStatus::Pending]);
        Event::factory()->create(['status' => ContentStatus::Published]);

        $this->assertSame(2, app(DashboardService::class)->pendingModerationCount());
    }

    public function test_statistik_pengguna(): void
    {
        User::factory()->superAdmin()->create();
        User::factory()->count(3)->wisatawan()->create();

        $stats = app(DashboardService::class)->userStats();

        $this->assertSame(4, $stats['total']);
        $this->assertSame(3, $stats['wisatawan']);
        $this->assertSame(4, $stats['new_this_month']);
    }

    public function test_statistik_engagement_memakai_ulasan_terbit_saja(): void
    {
        $user = User::factory()->wisatawan()->create();
        $destination = Destination::factory()->create(['status' => ContentStatus::Published]);

        $destination->reviews()->create([
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'Bagus',
            'status' => ContentStatus::Published,
        ]);
        $destination->reviews()->create([
            'user_id' => User::factory()->wisatawan()->create()->id,
            'rating' => 1,
            'comment' => 'Ditolak',
            'status' => ContentStatus::Rejected,
        ]);

        Favorite::create([
            'user_id' => $user->id,
            'favoritable_type' => Destination::class,
            'favoritable_id' => $destination->id,
        ]);

        $stats = app(DashboardService::class)->engagementStats();

        $this->assertSame(1, $stats['favorites']);
        $this->assertSame(1, $stats['reviews']);
        $this->assertSame(5.0, $stats['average_rating']);
    }

    public function test_tren_konten_mengembalikan_enam_label(): void
    {
        Destination::factory()->create([
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $trend = app(DashboardService::class)->contentTrend(6);

        $this->assertCount(6, $trend['labels']);
        $this->assertCount(5, $trend['series']);
        $this->assertSame('Destinasi', $trend['series'][0]['label']);
        // Bulan berjalan memuat 1 destinasi terbit.
        $this->assertSame(1, end($trend['series'][0]['data']));
    }

    public function test_ulasan_rating_rata_rata_nol_saat_tidak_ada(): void
    {
        $this->assertSame(0.0, app(DashboardService::class)->engagementStats()['average_rating']);
    }
}
