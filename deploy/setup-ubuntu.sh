#!/usr/bin/env bash
#
# Provisioning VPS Ubuntu untuk landing page TOEFL ITP (Laravel + Inertia + nginx + MySQL).
#
#   sudo bash deploy/setup-ubuntu.sh                    # deteksi domain dari argumen/opsional
#   sudo bash deploy/setup-ubuntu.sh toefl.example.com  # sekaligus menulis nginx server block
#
# Skrip ini idempotent: aman dijalankan ulang. Yang dilakukan:
#   1. paket dasar: nginx, MySQL, PHP + ekstensi, Composer, Node 22, certbot, ufw
#   2. database MySQL khusus aplikasi + kredensialnya (disimpan sekali di /root)
#   3. konfigurasi PHP-FPM (upload size, memory, opcache)
#   4. folder aplikasi + kepemilikan + cron scheduler Laravel
#   5. nginx server block (kalau domain diberikan) + firewall
#
# Yang TIDAK dilakukan (sengaja): clone repo, isi .env, migrate, dan SSL.
# Semuanya ada di docs/14-vps-nginx.md supaya Anda bisa memeriksa tiap langkah.

set -euo pipefail

# Kalau ada perintah yang gagal, tampilkan barisnya supaya tidak "berhenti diam-diam".
trap 'echo "[X] Gagal di baris ${LINENO}: ${BASH_COMMAND}" >&2' ERR

APP_DIR="${APP_DIR:-/var/www/landing-page-toefl}"
DB_NAME="${DB_NAME:-landing_page_toefl}"
DB_USER="${DB_USER:-toefl_app}"
CREDENTIALS_FILE="${CREDENTIALS_FILE:-/root/pbm-credentials.txt}"
# Subdomain (atau domain) tujuan, mis. toefl.domainmu.com
DOMAIN="${1:-${DOMAIN:-}}"
# Set WITH_WWW=1 hanya kalau ingin sekaligus melayani www.<domain>.
# Untuk SUBDOMAIN biarkan 0: www.toefl.domainmu.com biasanya tidak punya DNS record dan
# membuat certbot gagal memvalidasi.
WITH_WWW="${WITH_WWW:-0}"
# Diisi di bagian nginx; default dipakai kalau domain belum diberikan.
CERTBOT_ARGS="${CERTBOT_ARGS:-}"

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m[!] %s\033[0m\n' "$*"; }

if [ "$(id -u)" -ne 0 ]; then
    echo "Jalankan dengan sudo: sudo bash $0 [domain]" >&2
    exit 1
fi

# User pemilik aplikasi = user yang memanggil sudo (bukan root).
APP_USER="${SUDO_USER:-$(logname 2>/dev/null || echo root)}"

if ! grep -qi '^ID=ubuntu' /etc/os-release; then
    warn "Skrip ini ditulis untuk Ubuntu (terdeteksi: $(. /etc/os-release && echo "$PRETTY_NAME"))."
    warn "Perintah apt/nginx di bawah mungkin perlu penyesuaian."
fi

export DEBIAN_FRONTEND=noninteractive

# needrestart bisa menampilkan prompt interaktif setelah apt ("Which services should be
# restarted?") yang membuat skrip tampak menggantung. Mode "a" = restart otomatis.
export NEEDRESTART_MODE=a

if [ -f /etc/needrestart/needrestart.conf ]; then
    sed -i "s/^#\?\$nrconf{restart}.*/\$nrconf{restart} = 'a';/" /etc/needrestart/needrestart.conf || true
fi

# ---------------------------------------------------------------------------
# 1. Paket dasar
# ---------------------------------------------------------------------------
log "Memasang paket dasar (nginx, mysql, php, composer, node, certbot)"

apt-get update -qq

BASE_PACKAGES=(
    nginx mysql-server git unzip curl ca-certificates ufw
    certbot python3-certbot-nginx
)
PHP_PACKAGES=(
    php-fpm php-cli php-mysql php-mbstring php-xml
    php-curl php-zip php-gd php-bcmath php-intl
)

# Nama paket PHP berbeda antar rilis Ubuntu (mis. meta-package php-opcache tidak ada di 26.04).
# Karena itu: coba batch dulu, kalau ada yang tidak tersedia ulangi satu per satu supaya paket
# yang tersedia tetap terpasang dan yang hilang hanya diperingatkan.
if ! apt-get install -y -qq "${BASE_PACKAGES[@]}" "${PHP_PACKAGES[@]}" >/dev/null 2>&1; then
    warn "Pemasangan batch tidak sepenuhnya berhasil — mencoba satu per satu."

    for package in "${BASE_PACKAGES[@]}" "${PHP_PACKAGES[@]}"; do
        apt-get install -y -qq "${package}" >/dev/null 2>&1 || warn "  tidak tersedia, dilewati: ${package}"
    done
fi

for binary in nginx php mysql; do
    if ! command -v "${binary}" >/dev/null 2>&1; then
        echo "Paket wajib '${binary}' tidak terpasang. Perbaiki manual lalu jalankan skrip ini lagi." >&2
        exit 1
    fi
done

PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || true)"

