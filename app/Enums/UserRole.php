<?php

namespace App\Enums;

/**
 * Peran pengguna LokaNusa (RBAC sederhana berbasis enum).
 *
 * Setiap peran memetakan sejumlah {@see Permission}. Detail peran dapat
 * diperluas tanpa mengubah skema database (kolom `users.role`).
 */
enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case AdminPemerintah = 'admin_pemerintah';
    case AdminWilayah = 'admin_wilayah';
    case PengelolaDestinasi = 'pengelola_destinasi';
    case PemilikUmkm = 'pemilik_umkm';
    case PengelolaPenginapan = 'pengelola_penginapan';
    case KontributorBudaya = 'kontributor_budaya';
    case PengelolaEvent = 'pengelola_event';
    case Wisatawan = 'wisatawan';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Developer',
            self::AdminPemerintah => 'Admin Pemerintah',
            self::AdminWilayah => 'Admin Wilayah',
            self::PengelolaDestinasi => 'Pengelola Destinasi',
            self::PemilikUmkm => 'Pemilik UMKM',
            self::PengelolaPenginapan => 'Pengelola Penginapan',
            self::KontributorBudaya => 'Kontributor Budaya',
            self::PengelolaEvent => 'Pengelola Event',
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
            self::AdminWilayah => 'info',
            self::PengelolaDestinasi => 'success',
            self::PemilikUmkm => 'primary',
            self::PengelolaPenginapan => 'info',
            self::KontributorBudaya => 'gray',
            self::PengelolaEvent => 'violet',
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
            self::AdminWilayah => [
                Permission::ManageDestinations,
                Permission::ManageUmkm,
                Permission::ManageAccommodations,
                Permission::ManageCulture,
                Permission::ManageEvents,
                Permission::ModerateContent,
            ],
            self::PengelolaDestinasi => [
                Permission::ManageDestinations,
            ],
            self::PemilikUmkm => [
                Permission::ManageUmkm,
            ],
            self::PengelolaPenginapan => [
                Permission::ManageAccommodations,
            ],
            self::KontributorBudaya => [
                Permission::ManageCulture,
            ],
            self::PengelolaEvent => [
                Permission::ManageEvents,
            ],
            self::Wisatawan => [],
        };
    }

    /**
     * Apakah peran ini boleh mengelola SELURUH konten (bukan hanya miliknya),
     * yaitu peran admin (super admin, admin pemerintah/wilayah).
     */
    public function managesAllContent(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::AdminPemerintah, self::AdminWilayah => true,
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
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
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
