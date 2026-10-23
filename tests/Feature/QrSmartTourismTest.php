<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Filament\Pages\QrStats;
use App\Models\Accommodation;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\QrCode;
use App\Models\QrScan;
use App\Models\UmkmBusiness;
use App\Models\User;
use App\Services\QrCodeService;
use App\Services\QrTargetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QR Smart Tourism (D-050 + D-052): token untuk semua jenis konten,
 * pencatatan pemindaian nyata, statistik, dan papan QR siap cetak.
 */
class QrSmartTourismTest extends TestCase
{
    use RefreshDatabase;

    public function test_destinasi_mendapat_token_qr_saat_dibutuhkan(): void
    {
        $destination = Destination::factory()->create();

        // Token dibuat malas (lazy) — belum ada sebelum diminta.
        $this->assertSame(0, QrCode::query()->count());

        $token = $destination->qrToken();

        $this->assertSame(16, strlen($token));
        $this->assertSame(1, QrCode::query()->count());
    }

    public function test_token_qr_unik_antar_konten(): void
    {
        $a = Destination::factory()->create();
        $b = Destination::factory()->create();

        $this->assertNotSame($a->qrToken(), $b->qrToken());
    }

    public function test_permintaan_berulang_memakai_token_yang_sama(): void
    {
        $destination = Destination::factory()->create();

        $first = $destination->qrToken();
        $second = $destination->fresh()->qrToken();

        $this->assertSame($first, $second);
        $this->assertSame(1, QrCode::query()->count());
    }

    public function test_semua_jenis_konten_mendukung_qr(): void
    {
        $targets = [
            Destination::class => Destination::factory()->published()->create(),
            UmkmBusiness::class => UmkmBusiness::factory()->published()->create(),
            Accommodation::class => Accommodation::factory()->published()->create(),
            CultureArticle::class => CultureArticle::factory()->published()->create(),
            Event::class => Event::factory()->published()->create(),
        ];

        $tokens = [];

        foreach ($targets as $model => $content) {
            $token = $content->qrToken();

            $this->assertSame(16, strlen($token), "Token QR gagal untuk {$model}");
            $tokens[] = $token;
        }

        $this->assertCount(5, array_unique($tokens));
        $this->assertSame(5, QrCode::query()->count());
    }

    public function test_pemindaian_mencatat_kunjungan_dan_mengalihkan(): void
    {
        $user = User::factory()->wisatawan()->create();
        $destination = Destination::factory()->published()->create();

        $this->actingAs($user)
            ->get(route('qr.scan', $destination->qrToken()))
            ->assertRedirect(route('destinations.show', $destination->slug));

        $this->assertDatabaseCount('qr_scans', 1);
    }

    public function test_tamu_yang_memindai_diarahkan_ke_login(): void
    {
        $destination = Destination::factory()->published()->create();

        $response = $this->get(route('qr.scan', $destination->qrToken()));

        // Pemindaian tamu tetap tercatat, lalu diarahkan ke login.
        $this->assertDatabaseCount('qr_scans', 1);
        $response->assertRedirect(route('login'));
    }

    public function test_token_tidak_dikenal_menghasilkan_404(): void
    {
        $this->get(route('qr.scan', 'token-palsu-123456'))->assertNotFound();
        $this->assertDatabaseCount('qr_scans', 0);
    }

    public function test_konten_belum_terbit_tidak_dapat_dipindai(): void
    {
        $destination = Destination::factory()->create(); // draft

        $this->get(route('qr.scan', $destination->qrToken()))->assertNotFound();
        $this->assertDatabaseCount('qr_scans', 0);
    }

    public function test_pemindaian_berulang_cepat_tidak_dihitung_ganda(): void
    {
        $user = User::factory()->wisatawan()->create();
        $destination = Destination::factory()->published()->create();

        $this->actingAs($user)->get(route('qr.scan', $destination->qrToken()));
        $this->actingAs($user)->get(route('qr.scan', $destination->qrToken()));

        $this->assertDatabaseCount('qr_scans', 1);
    }

    public function test_pemindaian_mencatat_pengguna_terdaftar(): void
    {
        $user = User::factory()->wisatawan()->create();
        $destination = Destination::factory()->published()->create();

        $this->actingAs($user)->get(route('qr.scan', $destination->qrToken()));

        $this->assertSame($user->getKey(), QrScan::query()->first()->user_id);
    }

