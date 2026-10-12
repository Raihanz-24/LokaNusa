# LokaNusa — Setup Lokal

## Prasyarat
- PHP 8.2 dengan ekstensi `pdo_mysql` (driver `pdo_pgsql`/`pdo_sqlite` tidak tersedia di lingkungan ini).
- Composer 2.x, Node 20+ / npm.
- MySQL 8 (Laragon).

## Langkah
```bash
composer install
cp .env.example .env        # (Windows) copy .env.example .env
php artisan key:generate
```

Buat database:
```sql
CREATE DATABASE `loka-nusa` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE `loka-nusa-test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate
php artisan storage:link
npm install && npm run build
php artisan serve
```

Panel admin: `http://localhost:8000/admin`
Sisi publik: `http://localhost:8000/` (beranda), `/destinasi` (daftar), `/destinasi/{slug}` (detail).

> Sisi publik memakai **Blade + Vite (Tailwind v4)**. Jalankan `npm run dev` saat pengembangan, atau `npm run build` untuk aset produksi.

## Data wilayah Indonesia

Data wilayah (provinsi → kabupaten/kota → kecamatan) sudah **dibundel offline**
di `database/data/wilayah.json` (± 7.800 baris, sumber: wilayah.id).

**Cara mengisi:**
```bash
php artisan db:seed --class=RegionSeeder      # dari berkas offline, tanpa internet (~4 detik)
# atau sekaligus: php artisan migrate --seed
```

**Di server (alternatif tanpa artisan untuk data besar):** setelah `php artisan migrate`,
impor `database/data/wilayah.sql` lewat phpMyAdmin (tab Import). Isi tabel sama
(7.837 wilayah).

**Memperbarui data** (butuh internet):
```bash
php artisan wilayah:import     # ambil dari wilayah.id ke DB
php artisan wilayah:export     # tulis ulang bundle JSON + SQL
```
`--depth=provinsi|kabupaten|kecamatan` (default: kecamatan), `--fresh` untuk bersihkan dulu.


## Testing
- Test memakai MySQL `loka-nusa-test` (lihat `phpunit.xml`).
- Jalankan: `php artisan test`
- Test memakai `RefreshDatabase` → data test akan di-reset otomatis. Jangan arahkan ke DB produksi.

> **Deploy ke server:** lihat [`DEPLOY.md`](../DEPLOY.md).

## Konfigurasi penting
| Env | Nilai | Alasan |
|---|---|---|
| `DB_CONNECTION` | `mysql` | driver yang tersedia |
| `APP_LOCALE` | `id` | UI Bahasa Indonesia |
| `APP_FALLBACK_LOCALE` | `en` | fallback |
