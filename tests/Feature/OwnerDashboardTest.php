<?php

namespace Tests\Feature;

use App\Filament\Pages\OwnerDashboard;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Favorite;
use App\Models\Review;
use App\Models\UmkmBusiness;
use App\Models\UmkmProduct;
use App\Models\User;
use App\Services\OwnerTrafficService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->pemilikUmkm()->create();
    }

    public function test_pemilik_tanpa_usaha_belum_punya_bisnis(): void
    {
        $user = $this->owner();

        $summary = app(OwnerTrafficService::class)->summary($user);

        $this->assertFalse($summary['has_business']);
        $this->assertSame(0, $summary['content']['umkm']['total']);
        $this->assertNull($summary['rating']['average']);
    }

    public function test_ringkasan_menghitung_konten_dan_rating_miliknya(): void
    {
        $user = $this->owner();
        $business = UmkmBusiness::factory()->for($user, 'owner')->create();
        UmkmBusiness::factory()->create(); // milik orang lain (tidak dihitung)

        Review::factory()->published()->create([
            'reviewable_type' => UmkmBusiness::class,
            'reviewable_id' => $business->getKey(),
            'rating' => 4,
        ]);
        Review::factory()->published()->create([
            'reviewable_type' => UmkmBusiness::class,
            'reviewable_id' => $business->getKey(),
            'rating' => 5,
        ]);
        // Ulasan belum terbit tidak dihitung.
        Review::factory()->create([
            'reviewable_type' => UmkmBusiness::class,
            'reviewable_id' => $business->getKey(),
            'rating' => 1,
        ]);
        Favorite::query()->create([
            'user_id' => User::factory()->create()->getKey(),
            'favoritable_type' => UmkmBusiness::class,
            'favoritable_id' => $business->getKey(),
        ]);

        $summary = app(OwnerTrafficService::class)->summary($user);

        $this->assertTrue($summary['has_business']);
        $this->assertSame(1, $summary['content']['umkm']['total']);
        $this->assertSame(2, $summary['rating']['count']);
        $this->assertSame(4.5, $summary['rating']['average']);
        $this->assertSame(1, $summary['favorites']);
    }

    public function test_trafik_menginap_dan_pembelian_hanya_hitungan_lunas_miliknya(): void
    {
        $user = User::factory()->pemilikPenginapan()->create();
        $accommodation = Accommodation::factory()->create(['manager_id' => $user->getKey()]);
        $business = UmkmBusiness::factory()->for($user, 'owner')->create();
        $product = UmkmProduct::factory()->create(['umkm_business_id' => $business->getKey()]);

        // Lunas: 1 menginap (3 malam) + 1 pembelian (5 unit).
        Booking::factory()->paid()->create([
            'owner_id' => $user->getKey(),
            'bookable_type' => Accommodation::class,
            'bookable_id' => $accommodation->getKey(),
            'quantity' => 3,
            'unit_price' => 100000,
            'total_price' => 300000,
        ]);
        Booking::factory()->paid()->create([
            'owner_id' => $user->getKey(),
            'bookable_type' => UmkmProduct::class,
            'bookable_id' => $product->getKey(),
            'quantity' => 5,
            'unit_price' => 10000,
            'total_price' => 50000,
        ]);
        // Belum lunas: tidak dihitung.
        Booking::factory()->create([
            'owner_id' => $user->getKey(),
            'bookable_type' => Accommodation::class,
            'bookable_id' => $accommodation->getKey(),
        ]);

        $summary = app(OwnerTrafficService::class)->summary($user);

        $this->assertSame(1, $summary['stays']['bookings']);
        $this->assertSame(3, $summary['stays']['nights']);
        $this->assertSame(300000, $summary['stays']['revenue']);
        $this->assertSame(1, $summary['purchases']['bookings']);
        $this->assertSame(5, $summary['purchases']['units']);
        $this->assertSame(50000, $summary['purchases']['revenue']);
    }

    public function test_halaman_dasbor_usaha_dapat_diakses_pemilik(): void
    {
        $user = $this->owner();
        UmkmBusiness::factory()->for($user, 'owner')->create(['name' => 'Warung Saya']);

        $this->actingAs($user)
            ->get(OwnerDashboard::getUrl())
            ->assertOk()
            ->assertSee('Warung Saya')
            ->assertSee('Dasbor Usaha');
    }

    public function test_pemilik_tanpa_usaha_melihat_panduan_ajukan(): void
    {
        $this->actingAs($this->owner())
            ->get(OwnerDashboard::getUrl())
            ->assertOk()
            ->assertSee('Belum ada usaha')
            ->assertSee('Ajukan UMKM');
    }

    public function test_wisatawan_tidak_dapat_mengakses_dasbor_usaha(): void
    {
        $this->actingAs(User::factory()->wisatawan()->create())
            ->get(OwnerDashboard::getUrl())
            ->assertForbidden();
    }

    public function test_pengelola_tidak_melihat_dasbor_usaha(): void
    {
        $this->actingAs(User::factory()->pengelolaLokanusa()->create());
        $this->assertFalse(OwnerDashboard::canAccess());
    }

    public function test_pemilik_tidak_melihat_konten_orang_lain(): void
    {
        $user = $this->owner();
        Destination::factory()->create(); // bukan miliknya

        $items = app(OwnerTrafficService::class)->items($user);

        $this->assertSame([], $items);
    }

    public function test_filter_periode_membatasi_trafik(): void
    {
        $user = $this->owner();
        $business = UmkmBusiness::factory()->for($user, 'owner')->create();

        // Ulasan lama (60 hari) & baru.
        Review::factory()->published()->create([
            'reviewable_type' => UmkmBusiness::class,
            'reviewable_id' => $business->getKey(),
            'rating' => 5,
            'created_at' => now()->subDays(60),
        ]);
        Review::factory()->published()->create([
            'reviewable_type' => UmkmBusiness::class,
            'reviewable_id' => $business->getKey(),
            'rating' => 3,
            'created_at' => now(),
        ]);

        $service = app(OwnerTrafficService::class);

        $this->assertSame(2, $service->summary($user, null)['rating']['count']);
        $this->assertSame(1, $service->summary($user, 30)['rating']['count']);
    }

    public function test_daftar_konten_menyertakan_tautan_kelola(): void
    {
        $user = $this->owner();
        UmkmBusiness::factory()->for($user, 'owner')->create();

        $items = app(OwnerTrafficService::class)->items($user);

        $this->assertNotNull($items[0]['edit_url']);
        $this->assertStringContainsString('/edit', $items[0]['edit_url']);
    }

    public function test_halaman_dasbor_menampilkan_filter_periode(): void
    {
        $user = $this->owner();
        UmkmBusiness::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->get(OwnerDashboard::getUrl())
            ->assertOk()
            ->assertSee('Periode')
            ->assertSee('30 hari');
    }
}
