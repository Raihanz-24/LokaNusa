# LokaNusa — Panduan Deploy ke Server

Dokumen ini menjelaskan cara **mendeploy LokaNusa ke server** (uji coba / staging / produksi)
setelah kode di-*push* ke Git atau di-*pull* di server.

> Lingkungan yang diasumsikan: **Linux (Ubuntu/Debian) + Nginx/Apache + PHP 8.2 + MySQL 8 + Composer + Node.js**.
> LokaNusa = Laravel 12 + Filament 5 (panel `/admin`) + Blade/Tailwind v4 (sisi publik).

---

## 0. Ringkas: apa yang WAJIB dilakukan di server

Setiap kali kode masuk ke server, jalankan **langkah 6–7** di bawah. Intinya:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build            # atau "npm install && npm run build"
php artisan filament:assets        # WAJIB: aset panel admin
php artisan storage:link           # link public/storage -> storage/app/public
php artisan optimize               # cache config/route/view
```

> **Penting:** folder `public/build` (hasil Vite) dan `public/css|js|fonts/filament`
> **TIDAK ikut Git** (ada di `.gitignore`). Jadi di server **wajib** dihasilkan ulang
> lewat `npm run build` dan `php artisan filament:assets`.

---

## 1. Prasyarat server

- PHP **8.2+** dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`,
  `json`, `bcmath`, `fileinfo`, `curl`, **`gd`** (untuk gambar ikon), `zip`.
- Composer 2.x.
- Node.js 20+ & npm (hanya untuk **build aset**; boleh di mesin lain lalu diunggah).
- MySQL 8.
- Web server (Nginx disarankan) dengan **document root mengarah ke folder `public/`**.
- Git (opsional, jika deploy via clone/pull).

---

## 2. Ambil kode

**A. Via Git (disarankan):**
```bash
cd /var/www
git clone <URL_REPO> loka-nusa
cd loka-nusa
# atau pada update berikutnya:
git pull origin main
```

**B. Via unggah manual (FTP/SFTP/zip):**
Unggah seluruh isi proyek **kecuali** `node_modules`, `vendor`, `.env`, `public/build`.
Folder-folder itu akan dibuat ulang di langkah kompilasi.

---

## 3. Konfigurasi environment (`.env`)

```bash
cp .env.example .env          # (Windows: copy .env.example .env)
php artisan key:generate
```

Sunting `.env` untuk **produksi**. Nilai penting:

| Variabel | Nilai produksi | Keterangan |
|---|---|---|
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | **jangan** `true` di server publik |
| `APP_URL` | `https://namadomain.com` | tanpa garis miring di akhir |
| `APP_KEY` | (hasil `key:generate`) | jangan dibagikan |
| `APP_LOCALE` | `en` atau `id` | bahasa default (bisa diganti pengguna) |
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` | `127.0.0.1` | |
| `DB_DATABASE` | `loka-nusa` | |
| `DB_USERNAME` | `lokanusa` | **bukan** `root` di produksi |
| `DB_PASSWORD` | `••••••` | password kuat |
| `SESSION_DRIVER` | `database` | aman untuk 1 server (tabel dibuat saat migrate) |
| `CACHE_STORE` | `database` | tabel `cache` dibuat saat migrate |
| `QUEUE_CONNECTION` | `database` | butuh worker (lihat §9) |
| `MAIL_MAILER` | `smtp` (atau `log` untuk uji) | untuk reset kata sandi/verifikasi email |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_FROM_ADDRESS` | sesuai SMTP | |
| `AI_BASE_URL` | `https://api.kryptonlab.id/v1` | gateway AI |
| `AI_API_KEY` | `kl_liv_...` | **rahasia**, jangan commit |
| `AI_MODEL` | `deepseek-v4.1-flash` | |

> ⚠️ `AI_API_KEY` berbeda antara lokal & server. Jangan pernah menaruhnya di kode/Git.
> File `.env` sudah masuk `.gitignore`.

---

## 4. Dependency & database

```bash
composer install --no-dev --optimize-autoloader
```

