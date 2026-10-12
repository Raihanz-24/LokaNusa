# LokaNusa — Architecture

> Ekosistem smart tourism: wisatawan, UMKM, destinasi, budaya, penginapan, event, pemerintah.
> Dokumen ini adalah sumber kebenaran arsitektur. Perubahan besar wajib disetujui pemilik proyek.

## 1. Prinsip

- **Modular monolith.** Satu aplikasi Laravel; batas modul tegas lewat namespace & folder.
- **Business logic terpisah dari Filament** (service/action class) agar bisa dipakai PWA, API, jobs.
- **Server-side authorization** untuk setiap operasi sensitif (Policy/Gate), bukan sekadar sembunyikan tombol.
- **Tidak ada data/statistik palsu** yang ditampilkan sebagai data nyata.
- Hindari abstraksi prematur; bangun hanya yang dibutuhkan step aktif.

## 2. Stack (aktual lingkungan)

| Komponen | Versi | Catatan |
|---|---|---|
| PHP | 8.2.30 | driver PDO: **mysql** saja |
| Laravel | 12.69.3 | |
| Filament | 5.10.1 | API v5 (berbeda dari v3) |
| Database | **MySQL 8.0.30** | keputusan: ganti dari PostgreSQL (driver pgsql & sqlite tidak tersedia) |
| Node | 24 / npm 11 | Vite 7, Tailwind 4 |
| PWA | manifest + service worker | Step 16 |
| Testing | PHPUnit 11 | test DB: MySQL `loka-nusa-test` |
| Version control | Git | status: lihat DEVELOPMENT_STATUS |

## 3. Struktur Domain (target)

```
app/
  Domain/                      # (dibuat saat dibutuhkan) business logic per modul
    Identity/
    Region/
    Destination/
    Umkm/
    Culture/
    Accommodation/
    Event/
    Tourist/
    Engagement/                # favorit, review, rating
    QrCode/
    Itinerary/
    Notification/
    Analytics/
    Booking/
    Intelligence/
  Models/                      # Eloquent models
  Policies/                    # otorisasi
  Services/                    # service lintas modul
  Filament/                    # UI admin (Resources, Pages, Widgets)
  Http/                        # controller web publik + API
```

> Namespace `Domain/` **belum** dibuat. Modul ditambahkan bertahap sesuai roadmap; jangan membuat folder kosong lebih awal.

> Business logic yang sudah ada diletakkan di `app/Services` (mis. `DestinationService` untuk alur moderasi). `app/Domain` disiapkan untuk pemecahan per-modul bila kompleksitas meningkat.

## 4. Strategi Database

- **MySQL 8** (InnoDB, utf8mb4). Dev: `loka-nusa`. Test: `loka-nusa-test`.
- Migrasi terurut waktu; **tidak ada migrasi destruktif** tanpa persetujuan.
- FK dengan `constrained()`; index berdasarkan pola query (slug, koordinat bbox, status publikasi, tanggal).
- Kolom standar tiap tabel domain: `id`, timestamps; soft deletes hanya bila ada kebutuhan nyata (moderasi/audit).
- Snapshot teks (mis. nama wilayah) dipakai bila referensi bisa berubah & harus stabil pada dokumentasi historis.
- Enum: gunakan PHP backed enum + kolom string (bukan MySQL ENUM) agar mudah diperluas.

### Data referensi wilayah (`regions`)
- Wilayah bertingkat self-referencing: `provinsi → kabupaten_kota → kecamatan → desa_kelurahan`.
- `code` **unik** (kode BPS/wilayah.id) → dasar upsert idempoten.
- Data provinsi + kab/kota + kecamatan (± 7.800 baris) dibundel di `database/data/wilayah.json`
  (offline, tanpa dependency) dan di-seed via `RegionSeeder`. Dump `database/data/wilayah.sql`
  tersedia untuk impor via phpMyAdmin. Perbarui via `wilayah:import` + `wilayah:export`.
- Fokus aplikasi: **satu kabupaten/kota** (ditetapkan Super Admin; `AppSetting.focus_region_id`). Provinsi tetap tersedia sebagai induk.
- **Wilayah adalah data referensi — TIDAK diekspos sebagai halaman CRUD admin.**
  Tidak ada `RegionResource`. Data diisi sekali via seeder/impor, lalu dipakai read-only
  (mis. dropdown berjenjang di form lain). Alasan: data besar & stabil; tidak membebani panel.

