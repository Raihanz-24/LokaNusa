<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder akun Developer (peran tertinggi) pertama (aman untuk produksi).
 *
 * Kredensial dibaca dari .env agar tidak tersimpan di kode/repo:
 *   ADMIN_EMAIL=super@lokanusa.test
 *   ADMIN_NAME="Developer"
 *   ADMIN_PASSWORD=rahasia-kuat   # bila kosong → password acak ditampilkan
 *
 * Bila akun sudah ada, password TIDAK ditimpa (idempoten).
 *
 * Jalankan: php artisan db:seed --class=SuperAdminSeeder --force
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) env('ADMIN_EMAIL', 'super@lokanusa.test')));
        $name = (string) env('ADMIN_NAME', 'Developer');
        $password = (string) env('ADMIN_PASSWORD', '');

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            $existing->forceFill([
                'name' => $name,
                'role' => UserRole::SuperAdmin,
            ])->save();

            $this->command?->info("Akun Developer sudah ada: {$email} (nama disinkronkan, password tidak diubah).");

            return;
        }

        $generated = false;
        if ($password === '') {
            $password = Str::password(16);
            $generated = true;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Akun Developer dibuat: {$email}");

        if ($generated) {
            $this->command?->warn("Password acak (catat sekarang): {$password}");
        }
    }
}
