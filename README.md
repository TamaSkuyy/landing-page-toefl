# PBM Landing Page Boilerplate

Boilerplate ini adalah fondasi siap pakai untuk membuat landing page dengan Laravel, Inertia, React, analytics internal, dashboard A/B testing, dan integrasi marketing. **Boilerplate** berarti project dasar yang dapat disalin dan disesuaikan untuk klien baru tanpa membangun sistem pendukung dari awal.

Panduan ini berdiri sendiri. Developer tidak perlu mengetahui project lain atau proses pembuatan repository ini untuk mulai menggunakannya.

## Apa yang tersedia?

- Landing page React yang dirender melalui Inertia.
- Dua mode konversi: WhatsApp/checkout eksternal (`ctwa`) atau formulir (`form`).
- Tracking visit, engagement, bounce, intent, scroll, section, lead, dan payment.
- Dashboard Analytics di `/admin`.
- Dashboard perbandingan landing page atau A/B Labs di `/admin/labs`.
- Integrasi opsional Meta Pixel + Conversions API, Google Tag Manager, GA4, dan Microsoft Clarity.
- Penyimpanan lead, order, serta pembayaran internal melalui Duitku.
- Scheduler, pengarsipan analytics, test suite, dan workflow deployment GitHub Actions.

Semua integrasi pihak ketiga bersifat opsional. Landing page dan analytics internal tetap berjalan ketika ID integrasi dikosongkan.

## Istilah utama

- **CTA (Call to Action):** tombol atau tautan yang mengarahkan pengunjung melakukan tindakan, misalnya membuka WhatsApp atau menuju formulir.
- **CTWA (Click to WhatsApp):** landing page yang konversi utamanya terjadi ketika pengunjung membuka WhatsApp.
- **FORM:** landing page yang konversi utamanya terjadi ketika pengunjung mengirim formulir.
- **Event:** catatan satu aktivitas pengunjung, misalnya `visit`, `scroll`, atau `lead`.
- **Funnel:** urutan tahapan dari kunjungan sampai konversi.
- **Webhook/callback:** request dari layanan eksternal ke server untuk memberi tahu hasil proses, misalnya pembayaran berhasil.
- **Environment variable:** nilai konfigurasi di file `.env`, termasuk database, mode project, dan credential rahasia.

Istilah lainnya tersedia di [Glosarium](docs/00-glossary.md).

## Teknologi dan kebutuhan minimum

| Kebutuhan | Versi/fungsi |
|---|---|
| PHP | 8.4 atau lebih baru; menjalankan Laravel |
| Composer | 2.x; memasang dependency PHP |
| Node.js | 22.13 atau lebih baru; menjalankan tooling frontend |
| npm | Terpasang bersama Node.js; memasang dependency frontend |
| MySQL/MariaDB | Database aplikasi dan analytics |
| Git | Mengambil dan mengelola source code |

Untuk production, siapkan juga scheduler/cron, HTTPS, dan web server yang document root-nya mengarah ke folder `public`.

## 1. Pilih mode project

Pilih satu baris yang paling sesuai:

| Alur landing page | `PROJECT_MODE` | `PAYMENT_MODE` |
|---|---|---|
| CTA utama membuka WhatsApp | `ctwa` | `none` |
| CTA membuka checkout eksternal | `ctwa` | `none` |
| Form lalu halaman terima kasih | `form` | `none` |
| Form lalu halaman pembayaran milik klien | `form` | `external` |
| Form lalu pembayaran Duitku | `form` | `internal` |

Penjelasan lengkap ada di [Mode Project](docs/07-project-modes.md).

## 2. Instalasi lokal

```bash
git clone https://github.com/pbmagency/boilerplate-lp.git nama-project
cd nama-project
composer install
npm install
```

Salin file environment:

```bash
# macOS/Linux/Git Bash
cp .env.example .env

# Windows PowerShell
Copy-Item .env.example .env
```

Buat database kosong, lalu isi koneksi database di `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=root
DB_PASSWORD=
```

Isi identitas dan mode project:

```dotenv
APP_NAME="Nama Landing Page"
APP_URL=http://localhost:8000
CLIENT_ID=nama-klien
PROJECT_MODE=ctwa
PAYMENT_MODE=none
```

Selesaikan setup:

```bash
php artisan key:generate
php artisan migrate
php artisan pbm:create-admin
composer dev
```

Perintah `pbm:create-admin` akan meminta nama, email, dan password admin. Setelah server aktif, buka:

| URL | Fungsi |
|---|---|
| `http://localhost:8000` | Landing page |
| `http://localhost:8000/login` | Login admin |
| `http://localhost:8000/admin` | Dashboard Analytics |
| `http://localhost:8000/admin/labs` | Dashboard A/B Labs |
| `http://localhost:8000/admin/orders` | Order mode FORM |

Jika ingin mengisi dashboard dengan data contoh, jalankan `php artisan db:seed --class=AnalyticsDemoSeeder`. Jangan menjalankan seeder data contoh pada production.

## 3. Ganti halaman demo dengan desain klien

- Mode CTWA: edit `resources/js/pages/demo/ctwa.tsx`.
- Mode FORM: edit `resources/js/pages/demo/form.tsx`.
- Halaman terima kasih: edit `resources/js/pages/demo/thank-you.tsx`.

Pertahankan tiga aturan berikut agar analytics bekerja:

1. Beri setiap section penting atribut `id` yang unik dan stabil, misalnya `<section id="pricing">`.
2. Gunakan `TrackedCTA` untuk CTA, bukan elemen `<a>` biasa.
3. Gunakan `TrackedForm` untuk form lead.

Contoh CTA WhatsApp:

```tsx
<TrackedCTA
    zone="pricing"
    action="whatsapp"
    label="Chat Sekarang"
    href={whatsappUrl}
>
    Chat Sekarang
</TrackedCTA>
```

