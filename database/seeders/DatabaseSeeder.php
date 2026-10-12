<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Catatan: tidak membuat data contoh/dummy. Yang di-seed hanya data
     * referensi resmi (wilayah Indonesia) dan akun administrator (bila
     * variabel ADMIN_* diset di .env).
     */
    public function run(): void
    {
        $this->call([
            RegionSeeder::class,
            SuperAdminSeeder::class,
            HomeContentSeeder::class,
        ]);
    }
}
