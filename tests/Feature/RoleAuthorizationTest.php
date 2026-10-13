<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_memiliki_semua_permission(): void
    {
        $user = User::factory()->superAdmin()->create();

        foreach (Permission::cases() as $permission) {
            $this->assertTrue($user->hasPermission($permission));
            $this->assertTrue(Gate::forUser($user)->allows($permission->value));
        }
    }

    public function test_admin_pemerintah_boleh_kelola_pengguna_tapi_tidak_akses_penuh(): void
    {
        $user = User::factory()->adminPemerintah()->create();

        $this->assertTrue(Gate::forUser($user)->allows(Permission::ManageUsers->value));
        $this->assertFalse($user->isSuperAdmin());

        $this->actingAs($user);
        $this->assertTrue(UserResource::canViewAny());
    }

    public function test_wisatawan_tidak_boleh_mengakses_panel(): void
    {
        $user = User::factory()->wisatawan()->create();

        $this->assertFalse($user->canAccessPanel(filament()->getDefaultPanel()));

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_wisatawan_tidak_boleh_kelola_pengguna(): void
    {
        $user = User::factory()->wisatawan()->create();

        $this->assertFalse(Gate::forUser($user)->allows(Permission::ManageUsers->value));

        $this->actingAs($user);
        $this->assertFalse(UserResource::canViewAny());
    }

    public function test_pengelola_destinasi_boleh_panel_tapi_tidak_kelola_pengguna(): void
    {
        $user = User::factory()->role(UserRole::PemilikDestinasi)->create();

        $this->assertTrue($user->canAccessPanel(filament()->getDefaultPanel()));
        $this->assertFalse($user->hasPermission(Permission::ManageUsers));
        $this->assertTrue($user->hasPermission(Permission::ManageDestinations));
    }

    public function test_pengelola_lokanusa_punya_akses_penuh_kecuali_basis_pengetahuan(): void
    {
        $user = User::factory()->pengelolaLokanusa()->create();

        $this->assertTrue($user->hasPermission(Permission::ManageDestinations));
        $this->assertTrue($user->hasPermission(Permission::ManageUsers));
        $this->assertTrue($user->hasPermission(Permission::ModerateContent));
        $this->assertFalse($user->hasPermission(Permission::ManageAiKnowledge));
    }

    public function test_developer_disembunyikan_dari_peran_lain(): void
    {
        // Developer tidak melihat akun Developer lain.
        $options = UserRole::options(UserRole::PengelolaLokanusa);
        $this->assertArrayNotHasKey(UserRole::SuperAdmin->value, $options);

        // Developer tetap melihat peran Developer.
        $optionsForDev = UserRole::options(UserRole::SuperAdmin);
        $this->assertArrayHasKey(UserRole::SuperAdmin->value, $optionsForDev);
    }

    public function test_pengguna_tidak_boleh_menghapus_dirinya_sendiri(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertFalse(Gate::forUser($user)->allows('delete', $user));
    }

    public function test_admin_biasa_tidak_boleh_menghapus_super_admin(): void
    {
        $admin = User::factory()->adminPemerintah()->create();
        $super = User::factory()->superAdmin()->create();

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $super));
        $this->assertTrue(Gate::forUser($super)->allows('delete', $admin));
    }
}