Buat database (contoh):
```sql
CREATE DATABASE `loka-nusa` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Jalankan migrasi:
```bash
php artisan migrate --force
```

---

## 5. Data awal

### 5a. Akun Developer/Admin pertama
Atur di `.env` sebelum seeding (opsional, default aman):
```
ADMIN_EMAIL=email-anda@domain.com
ADMIN_NAME="Developer"
ADMIN_PASSWORD=password-kuat      # kosong = dibuat acak & ditampilkan
```
```bash
php artisan db:seed --class=SuperAdminSeeder --force
```

### 5b. Data wilayah Indonesia (wajib agar fokus kabupaten & dropdown jalan)
Pilih **salah satu**:

```bash
# A. Dari berkas offline (cepat, tanpa internet)
php artisan db:seed --class=RegionSeeder --force
```
Atau impor `database/data/wilayah.sql` via phpMyAdmin/CLI:
```bash
mysql -u lokanusa -p loka-nusa < database/data/wilayah.sql
```

### 5c. Desa kabupaten fokus (butuh internet)
```bash
php artisan wilayah:import-villages
```

### 5d. Kabupaten fokus & konten beranda
- Login ke `/admin` → **Pengaturan Aplikasi** → tetapkan **kabupaten/kota fokus**.
- Atur **Banner Beranda / Shortcut Beranda / Rekomendasi** bila perlu
  (`php artisan db:seed --class=HomeContentSeeder` untuk konten bawaan).

> **Jangan** menjalankan `DummySeeder` di produksi (itu data contoh pengembangan).

---

## 6. Bangun aset front-end

```bash
npm ci            # atau: npm install
npm run build
```

Menghasilkan `public/build/*` (CSS/JS publik, Tailwind v4).

> Jika server menyetel `NODE_ENV=production`, `npm ci` bisa melewati *devDependencies*
> padahal Vite/Tailwind ada di sana. Jalankan `npm ci --include=dev` bila build gagal.

---

## 7. Aset panel admin, storage link, optimasi

```bash
php artisan filament:assets       # aset Filament ke public/css|js|fonts/filament
php artisan storage:link          # public/storage -> storage/app/public (gambar unggahan)
php artisan optimize              # cache config + route + view (produksi)
```

**Hak akses folder** (Linux):
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```
Pastikan web server bisa **menulis**: `storage/` dan `bootstrap/cache/`.

---

## 8. Konfigurasi web server

Document root **harus** `…/loka-nusa/public`.

### Nginx
```nginx
server {
    listen 80;
    server_name namadomain.com www.namadomain.com;
    root /var/www/loka-nusa/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }

    client_max_body_size 20M;   # untuk unggah gambar
}
```

### Apache
Document root ke `public`. `.htaccess` sudah disediakan Laravel.
```apache
<VirtualHost *:80>
    ServerName namadomain.com
    DocumentRoot /var/www/loka-nusa/public

    <Directory /var/www/loka-nusa/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
Aktifkan modul: `sudo a2enmod rewrite && sudo systemctl restart apache2`.

### HTTPS
Gunakan Certbot: `sudo certbot --nginx -d namadomain.com -d www.namadomain.com`.
Setelah HTTPS aktif, pastikan `APP_URL` memakai `https://`.

---

## 9. Scheduler & queue (produksi)

**Cron** (untuk tugas terjadwal):
```
* * * * * cd /var/www/loka-nusa && php artisan schedule:run >> /dev/null 2>&1
```

**Queue worker** (karena `QUEUE_CONNECTION=database`) — pakai Supervisor:
```ini
[program:lokanusa-worker]
command=php /var/www/loka-nusa/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/loka-nusa/storage/logs/worker.log
stopwaitsecs=3600
```
```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start lokanusa-worker:*
```

> Untuk uji coba tanpa email, biarkan `MAIL_MAILER=log` (email tersimpan di `storage/logs/laravel.log`).

---

## 10. Perintah update (setiap ada perubahan kode)

Setelah `git pull` (atau unggah kode baru) di server:

```bash
cd /var/www/loka-nusa

# mode maintenance saat update (opsional, hindari error transien)
php artisan down --render="errors::503"

git pull origin main

composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan filament:assets

php artisan optimize:clear
php artisan optimize

php artisan up
```

> Jika `.env.example` berubah, bandingkan dengan `.env` dan tambahkan variabel baru manual.
> Jika ada aset Filament/Livewire baru, `filament:assets` sudah menanganinya.

---

## 11. Checklist keamanan sebelum publik

- [ ] `APP_ENV=production`, `APP_DEBUG=false`.
- [ ] `APP_KEY` sudah di-generate (`php artisan key:generate`).
- [ ] User DB **bukan** `root`; password kuat.
- [ ] Media root = folder `public/` (bukan akar proyek).
- [ ] `storage:link` sudah dibuat (gambar unggahan tampil).
- [ ] `filament:assets` & `npm run build` sudah dijalankan.
- [ ] `AI_API_KEY`, kredensial SMTP, kredensial DB **tidak** ada di Git.
- [ ] HTTPS aktif.
- [ ] Hak tulis `storage/` & `bootstrap/cache/` benar.
- [ ] Backup database terjadwal.
- [ ] (Opsional) Batasi akses `/admin` (VPN/IP allowlist) atau aktifkan 2FA.

---

## 12. Troubleshooting umum

| Gejala | Penyebab & solusi |
|---|---|
| **Halaman putih / 500** | Lihat `storage/logs/laravel.log`. Biasanya `APP_KEY` kosong atau folder `storage`/`bootstrap/cache` tidak writable. |
| **CSS/JS publik tidak termuat** | `public/build` belum ada → jalankan `npm run build` (aset tidak ikut Git). |
| **Panel `/admin` tampil polos/tanpa gaya** | Jalankan `php artisan filament:assets`. |
| **Gambar unggahan 404** | `php artisan storage:link` belum dibuat, atau `APP_ASSET_URL` salah (harus `/storage`). |
| **403 Forbidden** | Document root salah (harus `public/`) atau permission folder. |
| **Error setelah ubah `.env`** | `php artisan optimize:clear` lalu `php artisan optimize`. |
| **Perubahan view tidak tampil** | `php artisan view:clear`. |
| **Livewire error 419** | Pastikan `APP_URL` sesuai domain & HTTPS; session driver konsisten. |
| **Reset sandi tidak terkirim** | Atur `MAIL_*` yang benar, lalu jalankan queue worker. |

---

## 13. Catatan khusus Windows / Laragon (uji lokal di server Windows)

- `php artisan storage:link` di Windows membuat *junction* (bukan symlink). Sudah berfungsi.
- Jalankan queue worker & scheduler via Task Scheduler bila perlu.
- Arahkan virtual host Laragon ke folder **`public`** proyek.

---

## Lampiran — Perintah "sekali jalan" untuk server baru

```bash
cp .env.example .env
# >>> sunting .env (DB, APP_URL, APP_ENV=production, APP_DEBUG=false, AI, MAIL)
php artisan key:generate
composer install --no-dev --optimize-autoloader
# >>> buat database kosong dulu
php artisan migrate --force
php artisan db:seed --class=RegionSeeder --force
php artisan db:seed --class=SuperAdminSeeder --force
php artisan db:seed --class=HomeContentSeeder --force
npm ci && npm run build
php artisan filament:assets
php artisan storage:link
php artisan optimize
# >>> lalu: login /admin, set kabupaten fokus, impor desa (wilayah:import-villages)
```
