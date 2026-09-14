#!/usr/bin/env bash
#
# Deploy / redeploy aplikasi dari dalam folder aplikasi.
#
#   bash deploy/deploy.sh                 # pull kode, install, build, migrate, optimize
#   bash deploy/deploy.sh --no-build      # lewati npm ci + npm run build
#   bash deploy/deploy.sh --no-pull       # pakai kode yang sudah ada (tanpa git pull)
#
# Jalankan sebagai user pemilik aplikasi (bukan root). Perintah artisan dijalankan sebagai
# user yang sama supaya berkas di storage/ tidak bentrok kepemilikannya dengan php-fpm.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
DO_PULL=1
DO_BUILD=1

for arg in "$@"; do
    case "${arg}" in
        --no-pull) DO_PULL=0 ;;
        --no-build) DO_BUILD=0 ;;
        *) echo "Opsi tidak dikenal: ${arg}" >&2; exit 1 ;;
    esac
done

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }

if [ "$(id -u)" -eq 0 ]; then
    echo "Jangan jalankan sebagai root — storage/ akan jadi milik root dan php-fpm (www-data) gagal menulis." >&2
    echo "Jalankan sebagai user aplikasi, lalu sudo hanya untuk reload service (skrip ini melakukannya sendiri)." >&2
    exit 1
fi

cd "${APP_DIR}"

if [ ! -f .env ]; then
    echo ".env belum ada. Salin dulu: cp deploy/env.production.example .env && php artisan key:generate" >&2
    exit 1
fi

log "Mode maintenance: aktif"
php artisan down --render="errors::503" >/dev/null 2>&1 || php artisan down >/dev/null 2>&1 || true

restore_app() {
    php artisan up >/dev/null 2>&1 || true
}
trap restore_app EXIT

if [ "${DO_PULL}" -eq 1 ] && [ -d .git ]; then
    log "Menarik kode terbaru (git pull --ff-only)"
    git pull --ff-only
fi

log "Memasang dependency PHP (tanpa dev)"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

if [ "${DO_BUILD}" -eq 1 ]; then
    if command -v npm >/dev/null 2>&1; then
        log "Build aset frontend (npm ci + npm run build)"
        npm ci --no-audit --no-fund
        npm run build
    else
        echo "npm tidak ditemukan — lewati build aset. Jalankan --no-build kalau memang tidak perlu." >&2
    fi
fi

log "Migrasi database"
php artisan migrate --force

log "Menyiapkan symlink storage & cache produksi"
php artisan storage:link >/dev/null 2>&1 || true
php artisan optimize

log "Merapikan izin storage/ dan bootstrap/cache/"
chgrp -R www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod g+s {} + 2>/dev/null || true

log "Memuat ulang php-fpm & nginx"
sudo systemctl reload "php${PHP_VER}-fpm"
sudo systemctl reload nginx

restore_app
trap - EXIT
log "Selesai. Cek https://$(grep -m1 '^APP_URL=' .env | cut -d= -f2- | sed 's|https\?://||')/  dan  /admin"
