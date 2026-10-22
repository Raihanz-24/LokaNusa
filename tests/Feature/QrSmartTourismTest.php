<?php

namespace Tests\Feature;

use App\Filament\Resources\Destinations\Pages\DestinationQrStats;
use App\Models\Destination;
use App\Models\QrScan;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrSmartTourismTest extends TestCase
{
    use RefreshDatabase;

    public function test_destinasi_mendapat_token_qr_otomatis(): void
    {
        $destination = Destination::factory()->create();

        $this->assertNotNull($destination->qr_token);
        $this->assertSame(16, strlen($destination->qr_token));
    }

    public function test_token_qr_unik_antar_destinasi(): void
    {
        $a = Destination::factory()->create();
        $b = Destination::factory()->create();

        $this->assertNotSame($a->qr_token, $b->qr_token);
    }

    public function test_pemindaian_mencatat_kunjungan_dan_mengalihkan(): void
    {
        $destination = Destination::factory()->published()->create();

        $response = $this->get(route('qr.scan', $destination->qr_token));

        $response->assertRedirect(route('destinations.show', $destination->slug));
        $this->assertDatabaseCount('qr_scans', 1);
        $this->assertSame($destination->getKey(), QrScan::query()->first()->destination_id);
    }

    public function test_token_tidak_dikenal_menghasilkan_404(): void
    {
        $this->get(route('qr.scan', 'token-palsu-123456'))->assertNotFound();
        $this->assertDatabaseCount('qr_scans', 0);
    }

    public function test_destinasi_belum_terbit_tidak_dapat_dipindai(): void
    {
        $destination = Destination::factory()->create(); // draft

        $this->get(route('qr.scan', $destination->qr_token))->assertNotFound();
        $this->assertDatabaseCount('qr_scans', 0);
    }

    public function test_pemindaian_berulang_cepat_tidak_dihitung_ganda(): void
    {
        $destination = Destination::factory()->published()->create();

        $this->get(route('qr.scan', $destination->qr_token));
        $this->get(route('qr.scan', $destination->qr_token));

        $this->assertDatabaseCount('qr_scans', 1);
    }

    public function test_pemindaian_mencatat_pengguna_terdaftar(): void
    {
        $user = User::factory()->wisatawan()->create();
        $destination = Destination::factory()->published()->create();

        $this->actingAs($user)->get(route('qr.scan', $destination->qr_token));

        $this->assertSame($user->getKey(), QrScan::query()->first()->user_id);
    }

    public function test_gambar_qr_disajikan_sebagai_png(): void
    {
        $destination = Destination::factory()->published()->create();

        $response = $this->get(route('qr.image', $destination->slug));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_statistik_menghitung_pemindaian_nyata(): void
    {
        $destination = Destination::factory()->published()->create();
        QrScan::factory()->count(3)->create(['destination_id' => $destination->getKey()]);
        QrScan::factory()->create(['destination_id' => $destination->getKey(), 'scanned_at' => now()->subDays(20)]);
        // Destinasi lain tidak boleh ikut terhitung.
        QrScan::factory()->count(5)->create();

        $stats = app(QrCodeService::class)->stats($destination);

        $this->assertSame(4, $stats['total']);
        $this->assertSame(3, $stats['today']);
        $this->assertSame(3, $stats['last_7_days']);
        $this->assertSame(4, $stats['last_30_days']);
        $this->assertNotNull($stats['last_scan_at']);
    }

    public function test_statistik_kosong_tanpa_pemindaian(): void
    {
        $destination = Destination::factory()->published()->create();

        $stats = app(QrCodeService::class)->stats($destination);

        $this->assertSame(0, $stats['total']);
        $this->assertNull($stats['last_scan_at']);
    }

    public function test_halaman_statistik_qr_hanya_untuk_pengelola(): void
    {
        $destination = Destination::factory()->published()->create();

        $this->actingAs(User::factory()->pengelolaLokanusa()->create())
            ->get(DestinationQrStats::getUrl(['record' => $destination]))
            ->assertOk()
            ->assertSee('Statistik QR')
            ->assertSee('Kode QR Destinasi');

        $this->actingAs(User::factory()->wisatawan()->create())
            ->get(DestinationQrStats::getUrl(['record' => $destination]))
            ->assertForbidden();
    }

    public function test_daily_counts_mengembalikan_14_hari(): void
    {
        $destination = Destination::factory()->published()->create();
        QrScan::factory()->create([
            'destination_id' => $destination->getKey(),
            'scanned_at' => now(),
        ]);

        $daily = app(QrCodeService::class)->dailyCounts($destination, 14);

        $this->assertCount(14, $daily);
        $this->assertSame(1, array_sum(array_column($daily, 'count')));
    }
}
