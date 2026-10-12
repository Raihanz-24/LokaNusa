<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_dapat_diakses_tamu(): void
    {
        $this->get('/')->assertSuccessful()->assertSee('LokaNusa');
    }

    public function test_tamu_diarahkan_ke_beranda_dengan_popup_saat_membuka_halaman_lain(): void
    {
        $response = $this->get('/destinasi');

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('auth_prompt', true);

        // Tujuan disimpan untuk dilanjutkan setelah login.
        $this->assertSame(url('/destinasi'), session('url.intended'));
    }

    public function test_beranda_menampilkan_popup_setelah_gating(): void
    {
        $this->get('/destinasi');

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('One step away from your adventure')
            ->assertSee('data-auto-open="1"', false);
    }

    public function test_halaman_masuk_dapat_diakses_tamu(): void
    {
        $this->get(route('login'))
            ->assertSuccessful()
            ->assertSee('Sign in to continue exploring the archipelago.');
    }

    public function test_tamu_dapat_mendaftar_dan_langsung_masuk(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Wisatawan Baru',
            'email' => 'baru@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'baru@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Wisatawan, $user->role);
    }

    public function test_pendaftaran_mengembalikan_ke_tujuan_awal(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Wisatawan Baru',
            'email' => 'baru2@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'redirect' => '/umkm',
        ]);

        $response->assertRedirect('/umkm');
    }

    public function test_login_gagal_dengan_kredensial_salah(): void
    {
        User::factory()->wisatawan()->create([
            'email' => 'ada@example.com',
            'password' => 'rahasia123',
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'ada@example.com',
            'password' => 'salah-sekali',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_berhasil_dan_kembali_ke_tujuan(): void
    {
        $user = User::factory()->wisatawan()->create([
            'email' => 'wisata@example.com',
            'password' => 'rahasia123',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'wisata@example.com',
            'password' => 'rahasia123',
            'redirect' => '/destinasi',
        ]);

        $response->assertRedirect('/destinasi');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_dibatasi_setelah_terlalu_banyak_percobaan(): void
    {
        User::factory()->wisatawan()->create([
            'email' => 'throttle@example.com',
            'password' => 'rahasia123',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), [
                'email' => 'throttle@example.com',
                'password' => 'salah-sekali',
            ]);
        }

        $response = $this->post(route('login'), [
            'email' => 'throttle@example.com',
            'password' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_redirect_eksternal_diabaikan(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Wisatawan Baru',
            'email' => 'baru3@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'redirect' => 'https://jahat.example.com',
        ]);

        $response->assertRedirect(route('home'));
    }

    public function test_pengguna_terautentikasi_dapat_membuka_halaman_publik(): void
    {
        $this->actingAs(User::factory()->wisatawan()->create())
            ->get('/destinasi')
            ->assertSuccessful();
    }

    public function test_pengguna_dapat_keluar(): void
    {
        $this->actingAs(User::factory()->wisatawan()->create());

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_tautan_lupa_kata_sandi_mengirim_email_reset(): void
    {
        Notification::fake();

        $user = User::factory()->wisatawan()->create(['email' => 'lupa@example.com']);

        $this->post(route('password.email'), ['email' => 'lupa@example.com'])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_pengguna_dapat_mengatur_ulang_kata_sandi(): void
    {
        $user = User::factory()->wisatawan()->create(['email' => 'reset@example.com']);

        $token = app('auth.password.broker')->createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('sandi-baru-123', $user->fresh()->password));
    }
}
