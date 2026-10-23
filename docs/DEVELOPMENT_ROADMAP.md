# LokaNusa — Development Roadmap

Setiap nomor = satu checkpoint persetujuan. Jangan mulai step berikutnya tanpa approval.
Status: `PENDING` | `IN_PROGRESS` | `BLOCKED` | `READY_FOR_REVIEW` | `APPROVED`

| Step | Nama | Status | Catatan |
|---|---|---|---|
| 0 | Audit & perencanaan | APPROVED | MySQL dipilih (pgsql/sqlite tak tersedia) |
| 1 | Dokumen arsitektur & roadmap | APPROVED | `ARCHITECTURE.md` + roadmap ini |
| 2 | Inisialisasi & konfigurasi | APPROVED | MySQL dev+test, locale id, storage link, baseline test |
| 3 | Filament & authentication | APPROVED | UserResource + FilamentUser + command admin |
| 4 | Role, permission, authorization | APPROVED | Enum role+permission, Gate, UserPolicy, panel guard |
| 5 | Region & kategori | APPROVED | Region (data Indonesia, **tanpa halaman admin**) + DestinationCategory (dengan halaman admin) |
| 6 | Destination Management | APPROVED | CRUD + moderasi + galeri |
| 7 | PWA publik dasar | APPROVED | beranda, daftar/detail destinasi (Blade) |
| 7b | **Pondasi fokus kabupaten + admin per pengelola** | APPROVED | Pengaturan kabupaten fokus (super admin), dropdown kecamatan/desa, peta klik, modul UMKM & Kuliner + Penginapan (ownership per pengelola, moderasi generik) |
| 8 | Smart Tourism Map dasar + alamat | APPROVED | Peta jelajah publik `/peta` (destinasi/UMKM/penginapan); dropdown desa + auto-isi kecamatan/desa dari klik peta (Nominatim) |
| 9 | UMKM & produk | READY_FOR_REVIEW | tanpa checkout; sisi publik UMKM + produk |
| 10 | LokaCulture | READY_FOR_REVIEW | artikel budaya (admin + publik) |
| 11 | Accommodation | READY_FOR_REVIEW | tanpa booking engine; sisi publik penginapan + filter |
| 12 | Event & Calendar | READY_FOR_REVIEW | filter tanggal; `/event` + `/event/{slug}` |
| 13 | Akun wisatawan & engagement | READY_FOR_REVIEW | favorit, ulasan + moderasi (`/favorit`) |
| 14 | QR Smart Tourism | READY_FOR_REVIEW | QR semua jenis konten, `/q/{token}` catat pindai + redirect, statistik panel, papan QR cetak |
| 15 | Itinerary dasar | READY_FOR_REVIEW | tanpa AI; `/rencana` + butir per hari, tambah cepat dari detail |
| 16 | PWA installation & offline | READY_FOR_REVIEW | manifest, SW, cache aman |
| 17 | Dashboard & analytics dasar | READY_FOR_REVIEW | KPI data aktual |
| 18 | Notifications | READY_FOR_REVIEW | in-app (lonceng + /notifikasi) |
| 19 | AI Trip Planner | READY_FOR_REVIEW | AI nyata (Kryptonlab) + simpan ke Rencana |
| 20 | Booking & pembayaran | PENDING | payment gateway → approval |
| 21 | Tourism Intelligence | READY_FOR_REVIEW | analitik nyata + ekspor CSV |
| 22 | Production readiness | PENDING | audit keamanan & deploy checklist |

## Urutan Dependencies
Step 2 → 3 → 4 → 5 → 6 → 7 → 7b adalah jalur kritis. Step 8+ bergantung pada 6/7/7b.
Modul yang butuh Identity (3/4): hampir semua. Booking (20) bergantung 6, 9, 11.

## Perubahan Lingkup (Step 7b)
- Aplikasi difokuskan pada **satu kabupaten/kota** (ditetapkan Super Admin di `/admin/settings`).
  Wilayah lain tetap ada di data referensi; form dibatasi ke kecamatan dalam kabupaten fokus.
- **Kuliner/Restoran** = bagian modul UMKM dengan `business_type` (produk/kuliner/jasa).
- **Penginapan** = modul terpisah.
- **Ownership per akun**: pemilik UMKM / pengelola destinasi / pengelola penginapan hanya
  mengelola miliknya; admin (super/admin pemerintah/admin wilayah) kelola semua + moderasi.
- Modul yang dilipat/ditunda di luar fokus saat ini: Budaya, Event, dsb. tetap di roadmap
  tetapi dikerjakan setelah pondasi ini disetujui.
