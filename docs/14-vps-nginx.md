# Deployment VPS Ubuntu — Subdomain (nginx + PHP-FPM + MySQL)

Panduan ini untuk VPS Ubuntu dengan nginx dan MySQL lokal. Contoh yang dipakai: landing page
diletakkan di **subdomain** (mis. `toefl.domainmu.com`) supaya domain utama tetap bebas untuk
keperluan lain (portofolio/CV, dan sebagainya).

Arsitekturnya: `nginx (80/443) → php-fpm (socket) → Laravel (public/index.php) → MySQL lokal`.

Berkas bantu di repo:

| Berkas | Fungsi |
| --- | --- |
| `deploy/setup-ubuntu.sh` | Provisioning idempotent: nginx, MySQL, PHP + ekstensi, Composer, Node 22, certbot, ufw, database, cron, server block |
| `deploy/nginx-site.conf.template` | Template server block (docroot `public/`, cache aset, fastcgi ke socket PHP) |
| `deploy/deploy.sh` | Deploy/redeploy: pull, `composer install`, build aset, migrate, optimize, izin, reload service |
| `deploy/env.production.example` | Template `.env` production untuk mode CTWA |

## 0. Prasyarat

- VPS Ubuntu (24.04 / 26.04) dengan akses `sudo`. RAM **minimal 1 GB**, disarankan 2 GB karena
  `npm run build` cukup berat.
- Satu domain yang DNS-nya bisa Anda ubah (domain CV juga boleh — kita pakai subdomainnya).
- Akses repo dari VPS (deploy key SSH atau personal access token).

## 1. Arahkan DNS subdomain

Di panel DNS domain Anda, tambahkan **satu** record:

| Tipe | Nama | Nilai | TTL |
| --- | --- | --- | --- |
| A | `toefl` | IP VPS | Auto / 300 |

Nama record diisi **label subdomain saja** (`toefl`), bukan `toefl.domainmu.com` — panel DNS
umumnya menambahkan domainnya otomatis. Hasilnya: `toefl.domainmu.com → IP VPS`.

Verifikasi dari komputer Anda:

```bash
dig +short toefl.domainmu.com
```

Harus menampilkan IP VPS. Propagasi biasanya 5–60 menit. **Jangan** menambahkan record `www`
untuk subdomain — `www.toefl.domainmu.com` tidak diperlukan dan membuat certbot gagal validasi.

Selama DNS belum propagasi, situs masih bisa diuji lewat `http://IP_VPS`.

> **Pakai Cloudflare?** Saat menerbitkan sertifikat, set record ke **DNS only** (awan abu-abu) dulu
> supaya tantangan HTTP-01 Let's Encrypt bisa lewat. Sesudah sertifikat terbit, proxy boleh
> dinyalakan (awan oranye) — tapi set juga `TRUSTED_PROXIES=*` di `.env` supaya Laravel tahu
> request aslinya HTTPS, dan mode SSL Cloudflare di **Full (strict)**.

## 2. Clone repo di VPS

```bash
sudo mkdir -p /var/www
sudo chown "$USER:www-data" /var/www
git clone <URL_REPO> /var/www/landing-page-toefl
```

Untuk repo privat, pakai deploy key (`git@github.com:...`) atau token
(`https://<token>@github.com/...`).

## 3. Provisioning server (satu perintah)

```bash
sudo bash /var/www/landing-page-toefl/deploy/setup-ubuntu.sh toefl.domainmu.com
```

Skrip ini (aman dijalankan ulang):

1. memasang nginx, MySQL, PHP + ekstensi, Composer, Node 22, certbot, ufw;
2. membuat database `landing_page_toefl` + user `toefl_app`, kredensialnya ditulis ke
   **`/root/pbm-credentials.txt`** (chmod 600 — jangan di-commit);
3. menulis konfigurasi PHP-FPM (`upload_max_filesize`, `memory_limit`, opcache);
4. menyiapkan `/var/www/landing-page-toefl`, izin `storage/` + `bootstrap/cache`, dan cron
   `schedule:run` (scheduler menjalankan `analytics:archive` tiap hari 02:30);
5. menulis server block nginx dengan `server_name toefl.domainmu.com;` + firewall (SSH, 80, 443).

Skrip mendeteksi versi PHP yang tersedia (Ubuntu 26.04 bisa 8.4/8.5) dan menyesuaikan socket
php-fpm di server block. Ingin sekaligus melayani `www`? Jalankan dengan `WITH_WWW=1`:

```bash
sudo WITH_WWW=1 bash deploy/setup-ubuntu.sh domainmu.com
```

## 4. Isi `.env`

```bash
cd /var/www/landing-page-toefl
cp deploy/env.production.example .env
php artisan key:generate
```

Sesuaikan minimal: `APP_URL=https://toefl.domainmu.com`, `DB_PASSWORD` (dari
`/root/pbm-credentials.txt`), dan `WHATSAPP_NUMBER`. Catatan penting:

- `ASSET_URL=/` **jangan diubah** — HTML landing di-cache 7 hari, URL absolut akan rusak kalau
  situs dibuka lewat host/port lain.
- `SESSION_DRIVER=database` dan `CACHE_STORE=database` sudah diset (tidak perlu driver file).
- `INERTIA_SSR_ENABLED=false` karena VPS ini tidak menjalankan proses Node SSR; halaman dirender
  di klien dan tampilannya sama.

## 5. Deploy pertama

```bash
bash deploy/deploy.sh
```

