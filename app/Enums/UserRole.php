<?php

namespace App\Enums;

/**
 * Peran pengguna LokaNusa (RBAC sederhana berbasis enum).
 *
 * Setiap peran memetakan sejumlah {@see Permission}. Detail peran dapat
 * diperluas tanpa mengubah skema database (kolom `users.role`).
 *
 * Catatan penataan (Step 20): peran admin yang lama (admin wilayah, pengelola
 * destinasi/penginapan/event) disederhanakan menjadi satu peran umum
 * {@see self::PengelolaLokanusa} yang mengelola seluruh konten. Sisi pemilik
 * usaha diwakili {@see self::PemilikDestinasi}, {@see self::PemilikUmkm}, dan
 * {@see self::PemilikPenginapan}.
 */
enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case AdminPemerintah = 'admin_pemerintah';
    case PengelolaLokanusa = 'pengelola_lokanusa';
    case PemilikDestinasi = 'pemilik_destinasi';
    case PemilikUmkm = 'pemilik_umkm';
    case PemilikPenginapan = 'pemilik_penginapan';
    case KontributorBudaya = 'kontributor_budaya';
    case Wisatawan = 'wisatawan';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Developer',
            self::AdminPemerintah => 'Admin Pemerintah',
            self::PengelolaLokanusa => 'Pengelola LokaNusa',
            self::PemilikDestinasi => 'Pemilik Destinasi',
            self::PemilikUmkm => 'Pemilik UMKM',
            self::PemilikPenginapan => 'Pemilik Penginapan',
            self::KontributorBudaya => 'Kontributor Budaya',
            self::Wisatawan => 'Wisatawan',
        };
    }

    /**
     * Warna badge Filament.
     */
    public function color(): string
    {
        return match ($this) {
            self::SuperAdmin => 'danger',
            self::AdminPemerintah => 'warning',
            self::PengelolaLokanusa => 'success',
            self::PemilikDestinasi => 'info',
            self::PemilikUmkm => 'primary',
            self::PemilikPenginapan => 'violet',
            self::KontributorBudaya => 'gray',
            self::Wisatawan => 'gray',
        };
    }

    /**
     * Daftar permission yang dimiliki peran ini.
     *
     * @return array<int, Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => Permission::cases(),
            self::AdminPemerintah => [
                Permission::ManageUsers,
                Permission::ManageDestinations,
                Permission::ManageUmkm,
                Permission::ManageAccommodations,
                Permission::ManageCulture,
                Permission::ManageEvents,
                Permission::ModerateContent,
            ],
            // Pengelola LokaNusa: akses penuh seluruh modul & moderasi,
            // KECUALI basis pengetahuan AI (khusus Developer).
            self::PengelolaLokanusa => array_values(array_filter(
                Permission::cases(),
                fn (Permission $permission): bool => $permission !== Permission::ManageAiKnowledge,
            )),
            self::PemilikDestinasi => [
                Permission::ManageDestinations,
            ],
            self::PemilikUmkm => [
                Permission::ManageUmkm,
            ],
            self::PemilikPenginapan => [
                Permission::ManageAccommodations,
            ],
            self::KontributorBudaya => [
                Permission::ManageCulture,
            ],
            self::Wisatawan => [],
        };
    }

    /**
     * Apakah peran ini boleh mengelola SELURUH konten (bukan hanya miliknya).
     */
    public function managesAllContent(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::AdminPemerintah, self::PengelolaLokanusa => true,
            default => false,
        };
    }

    /**
     * Apakah peran ini adalah "pemilik usaha" (hanya mengelola miliknya dan
     * mengajukan untuk ditinjau Pengelola LokaNusa).
     */
    public function isOwner(): bool
    {
        return match ($this) {
            self::PemilikDestinasi, self::PemilikUmkm, self::PemilikPenginapan => true,
            default => false,
        };
    }

    /**
     * Peran yang bertugas memoderasi konten (menyetujui/menolak).
     */
    public function isModerator(): bool
    {
        return $this->can(Permission::ModerateContent);
    }

    /**
     * Apakah peran ini memiliki (minimal satu dari) permission tertentu.
     */
    public function can(Permission ...$permissions): bool
    {
        $owned = $this->permissions();

        foreach ($permissions as $permission) {
            if (in_array($permission, $owned, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Peran yang boleh mengakses panel admin.
     */
    public function canAccessPanel(): bool
    {
        return $this !== self::Wisatawan;
    }

    /**
     * Opsi peran untuk formulir. Peran {@see self::SuperAdmin} (Developer)
     * disembunyikan dari semua peran di bawahnya agar tidak terlihat/terpilih.
     *
     * @return array<string, string>
     */
    public static function options(?UserRole $viewerRole = null): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            if ($case === self::SuperAdmin && $viewerRole !== self::SuperAdmin) {
                continue;
            }

            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