`zone` menjelaskan lokasi CTA. `action` menjelaskan tindakannya dan menentukan event. `label` adalah nama yang mudah dibaca saat analisis. Contoh form dan seluruh nilai yang diizinkan dijelaskan di [Memasang Tracking pada Frontend](docs/03-frontend-wiring.md).

## 4. Konfigurasi berdasarkan mode

CTWA minimum:

```dotenv
PROJECT_MODE=ctwa
WHATSAPP_NUMBER=628123456789
WHATSAPP_DEFAULT_MESSAGE="Halo, saya tertarik."
EXTERNAL_CHECKOUT_URL=
```

FORM tanpa payment:

```dotenv
PROJECT_MODE=form
PAYMENT_MODE=none
THANK_YOU_PATH=/terima-kasih
```

FORM dengan payment eksternal:

```dotenv
PROJECT_MODE=form
PAYMENT_MODE=external
EXTERNAL_PAYMENT_URL=https://contoh.com/payment
```

FORM dengan Duitku:

```dotenv
PROJECT_MODE=form
PAYMENT_MODE=internal
PRODUCT_NAME="Nama Produk"
PRODUCT_PRICE=199000
DUITKU_ENV=sandbox
DUITKU_MERCHANT_CODE=
DUITKU_API_KEY=
```

Setelah mengubah `.env` pada server yang menggunakan cache konfigurasi, jalankan:

```bash
php artisan optimize:clear
php artisan config:cache
```

## 5. Cara analytics bekerja

Tracking otomatis dimulai ketika halaman Inertia dimuat. Developer tidak perlu menulis kode tambahan untuk visit, durasi aktif, scroll, atau section view.

- Sesi dimulai sebagai bounce.
- Sesi menjadi engaged jika memenuhi minimal satu sinyal: aktif selama batas waktu, scroll melewati batas, atau melakukan tindakan funnel.
- Engagement adalah negasi bounce. Karena itu `Engagement Rate + Bounce Rate = 100%`.
- Lead FORM dan payment hanya ditulis oleh server agar tidak mudah dipalsukan.
- Total Lead CTWA menghitung gabungan sesi unik WhatsApp dan direct checkout; satu sesi yang melakukan keduanya tetap dihitung satu lead.

Lihat [Kontrak Event dan Metrik](docs/02-analytics-events.md) sebelum menambah event atau mengubah tracking.

## 6. Integrasi opsional

| Integrasi | Variabel utama | Panduan |
|---|---|---|
| Meta Pixel + CAPI | `META_PIXEL_ID`, `META_ACCESS_TOKEN` | [Meta](docs/04-meta-pixel-capi.md) |
| Google Tag Manager | `GTM_CONTAINER_ID` | [GTM/GA4/Clarity](docs/05-gtm-ga4-clarity.md) |
| GA4 langsung | `GA4_MEASUREMENT_ID` | [GTM/GA4/Clarity](docs/05-gtm-ga4-clarity.md) |
| Microsoft Clarity | `CLARITY_PROJECT_ID` | [GTM/GA4/Clarity](docs/05-gtm-ga4-clarity.md) |
| Duitku | `DUITKU_MERCHANT_CODE`, `DUITKU_API_KEY` | [Duitku](docs/06-duitku-payment.md) |

Jangan commit file `.env` atau menaruh credential di source code.

## 7. Menjalankan scheduler

Pada local development, `composer dev` menjalankan Laravel dan Vite bersamaan. Meta CAPI dikirim langsung oleh server ketika event diterima sehingga tidak memerlukan queue worker.

Pada production, cron harus memanggil scheduler Laravel setiap menit. **Cron** adalah penjadwal milik sistem operasi, sedangkan **scheduler Laravel** menentukan task aplikasi yang perlu dijalankan pada waktu tersebut.

```bash
php artisan schedule:work
```

Command `schedule:work` sesuai untuk local development. Pada production, gunakan konfigurasi cron dari [panduan deployment](docs/09-deployment.md). Tanpa scheduler, data analytics lama tidak dipindahkan ke tabel arsip.

## 8. Sebelum deploy

```bash
composer test
npm run lint:check
npm run format:check
npm run types:check
npm run build
```

Kemudian ikuti [Checklist QA](docs/11-qa-checklist.md) pada staging. **Staging** adalah server uji yang menyerupai production dan digunakan sebelum website dibuka untuk traffic nyata.

## Peta dokumentasi

1. [Glosarium](docs/00-glossary.md)
2. [Instalasi dan Project Pertama](docs/01-getting-started.md)
3. [Kontrak Event dan Metrik Analytics](docs/02-analytics-events.md)
4. [Memasang Tracking pada Frontend](docs/03-frontend-wiring.md)
5. [Meta Pixel dan Conversions API](docs/04-meta-pixel-capi.md)
6. [Google Tag Manager, GA4, dan Clarity](docs/05-gtm-ga4-clarity.md)
7. [Payment Duitku](docs/06-duitku-payment.md)
8. [Mode Project](docs/07-project-modes.md)
9. [Membaca Dashboard](docs/08-dashboard-guide.md)
10. [Deployment Production](docs/09-deployment.md)
11. [Troubleshooting](docs/10-troubleshooting.md)
12. [Checklist QA](docs/11-qa-checklist.md)
13. [Referensi Environment](docs/12-environment-reference.md)
14. [Landing Page TOEFL ITP Full Bright](docs/13-toefl-landing-page.md) — contoh wiring desain klien ke boilerplate

Jika baru pertama menggunakan repository ini, baca dokumen sesuai urutan di atas. Jika mengalami error, mulai dari [Troubleshooting](docs/10-troubleshooting.md).