Yang dijalankan: `composer install --no-dev`, `npm ci && npm run build`, `migrate --force`,
`storage:link`, `optimize`, perbaikan izin, lalu reload php-fpm & nginx. Aplikasi otomatis masuk
mode maintenance selama proses dan keluar lagi di akhir (termasuk kalau gagal).

Redeploy berikutnya cukup perintah yang sama.

## 6. Akun admin dashboard

```bash
php artisan pbm:create-admin \
  --name="Demo Admin" --email=demo@domainmu.com --password="Ganti-Dengan-Password-Kuat"
```

Sertakan kredensial ini saat submit supaya dashboard bisa diperiksa.

## 7. HTTPS untuk subdomain

Setelah `dig +short toefl.domainmu.com` menampilkan IP VPS:

```bash
sudo certbot --nginx -d toefl.domainmu.com
```

Satu `-d` saja untuk subdomain. Certbot mengubah server block menjadi HTTPS dan menambahkan
redirect dari HTTP; perpanjangan otomatis sudah aktif (systemd timer `certbot`). Kalau `APP_URL`
masih `http://`, ubah ke `https://` lalu jalankan `php artisan optimize`.

## 8. Verifikasi

```bash
curl -I https://toefl.domainmu.com                  # 200
curl -I https://toefl.domainmu.com/admin            # 302 ke /login
curl -s -D - -o /dev/null -H "Range: bytes=0-1023" \
  "https://toefl.domainmu.com/media/testimoni%20iyha.mp4" | head -1   # 206 Partial Content
```

Checklist lanjutan:

- buka halaman: toggle paket, accordion FAQ, dan play video berfungsi;
- login `/admin`, pastikan funnel dan event baru muncul setelah CTA diklik;
- `php artisan schedule:list` menampilkan `analytics:archive`;
- `tail -f storage/logs/laravel.log` bersih dari error.

## Troubleshooting

| Gejala | Penyebab & solusi |
| --- | --- |
| **502 Bad Gateway** | Socket php-fpm tidak cocok. Cek `ls /run/php/`, samakan dengan `fastcgi_pass` di server block, lalu `sudo systemctl reload nginx`. |
| **`Package 'php-opcache' has no installation candidate`** | Di Ubuntu 26.04 tidak ada meta-package `php-opcache`; yang tersedia hanya `php<versi>-opcache`. Skrip sudah menanganinya (opsional, dipasang per versi). Manual: `sudo apt-get install -y php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-opcache`. Kalau dilewati pun aplikasi tetap jalan. |
| **Paket PHP lain "no installation candidate"** | Skrip mencoba ulang satu per satu dan hanya memperingatkan; paket wajib (`nginx`, `php`, `mysql`) dicek di akhir. Pasang manual paket yang dilaporkan lalu jalankan skrip lagi. |
| **Subdomain tidak terbuka, IP jalan** | DNS belum propagasi atau `server_name` salah. Cek `dig +short toefl.domainmu.com` dan `nginx -T \| grep server_name`. |
| **Certbot gagal (challenge)** | Pastikan record DNS hanya `toefl` → IP VPS (tanpa `www`), port 80 terbuka, dan proxy Cloudflare dimatikan sementara. |
| **500 / blank** | Cek `storage/logs/laravel.log`. Umumnya izin: `chgrp -R www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache`. |
| **Aset 404 (`/build/...`)** | `npm run build` belum jalan, atau docroot bukan `public/`. Cek `root` di server block dan isi `public/build`. |
| **Halaman tampil tanpa CSS** | `APP_URL` salah atau `ASSET_URL` diubah dari `/`. Jalankan `php artisan optimize` setelah memperbaiki `.env`. |
| **Video tidak bisa diputar** | Harus `Accept-Ranges: bytes` + respons `206`. Kalau lewat PHP terasa lambat, aktifkan blok `location /media/` opsional di `deploy/nginx-site.conf.template` lalu reload nginx. |
| **Event analytics tidak masuk** | Pastikan `SESSION_DRIVER=database`, `CACHE_STORE=database`, migrasi sudah jalan, dan token CSRF di HTML sesuai sesi pengunjung (lihat catatan bug cache di [dokumen landing page](13-toefl-landing-page.md)). |
| **`npm ci` kehabisan memori** | Tambahkan swap — lihat [Menambah swap](#menambah-swap) di bawah. |

## Menambah swap

`npm ci && npm run build` adalah langkah paling berat di VPS kecil (RAM 1 GB bisa kehabisan
memori). **Cek dulu** — Ubuntu biasanya sudah menyiapkan `/swap.img`:

```bash
free -h
swapon --show
```

Kalau `swapon --show` sudah menampilkan baris, tidak perlu apa-apa lagi. Kalau kosong:

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

Verifikasi:

```bash
swapon --show      # harus ada /swapfile
free -h            # kolom Swap: 2,0Gi
```

Kalau muncul `swapon: /swapfile: swapon failed: Operation not permitted`, VPS-nya berbasis
container (OpenVZ/LXC) yang tidak bisa memakai swap. Bersihkan dan pakai jalur build di laptop:

```bash
# di VPS
sudo swapoff /swapfile 2>/dev/null; sudo rm -f /swapfile

# di laptop (Node >= 22.13)
npm ci && npm run build
scp -r public/build sekuyy@IP_VPS:/var/www/landing-page-toefl/public/

# di VPS: deploy tanpa build
bash deploy/deploy.sh --no-build
```
