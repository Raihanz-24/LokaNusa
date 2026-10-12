<?php

namespace App\Enums;

/**
 * Daftar permission administratif LokaNusa.
 *
 * Permission menjadi dasar definisi Gate; peran memetakan permission pada
 * {@see UserRole::permissions()}.
 */
enum Permission: string
{
    case ManageUsers = 'manage-users';
    case ManageRegions = 'manage-regions';
    case ManageSettings = 'manage-settings';
    case ManageDestinations = 'manage-destinations';
    case ManageUmkm = 'manage-umkm';
    case ManageAccommodations = 'manage-accommodations';
    case ManageCulture = 'manage-culture';
    case ManageEvents = 'manage-events';
    case ModerateContent = 'moderate-content';
    case ManageAiKnowledge = 'manage-ai-knowledge';

    public function label(): string
    {
        return match ($this) {
            self::ManageUsers => 'Kelola Pengguna',
            self::ManageRegions => 'Kelola Wilayah',
            self::ManageSettings => 'Kelola Pengaturan Aplikasi',
            self::ManageDestinations => 'Kelola Destinasi',
            self::ManageUmkm => 'Kelola UMKM',
            self::ManageAccommodations => 'Kelola Penginapan',
            self::ManageCulture => 'Kelola Budaya',
            self::ManageEvents => 'Kelola Event',
            self::ModerateContent => 'Moderasi Konten',
            self::ManageAiKnowledge => 'Kelola Basis Pengetahuan AI',
        };
    }
}
