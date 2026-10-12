<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Membuat / memperbarui akun administrator melalui baris perintah.
 *
 * Akun yang dibuat diberi peran Developer (peran tertinggi). Berguna untuk membuat akun
 * pertama di server tanpa seeder yang menaruh password default. Bila
 * --password tidak diberikan, password acak yang kuat akan dibuat.
 */
class MakeAdminCommand extends Command
{
    protected $signature = 'lokanusa:make-admin
                            {email : Email administrator}
                            {--name= : Nama tampilan}
                            {--password= : Password (min. 8 karakter). Bila kosong → dibuat acak}
                            {--role=super_admin : Peran (default super_admin)}';

    protected $description = 'Buat/perbarui akun administrator (untuk akun pertama & reset).';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $name = trim((string) ($this->option('name') ?: ''));
        $password = (string) ($this->option('password') ?: '');
        $role = UserRole::tryFrom((string) $this->option('role'));

        if ($role === null) {
            $this->error('Peran tidak valid. Pilihan: '.implode(', ', UserRole::values()));

            return self::FAILURE;
        }

        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($name === '') {
            $name = ucfirst(explode('@', $email)[0]);
        }

        $generated = false;
        if ($password === '') {
            $password = Str::password(16);
            $generated = true;
        }

        if (strlen($password) < 8) {
            $this->error('Password minimal 8 karakter.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        $isNew = $user === null;

        if ($isNew) {
            $user = new User;
        }

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'email_verified_at' => now(),
        ])->save();

        $this->info($isNew
            ? "Akun administrator dibuat: {$email} (peran: {$role->value})"
            : "Akun administrator diperbarui: {$email} (peran: {$role->value})");

        if ($generated) {
            $this->warn("Password (catat sekarang, tidak ditampilkan lagi): {$password}");
        }

        return self::SUCCESS;
    }
}
