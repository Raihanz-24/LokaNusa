<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_ditolak_dan_diarahkan_ke_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_halaman_login_tampil(): void
    {
        $this->get('/admin/login')->assertSuccessful();
    }

    public function test_pengguna_terautentikasi_bisa_mengakses_panel(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_pengguna_terautentikasi_bisa_melihat_daftar_pengguna(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->get('/admin/users')->assertSuccessful();
    }

    public function test_daftar_pengguna_menampilkan_record(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        $others = User::factory(2)->create();

        Livewire::test(ListUsers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($others);
    }

    public function test_command_membuat_administrator_dengan_password_ter_hash(): void
    {
        $this->artisan('lokanusa:make-admin', [
            'email' => 'admin-baru@lokanusa.test',
            '--name' => 'Admin Baru',
            '--password' => 'rahasia123',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'admin-baru@lokanusa.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('Admin Baru', $user->name);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_command_menolak_password_pendek(): void
    {
        $this->artisan('lokanusa:make-admin', [
            'email' => 'x@lokanusa.test',
            '--password' => 'pendek',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'x@lokanusa.test']);
    }
}