if [ -z "${PHP_VER}" ]; then
    echo "PHP tidak terpasang dengan benar." >&2
    exit 1
fi

log "PHP terdeteksi: ${PHP_VER}"

# opcache: nama meta-package "php-opcache" tidak ada di semua rilis (Ubuntu 26.04 hanya
# menyediakan php<versi>-opcache). Dipasang per versi dan tidak fatal kalau tidak tersedia —
# aplikasi tetap jalan, hanya tanpa percepatan bytecode.
if ! php -m | grep -qi 'Zend OPcache'; then
    apt-get install -y -qq "php${PHP_VER}-opcache" >/dev/null 2>&1 \
        || warn "php${PHP_VER}-opcache tidak tersedia — dilewati (tanpa opcache)."
fi

# Composer resmi (versi apt sering tertinggal).
if ! command -v composer >/dev/null 2>&1; then
    log "Memasang Composer"

    if curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php \
        && php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer; then
        log "Composer terpasang."
    else
        warn "Composer gagal dipasang — pasang manual, lalu jalankan skrip ini lagi."
    fi

    rm -f /tmp/composer-setup.php
fi

# Node 22 — package.json mensyaratkan >= 22.13.
if ! command -v node >/dev/null 2>&1 || [ "$(node -p 'process.versions.node.split(".")[0]' 2>/dev/null || echo 0)" -lt 22 ]; then
    log "Memasang Node.js 22 (dipakai saat build aset)"

    if curl -fsSL https://deb.nodesource.com/setup_22.x | bash - >/dev/null 2>&1 \
        && apt-get install -y -qq nodejs >/dev/null; then
        log "Node.js terpasang: $(node -v)"
    else
        warn "Node.js gagal dipasang — build aset harus dijalankan manual di laptop lalu diunggah."
    fi
fi

log "Versi terpasang"
printf '  nginx      : %s\n' "$(nginx -v 2>&1 | cut -d/ -f2)"
printf '  php        : %s\n' "$(php -r 'echo PHP_VERSION;')"
printf '  composer   : %s\n' "$(composer --version 2>/dev/null | cut -d' ' -f3 || echo 'TIDAK TERPASANG')"
printf '  node       : %s\n' "$(node -v 2>/dev/null || echo 'TIDAK TERPASANG')"
printf '  mysql      : %s\n' "$(mysql --version 2>/dev/null | awk '{print $5}' | tr -d ',' || echo 'TIDAK TERPASANG')"
printf '  opcache    : %s\n' "$(php -m 2>/dev/null | grep -qi 'Zend OPcache' && echo aktif || echo 'tidak aktif (opsional)')"

# ---------------------------------------------------------------------------
# 2. Database + kredensial
# ---------------------------------------------------------------------------
log "Menyiapkan database MySQL"

if [ -f "${CREDENTIALS_FILE}" ]; then
    DB_PASS="$(grep -m1 '^DB_PASSWORD=' "${CREDENTIALS_FILE}" | cut -d= -f2-)"
    warn "Kredensial lama dipakai ulang dari ${CREDENTIALS_FILE} (password tidak diubah)."
else
    DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-24)"
fi

mysql --protocol=socket <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

umask 077
cat > "${CREDENTIALS_FILE}" <<CREDS
# Kredensial production — JANGAN di-commit.
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}
CREDS

log "Kredensial database disimpan di ${CREDENTIALS_FILE} (chmod 600)"
cat "${CREDENTIALS_FILE}"

# ---------------------------------------------------------------------------
# 3. Konfigurasi PHP-FPM
# ---------------------------------------------------------------------------
log "Menulis konfigurasi PHP ${PHP_VER} (upload, memory, opcache)"

PHP_INI_DIR="/etc/php/${PHP_VER}"
cat > "${PHP_INI_DIR}/fpm/conf.d/99-pbm.ini" <<'INI'
; Konfigurasi khusus aplikasi (dibuat oleh deploy/setup-ubuntu.sh)
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 60

opcache.enable = 1
opcache.memory_consumption = 192
opcache.validate_timestamps = 1
opcache.revalidate_freq = 2
INI