    public function test_gambar_qr_disajikan_sebagai_png(): void
    {
        $destination = Destination::factory()->published()->create();

        $response = $this->get(route('qr.image', $destination->qrToken()));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_papan_qr_siap_cetak_menampilkan_nama_dan_gambar(): void
    {
        $destination = Destination::factory()->published()->create(['name' => 'Gunung Bromo']);

        $response = $this->get(route('qr.poster', $destination->qrToken()));

        $response->assertOk();
        $response->assertSee('Gunung Bromo');
        $response->assertSee('LokaNusa');
        $response->assertSee('Pindai untuk melihat detail');
        $response->assertSee('data:image/png;base64,', false);
    }

    public function test_statistik_menghitung_pemindaian_nyata(): void
    {
        $destination = Destination::factory()->published()->create();
        $qrCode = $destination->ensureQrCode();

        QrScan::factory()->count(3)->create(['qr_code_id' => $qrCode->getKey()]);
        QrScan::factory()->create([
            'qr_code_id' => $qrCode->getKey(),
            'scanned_at' => now()->subDays(20),
        ]);

        // Konten lain tidak boleh ikut terhitung.
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

    public function test_halaman_statistik_qr_untuk_destinasi(): void
    {
        $destination = Destination::factory()->published()->create();

        $this->actingAs(User::factory()->pengelolaLokanusa()->create())
            ->get(QrStats::getUrl(['type' => 'destinasi', 'id' => $destination->getKey()]))
            ->assertOk()
            ->assertSee('Statistik QR')
            ->assertSee('Kode QR');

        $this->actingAs(User::factory()->wisatawan()->create())
            ->get(QrStats::getUrl(['type' => 'destinasi', 'id' => $destination->getKey()]))
            ->assertForbidden();
    }

    public function test_halaman_statistik_qr_untuk_semua_tipe(): void
    {
        $moderator = User::factory()->pengelolaLokanusa()->create();

        $targets = [
            'destinasi' => Destination::factory()->published()->create(),
            'umkm' => UmkmBusiness::factory()->published()->create(),
            'penginapan' => Accommodation::factory()->published()->create(),
            'budaya' => CultureArticle::factory()->published()->create(),
            'event' => Event::factory()->published()->create(),
        ];

        foreach ($targets as $type => $content) {
            $this->actingAs($moderator)
                ->get(QrStats::getUrl(['type' => $type, 'id' => $content->getKey()]))
                ->assertOk()
                ->assertSee('Statistik QR');
        }
    }

    public function test_halaman_statistik_qr_menolak_tipe_asing(): void
    {
        $this->actingAs(User::factory()->pengelolaLokanusa()->create())
            ->get(QrStats::getUrl(['type' => 'tidak-ada', 'id' => 1]))
            ->assertNotFound();
    }

    public function test_daily_counts_mengembalikan_14_hari(): void
    {
        $destination = Destination::factory()->published()->create();
        QrScan::factory()->create([
            'qr_code_id' => $destination->ensureQrCode()->getKey(),
            'scanned_at' => now(),
        ]);

        $daily = app(QrCodeService::class)->dailyCounts($destination, 14);

        $this->assertCount(14, $daily);
        $this->assertSame(1, array_sum(array_column($daily, 'count')));
    }

    public function test_target_service_memetakan_url_publik_dengan_benar(): void
    {
        $targets = app(QrTargetService::class);

        $destination = Destination::factory()->published()->create();
        $umkm = UmkmBusiness::factory()->published()->create();

        $this->assertSame(
            route('destinations.show', $destination->slug),
            $targets->publicUrl($destination),
        );

        $this->assertSame(
            route('umkm.show', $umkm->slug),
            $targets->publicUrl($umkm),
        );

        $this->assertTrue($targets->supports($destination));
    }

    public function test_qr_tidak_dapat_dibuat_untuk_model_tanpa_trait(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(QrCodeService::class)->qrCodeFor(new User);
    }

    public function test_konten_terbit_wajib_untuk_pemindaian(): void
    {
        $destination = Destination::factory()->create(['status' => ContentStatus::Draft]);
        $qrCode = $destination->ensureQrCode();

        $this->assertNull(app(QrTargetService::class)->resolve($qrCode->token));
    }
}