### Modul konten (Step 7b–10)
- `destinations` — destinasi wisata (manager_id, kategori, wilayah, koordinat, jam, tiket, fasilitas, galeri).
- `umkm_businesses` + `umkm_products` — UMKM; **kuliner/restoran** = UMKM dengan `business_type=kuliner`.
- `accommodations` — penginapan (hotel/homestay/dll), `manager_id`.
- `culture_articles` — **LokaCulture** artikel budaya (`CultureType`, `author_id`, sampul, wilayah opsional).
- Kolom `village_id` (FK `regions`) menyimpan desa/kelurahan; desa diimpor per kabupaten fokus via `wilayah:import-villages` (dataset wilayah bawaan hanya sampai kecamatan).
- Status moderasi generik: `ContentStatus` (draft/pending/published/rejected/archived).
- Moderasi: trait `App\Services\Concerns\ModeratesContent`; aksi Filament: `App\Filament\Support\ModerationActions`.

## 5. Role & Permission

- **RBAC terpusat berbasis enum** (bukan tabel/Spatie): `App\Enums\UserRole` + `App\Enums\Permission`.
  - Kolom `users.role` (string, indexed, default `wisatawan`).
  - `UserRole::permissions()` memetakan peran → permission; `UserRole::can()` untuk cek.
  - `AppServiceProvider` mendaftarkan satu **Gate** per permission (`manage-users`, `manage-regions`, `manage-settings`, `manage-destinations`, `manage-umkm`, `manage-accommodations`, `manage-culture`, `moderate-content`) + `Gate::policy` untuk `Destination`, `UmkmBusiness`, `UmkmProduct`, `Accommodation`, `CultureArticle`.
  - `UserPolicy` untuk operasi model `User` (mis. larangan hapus diri sendiri & hapus Super Admin oleh non-Super Admin); `DestinationPolicy`, `UmkmBusinessPolicy`, `UmkmProductPolicy`, `AccommodationPolicy`, `CultureArticlePolicy` untuk kepemilikan & moderasi.
- Role awal: `super_admin`, `admin_pemerintah`, `admin_wilayah`, `pengelola_destinasi`, `pemilik_umkm`, `pengelola_penginapan`, `kontributor_budaya`, `wisatawan`.
- **Ownership**: `UserRole::managesAllContent()` (super/admin pemerintah/admin wilayah) boleh kelola semua; peran pengelola hanya miliknya (`owner_id`/`manager_id`).
- `canAccessPanel()`: semua peran kecuali `wisatawan`.
- Aturan: otorisasi di server (Gate/Policy) + `canViewAny`/`canX` di Filament. Menyembunyikan tombol bukan pengganti otorisasi.
- Alasan memilih enum: jumlah peran/permission terbatas & stabil; menghindari tabel tambahan dan query ekstra. Bila kelak butuh permission dinamis per-user, migrasi ke tabel dapat dilakukan tanpa mengubah pemakaian `Gate`.

## 6. PWA

- Manifest + service worker (workbox manual sederhana atau vite-plugin-pwa bila disetujui di Step 16).
- Cache **hanya konten publik**; data privat/pribadi tidak boleh masuk cache.
- Halaman offline; strategi cache (network-first untuk data, cache-first untuk aset statis).
- Pengujian installability + pemeriksaan kebocoran cache.

## 7. Standar Keamanan

- Validasi server-side di semua form & API.
- Otorisasi tiap operasi sensitif; cek kepemilikan lintas akun.
- Proteksi CSRF/XSS/SQLi/mass-assignment (fillable eksplisit, form request).
- Rate limiting pada endpoint publik (review, QR scan, login).
- Upload file: validasi tipe/ukuran, simpan di disk `public` terkontrol, sanitasi nama.
- Secret hanya di `.env`; jangan masuk log/dokumen.
- Audit log untuk perubahan sensitif (Step 21 area; tambahkan sedini yang relevan).
- Webhook payment: verifikasi signature + idempotency (Step 20).

## 8. Konvensi Penamaan

- Model singular (`Destination`), tabel plural snake_case (`destinations`).
- Service: `<Modul>Service` / action class `<Verb><Entity>Action`.
- Enum suffix `Status`/`Type`/`Category`.
- Test: `tests/Feature/<Modul>`, `tests/Unit/<Modul>`. Nama method deskriptif berbahasa Indonesia/Inggris konsisten.
- Route publik: `/{resource}`; admin Filament: `/admin`.

## 9. Dependency Antarmodul

```
Region ─▶ Destination, Umkm, Culture, Accommodation, Event
Identity ─▶ semua modul (aktor & otorisasi)
Destination ─▶ Review, Favorites, QR, Itinerary, Analytics, Booking
Umkm ─▶ Product Catalog, Booking
Tourist ─▶ Favorites, Review, Itinerary, Notification
Booking ─▶ Payment, Notification, Analytics
```

Aturan: modul hilir bergantung hulu; hindari ketergantungan sirkular. Komunikasi lintas modul via service, bukan langsung antar Resource.

## 10. Testing

- Test DB: MySQL `loka-nusa-test` (+ `RefreshDatabase`).
- Setiap step: tes CRUD, otorisasi lintas user, validasi.
- Regression test untuk fitur yang sudah disetujui.
- Tidak mengklaim bebas celah hanya karena test lulus; laporkan cakupan pengujian.
