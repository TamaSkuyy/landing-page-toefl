# Troubleshooting

Mulai dari pesan error paling awal. Jangan mengubah banyak hal sekaligus; lakukan satu perbaikan, bersihkan cache bila perlu, lalu ulangi langkah yang gagal.

## Halaman tidak dapat dibuka

1. Pastikan `composer dev` atau `php artisan serve` masih berjalan.
2. Periksa `APP_URL` dan port terminal.
3. Jalankan `php artisan optimize:clear`.
4. Baca error terbaru di `storage/logs/laravel.log`.

Pada production, pastikan document root menuju folder `public`, bukan root repository.

## Error database

Gejala umum: `Connection refused`, `Access denied`, atau `Unknown database`.

- Pastikan service MySQL/MariaDB aktif.
- Cocokkan `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`.
- Pastikan database sudah dibuat.
- Setelah `.env` berubah, jalankan `php artisan optimize:clear`.
- Jalankan `php artisan migrate:status` untuk melihat status migration.

Jangan menjalankan `migrate:fresh` pada production karena command tersebut menghapus seluruh tabel.

## Tampilan frontend lama atau kosong

- Development: pastikan `npm run dev` aktif.
- Production: jalankan `npm ci && npm run build`.
- Hard refresh browser.
- Periksa error console browser.
- Pastikan folder `public/build` dan `public/build/manifest.json` tersedia.

## Perubahan `.env` tidak terbaca

Laravel mungkin masih menggunakan cache konfigurasi:

```bash
php artisan optimize:clear
php artisan config:cache
```

## Route tidak sesuai mode

Route FORM hanya didaftarkan pada `PROJECT_MODE=form`.

```bash
php artisan optimize:clear
php artisan route:list
```

Periksa nilai `PROJECT_MODE` dan `PAYMENT_MODE`, lalu cache ulang pada production.

## Dashboard kosong

- Pastikan `ANALYTICS_ENABLED=true`.
- Buka landing page, bukan hanya dashboard.
- Pastikan tabel `user_analytics` dan `analytics_sessions` tersedia.
- Pilih rentang tanggal yang mencakup hari ini.
- Untuk data contoh local, jalankan `php artisan db:seed --class=AnalyticsDemoSeeder`.
- A/B Labs menggunakan cache satu menit; klik Refresh Data jika diperlukan.

## Engagement dan bounce tidak berubah

- Pastikan heartbeat request ke `/analytics/heartbeat` berhasil.
- Tunggu melewati `ANALYTICS_ENGAGEMENT_THRESHOLD` dengan tab tetap visible.
- Scroll melewati `ANALYTICS_SCROLL_BOUNCE_THRESHOLD`, bukan tepat pada batas.
- Pastikan JavaScript tidak error.

Engagement adalah negasi bounce. Klik CTA funnel juga membuat session menjadi engaged.

## CTA tidak tercatat

- Pastikan menggunakan `TrackedCTA`.
- Cocokkan action dengan mode project.
- Gunakan zone/action yang terdaftar pada [Kontrak Event](02-analytics-events.md).
- Periksa Network tab untuk request `/analytics/track`.
- Ad blocker dapat memblokir layanan eksternal, tetapi event internal seharusnya tetap masuk.

## Form gagal submit

- Pastikan `PROJECT_MODE=form`.
- `name` dan `phone` wajib diisi.
- Pastikan setiap input memiliki atribut `name`.
- Periksa response POST `/lead` pada Network tab.
- Payment external membutuhkan `EXTERNAL_PAYMENT_URL`.
- Payment internal membutuhkan `PRODUCT_PRICE`, merchant code, dan API key.

## Meta CAPI tidak terkirim

Meta CAPI dikirim langsung dan tidak memerlukan queue worker. Periksa `META_PIXEL_ID`, `META_ACCESS_TOKEN`, `META_CAPI_ENABLED`, cache konfigurasi, dan koneksi HTTPS keluar server. Gunakan Test Events; jangan menyalin token ke log atau chat publik.

Untuk melihat status respons server sementara:

```dotenv
META_CAPI_LOG_ENABLED=true
```

Jalankan `php artisan optimize:clear`, ulangi satu event, lalu periksa tabel `meta_capi_logs`. Kembalikan nilainya ke `false` setelah selesai agar tabel audit tidak terus bertambah.

## GTM/GA4 tercatat dua kali

Jika `GTM_CONTAINER_ID` terisi, kelola GA4 melalui GTM. Jangan memasang GA4 kedua dari source landing page atau plugin lain. Gunakan Tag Assistant untuk melihat tag yang aktif dua kali.

## Duitku order tetap pending

- Pastikan callback URL dapat diakses melalui HTTPS publik.
- Cocokkan `APP_URL` dengan URL saat invoice dibuat.
- Periksa log callback dan dashboard merchant.
- Pastikan nominal callback sama dengan amount order.
- Jika memakai tunnel local, buat invoice baru setiap URL tunnel berubah.

## Scheduler atau arsip tidak berjalan

- Pastikan cron memanggil `php artisan schedule:run` setiap menit.
- Jalankan `php artisan schedule:list`.
- Uji manual dengan `php artisan analytics:archive`.
- Periksa timezone aplikasi dan timezone server jika jam eksekusi berbeda.

## Tidak bisa login admin

Buat atau perbarui admin:

```bash
php artisan pbm:create-admin
```

Pastikan database yang dipakai command sama dengan database website. Registrasi publik memang tidak tersedia.

## Kapan meminta bantuan

Sertakan informasi berikut tanpa secret:

- langkah yang dilakukan;
- pesan error lengkap;
- environment local/staging/production;
- versi PHP dan Node.js;
- mode project/payment;
- potongan log yang relevan setelah menghapus credential dan PII.
