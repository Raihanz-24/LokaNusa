<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Accommodation;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\UmkmBusiness;
use App\Models\UmkmProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private function wisatawan(): User
    {
        return User::factory()->wisatawan()->create();
    }

    private function accommodationWithOwner(User $owner, int $price = 250000): Accommodation
    {
        return Accommodation::factory()->create([
            'manager_id' => $owner->getKey(),
            'price_per_night' => $price,
        ]);
    }

    public function test_wisatawan_bisa_memesan_penginapan(): void
    {
        $owner = User::factory()->pemilikPenginapan()->create();
        $accommodation = $this->accommodationWithOwner($owner);
        $user = $this->wisatawan();

        $start = now()->addDays(2)->toDateString();
        $end = now()->addDays(5)->toDateString();

        $response = $this->actingAs($user)->post(route('bookings.store-accommodation', $accommodation), [
            'start_date' => $start,
            'end_date' => $end,
            'contact_name' => 'Budi',
            'contact_phone' => '08123456789',
        ]);

        $booking = Booking::query()->first();

        $response->assertRedirect(route('bookings.show', $booking->code));

        $this->assertSame($user->getKey(), $booking->user_id);
        $this->assertSame($owner->getKey(), $booking->owner_id);
        $this->assertSame(3, $booking->quantity); // 3 malam
        $this->assertSame(750000, $booking->total_price);
        $this->assertSame(BookingStatus::Pending, $booking->status);
    }

    public function test_wisatawan_bisa_membeli_produk_umkm(): void
    {
        $owner = User::factory()->pemilikUmkm()->create();
        $business = UmkmBusiness::factory()->create(['owner_id' => $owner->getKey()]);
        $product = UmkmProduct::factory()->create(['umkm_business_id' => $business->getKey(), 'price' => 20000]);

        $user = $this->wisatawan();

        $response = $this->actingAs($user)->post(route('bookings.store-product', $product), [
            'quantity' => 4,
            'contact_name' => 'Siti',
            'contact_phone' => '08987654321',
        ]);

        $booking = Booking::query()->first();

        $response->assertRedirect(route('bookings.show', $booking->code));

        $this->assertSame(4, $booking->quantity);
        $this->assertSame(80000, $booking->total_price);
        $this->assertSame($owner->getKey(), $booking->owner_id);
    }

    public function test_pembayaran_demo_menandai_lunas(): void
    {
        $user = $this->wisatawan();
        $booking = Booking::factory()->create(['user_id' => $user->getKey()]);

        $response = $this->actingAs($user)->post(route('bookings.pay', $booking->code), [
            'payment_method' => 'transfer',
        ]);

        $response->assertRedirect(route('bookings.show', $booking->code));

        $booking->refresh();

        $this->assertSame(BookingStatus::Paid, $booking->status);
        $this->assertSame('transfer', $booking->payment_method);
        $this->assertNotNull($booking->paid_at);
    }

    public function test_pengguna_lain_tidak_bisa_melihat_pesanan_orang(): void
    {
        $owner = $this->wisatawan();
        $other = $this->wisatawan();
        $booking = Booking::factory()->create(['user_id' => $owner->getKey()]);

        $this->actingAs($other)->get(route('bookings.show', $booking->code))->assertForbidden();
    }

    public function test_pesanan_yang_sudah_dibayar_tidak_bisa_dibatalkan(): void
    {
        $user = $this->wisatawan();
        $booking = Booking::factory()->paid()->create(['user_id' => $user->getKey()]);

        $response = $this->actingAs($user)->post(route('bookings.cancel', $booking->code));

        $response->assertSessionHasErrors('booking');
        $this->assertSame(BookingStatus::Paid, $booking->fresh()->status);
    }

    public function test_pemilik_mendapat_notifikasi_pesanan_baru(): void
    {
        $owner = User::factory()->pemilikPenginapan()->create();
        $accommodation = $this->accommodationWithOwner($owner);

        $this->actingAs($this->wisatawan())->post(route('bookings.store-accommodation', $accommodation), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'contact_name' => 'Budi',
            'contact_phone' => '08123',
        ]);

        $this->assertSame(
            1,
            AppNotification::query()
                ->where('user_id', $owner->getKey())
                ->where('type', AppNotification::TYPE_BOOKING)
                ->count(),
        );
    }

    public function test_halaman_pesanan_menampilkan_milik_pengguna(): void
    {
        $user = $this->wisatawan();
        $booking = Booking::factory()->create(['user_id' => $user->getKey()]);

        $this->actingAs($user)->get(route('bookings.index'))
            ->assertOk()
            ->assertSee('Pesanan Saya')
            ->assertSee($booking->code);
    }

    public function test_halaman_penginapan_menampilkan_form_pesan_demo(): void
    {
        $accommodation = Accommodation::factory()->published()->create(['price_per_night' => 200000]);

        $this->actingAs($this->wisatawan())
            ->get(route('accommodations.show', $accommodation->slug))
            ->assertOk()
            ->assertSee('Pesan Menginap')
            ->assertSee('DEMO');
    }

    public function test_halaman_umkm_menampilkan_tombol_beli_demo(): void
    {
        $business = UmkmBusiness::factory()->published()->create();
        $product = UmkmProduct::factory()->create([
            'umkm_business_id' => $business->getKey(),
            'is_available' => true,
        ]);

        $this->actingAs($this->wisatawan())
            ->get(route('umkm.show', $business->slug))
            ->assertOk()
            ->assertSee('Beli (Demo)');
    }
}
