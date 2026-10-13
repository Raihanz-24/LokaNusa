#!/usr/bin/env bash
#
# LokaNusa — skrip deploy/update untuk server Linux.
#
# Jalankan dari folder proyek:
#   chmod +x scripts/deploy.sh
#   ./scripts/deploy.sh
#
# Skrip ini WAJIB dijalankan setiap kali kode baru masuk (git pull).
# Ubah variabel di bawah bila perlu (mis. cabang Git, apakah npm ci dipakai).
#
# Catatan:
#  - Aset publik (public/build) & Filament (public/css|js|fonts/filament) TIDAK
#    ikut Git, jadi dibangun ulang di sini.
#  - Skrip TIDAK menjalankan "db:seed" otomatis agar data tidak tertimpa.

set -euo pipefail

BRANCH="${BRANCH:-main}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP="${PHP:-php}"

cd "$APP_DIR"

echo "==> LokaNusa deploy @ $(date '+%Y-%m-%d %H:%M:%S')"
echo "==> Direktori: $APP_DIR (cabang: $BRANCH)"

echo "==> [1/8] Mode maintenance"
"$PHP" artisan down --render="errors::503" || true

echo "==> [2/8] Ambil kode terbaru (git pull)"
if [ -d .git ]; then
    git pull origin "$BRANCH"
else
    echo "    (bukan repo git — lewati git pull; pastikan kode sudah diunggah)"
fi

echo "==> [3/8] Dependency PHP (production)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> [4/8] Migrasi database"
"$PHP" artisan migrate --force

echo "==> [5/8] Bangun aset front-end"
if [ -f package-lock.json ]; then
    npm ci --include=dev
else
    npm install --include=dev
fi
npm run build

echo "==> [6/8] Aset panel admin (Filament)"
"$PHP" artisan filament:assets

echo "==> [7/8] Storage link + bersihkan cache lama"
"$PHP" artisan storage:link || true
"$PHP" artisan optimize:clear

echo "==> [8/8] Optimasi produksi + keluar dari maintenance"
"$PHP" artisan optimize
"$PHP" artisan up

echo "==> Selesai. Cek kesehatan:"
echo "    $PHP artisan about"
echo "    $PHP artisan migrate:status"
