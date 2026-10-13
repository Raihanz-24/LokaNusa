<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Penataan peran (Step 20).
 *
 * Peran lama disederhanakan:
 *  - admin_wilayah, pengelola_destinasi, pengelola_penginapan, pengelola_event
 *    -> pengelola_lokanusa (pengelola umum seluruh konten).
 *
 * Pengguna dengan peran baru (pengelola_lokanusa, pemilik_destinasi,
 * pemilik_penginapan) tidak butuh perubahan skema karena kolom `role` berupa
 * string. Konten lama tetap utuh (kepemilikan pada pemilik dipertahankan).
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $legacyRoles = [
        'admin_wilayah',
        'pengelola_destinasi',
        'pengelola_penginapan',
        'pengelola_event',
    ];

    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', $this->legacyRoles)
            ->update(['role' => UserRole::PengelolaLokanusa->value]);
    }

    public function down(): void
    {
        // Tidak dapat dipulihkan per peran asal secara pasti; kembalikan ke
        // peran admin wilayah sebagai default paling dekat.
        DB::table('users')
            ->where('role', UserRole::PengelolaLokanusa->value)
            ->update(['role' => 'admin_wilayah']);
    }
};
