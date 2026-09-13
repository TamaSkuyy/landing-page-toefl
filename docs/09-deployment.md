# Deployment Production

Deployment adalah proses memindahkan aplikasi ke server yang melayani pengguna nyata. Contoh ini ditujukan untuk VPS dengan CyberPanel/OpenLiteSpeed, tetapi prinsipnya berlaku untuk web server lain.

## Arsitektur production minimum

- Domain dengan HTTPS.
- PHP 8.3+ dan extension Laravel.
- MySQL/MariaDB.
- Composer 2.
- Node.js 22.13+ hanya diperlukan pada tahap build.
- Web server dengan document root menuju folder `public`.
- Cron yang menjalankan scheduler setiap menit.

Misalnya source code berada di:

```text
/home/example.com/public_html/current
```

Document root harus:

```text
/home/example.com/public_html/current/public
```

Jangan arahkan document root ke root repository karena file `.env` dan source code dapat terekspos.

## 1. Siapkan source dan environment

Di server:

```bash
cd /home/example.com/public_html/current
cp .env.example .env
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan key:generate
```

Isi `.env` production:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
LOG_CHANNEL=daily
QUEUE_CONNECTION=sync
CACHE_STORE=database
SESSION_DRIVER=database
```

Isi juga database, mode project, serta credential integrasi. Lihat [Referensi Environment](12-environment-reference.md).

## 2. Build frontend

Jika build dilakukan di server:

```bash
npm ci
npm run build
```

`npm ci` memasang dependency persis dari `package-lock.json`. Hasil build berada di `public/build`.

Jika menggunakan GitHub Actions bawaan, build dilakukan oleh workflow dan hasilnya dikirim sebagai release artifact.

## 3. Siapkan Laravel

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan pbm:create-admin
```

Option `--force` mengizinkan migration berjalan pada production. Pastikan folder `storage` dan `bootstrap/cache` dapat ditulis oleh user web server.

## 4. Scheduler dengan cron

Tambahkan cron pada user aplikasi:

```cron
* * * * * cd /home/example.com/public_html/current && php artisan schedule:run >> /dev/null 2>&1
```

Cron memanggil scheduler setiap menit. Laravel menentukan task yang benar-benar dijalankan. Project ini menjadwalkan `analytics:archive` setiap hari pukul 02:30.

## 5. GitHub Actions

Workflow `.github/workflows/deploy.yml` berjalan saat branch `main` menerima push atau ketika dijalankan manual. Workflow melakukan validasi, test, build, membuat archive release, mengirimnya ke VPS, menjalankan migration, dan mengoptimalkan Laravel.

Buat GitHub Environment bernama `production`, lalu tambahkan secrets:

| Secret | Isi |
|---|---|
| `VPS_HOST` | Host/IP server |
| `VPS_PORT` | Port SSH, biasanya 22 |
| `VPS_USER` | User SSH untuk deployment |
| `VPS_SSH_KEY` | Private key SSH |
| `VPS_PROJECT_PATH` | Path absolut project di server |

File `.env` server tidak dikirim workflow dan tetap dipertahankan di server.

## 6. Update berikutnya

Urutan aman untuk deployment manual:

```bash
php artisan down
git pull origin main
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan up
```

Maintenance mode dari `artisan down` mencegah pengguna mengakses aplikasi saat file dan database belum sinkron.

## 7. Log rotation

Gunakan:

```dotenv
LOG_CHANNEL=daily
LOG_DAILY_DAYS=14
```

Laravel akan membuat file log harian di `storage/logs`. Pastikan folder tersebut dapat ditulis oleh user web server dan dipantau agar error production dapat ditemukan.

## 8. Smoke test setelah deploy

Smoke test adalah pemeriksaan singkat bahwa fungsi utama hidup:

1. Buka `/up` dan pastikan HTTP 200.
2. Buka landing page melalui HTTPS.
3. Login dan buka `/admin` serta `/admin/labs`.
4. Klik satu CTA dan pastikan event muncul.
5. Submit form pada mode FORM.
6. Uji callback sandbox jika menggunakan Duitku.
7. Periksa Meta Test Events/GTM Preview/Clarity jika diaktifkan.

Selesaikan [Checklist QA](11-qa-checklist.md) sebelum membuka traffic campaign.