cp "${PHP_INI_DIR}/fpm/conf.d/99-pbm.ini" "${PHP_INI_DIR}/cli/conf.d/99-pbm.ini"
systemctl restart "php${PHP_VER}-fpm"

# ---------------------------------------------------------------------------
# 4. Folder aplikasi, kepemilikan, cron
# ---------------------------------------------------------------------------
log "Menyiapkan folder aplikasi ${APP_DIR}"

mkdir -p "${APP_DIR}"
chown -R "${APP_USER}:www-data" "${APP_DIR}"
chmod 750 "${APP_DIR}"

for dir in storage storage/app storage/framework storage/framework/cache storage/framework/cache/data \
           storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; do
    mkdir -p "${APP_DIR}/${dir}"
done

chown -R "${APP_USER}:www-data" "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" 2>/dev/null || true
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" 2>/dev/null || true
find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" -type d -exec chmod g+s {} + 2>/dev/null || true

log "Menambahkan cron scheduler Laravel untuk user ${APP_USER}"
CRON_LINE="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
CURRENT_CRON="$(crontab -u "${APP_USER}" -l 2>/dev/null || true)"

if printf '%s\n' "${CURRENT_CRON}" | grep -Fq "artisan schedule:run"; then
    warn "Cron schedule:run sudah ada, dilewati."
else
    printf '%s\n%s\n' "${CURRENT_CRON}" "${CRON_LINE}" | sed '/^$/d' | crontab -u "${APP_USER}" -
    log "Cron ditambahkan. Scheduler menjalankan analytics:archive setiap hari 02:30."
fi

# ---------------------------------------------------------------------------
# 5. nginx + firewall
# ---------------------------------------------------------------------------
if [ -n "${DOMAIN}" ]; then
    SERVER_NAMES="${DOMAIN}"

    if [ "${WITH_WWW}" = "1" ]; then
        SERVER_NAMES="${DOMAIN} www.${DOMAIN}"
    fi

    # Untuk subdomain cukup satu -d; menambahkan www.<subdomain> akan gagal karena
    # record DNS-nya biasanya tidak ada.
    CERTBOT_ARGS="-d ${DOMAIN}"

    if [ "${WITH_WWW}" = "1" ]; then
        CERTBOT_ARGS="${CERTBOT_ARGS} -d www.${DOMAIN}"
    fi

    log "Menulis nginx server block untuk: ${SERVER_NAMES}"

    SITE_NAME="$(printf '%s' "${DOMAIN}" | tr '.' '-')"
    TARGET="/etc/nginx/sites-available/${SITE_NAME}"

    sed -e "s|__APP_PATH__|${APP_DIR}|g" \
        -e "s|__PHP_VER__|${PHP_VER}|g" \
        -e "s|__SERVER_NAME__|${SERVER_NAMES}|g" \
        "$(dirname "$0")/nginx-site.conf.template" > "${TARGET}"

    ln -sfn "${TARGET}" "/etc/nginx/sites-enabled/${SITE_NAME}"
    rm -f /etc/nginx/sites-enabled/default

    nginx -t && systemctl reload nginx
    log "nginx dimuat ulang."
else
    warn "Domain/subdomain belum diberikan — server block nginx belum dibuat."
    warn "Jalankan ulang: sudo bash $0 toefl.domainmu.com  (setelah DNS diarahkan)"
fi

log "Mengaktifkan firewall (SSH, 80, 443)"
ufw allow OpenSSH >/dev/null 2>&1 || true
ufw allow 'Nginx Full' >/dev/null 2>&1 || true
ufw --force enable >/dev/null

log "Ringkasan"
cat <<SUMMARY
  Aplikasi     : ${APP_DIR}  (pemilik: ${APP_USER}:www-data)
  PHP          : ${PHP_VER}   socket: /run/php/php${PHP_VER}-fpm.sock
  Database     : ${DB_NAME}  user: ${DB_USER}  (kredensial: ${CREDENTIALS_FILE})
  Domain       : ${SERVER_NAMES:-belum diset}
  Cron         : $(printf '%s' "${CRON_LINE}")

Langkah berikutnya (lihat docs/14-vps-nginx.md):
  1. Arahkan DNS: A record "${DOMAIN%%.*}" -> IP VPS ini, lalu cek: dig +short ${DOMAIN:-toefl.domainmu.com}
  2. Clone repo ke ${APP_DIR}, buat .env (APP_URL=https://${DOMAIN:-toefl.domainmu.com}), php artisan key:generate
  3. bash deploy/deploy.sh
  4. sudo certbot --nginx ${CERTBOT_ARGS:--d toefl.domainmu.com}
  5. php artisan pbm:create-admin --name="Demo Admin" --email=... --password=...
SUMMARY
