# Glosarium

Dokumen ini menjelaskan istilah yang digunakan di seluruh panduan. Istilah tetap ditulis dalam bentuk aslinya agar mudah dicocokkan dengan kode, dashboard, dan dokumentasi layanan pihak ketiga.

## Dasar aplikasi

| Istilah | Penjelasan |
|---|---|
| Boilerplate | Project dasar yang sudah berisi struktur dan fitur umum untuk digunakan kembali. |
| Backend | Bagian aplikasi yang berjalan di server, menangani database, validasi, autentikasi, dan integrasi eksternal. Di project ini backend menggunakan Laravel. |
| Frontend | Bagian aplikasi yang tampil dan berjalan di browser. Di project ini frontend menggunakan React dan TypeScript. |
| Laravel | Framework PHP yang menjadi fondasi backend. Framework adalah kerangka kerja yang menyediakan pola dan fitur standar aplikasi. |
| React | Library JavaScript untuk membangun antarmuka menggunakan komponen. Komponen adalah bagian UI yang dapat digunakan kembali. |
| TypeScript | JavaScript dengan pemeriksaan tipe data untuk mengurangi kesalahan saat development. |
| Inertia | Penghubung Laravel dan React. Laravel menentukan halaman serta datanya, React menampilkan UI tanpa memerlukan REST API terpisah untuk setiap halaman. |
| Vite | Tool untuk menjalankan development server frontend dan menghasilkan file frontend production. |
| Artisan | Command-line tool milik Laravel, digunakan melalui perintah `php artisan ...`. |
| Dependency | Package/library yang dibutuhkan project. Dependency PHP dipasang Composer; dependency frontend dipasang npm. |
| Migration | File yang mendefinisikan perubahan struktur database. `php artisan migrate` menerapkannya. |
| Seeder | Kode untuk mengisi database dengan data awal atau data contoh. |
| Environment variable | Nilai konfigurasi yang disimpan di `.env`, misalnya koneksi database dan API key. |
| Credential/secret | Data rahasia seperti password, token, dan API key. Jangan commit data ini ke Git. |
| Cache konfigurasi | Salinan konfigurasi Laravel yang dioptimalkan. Setelah `.env` berubah di production, cache perlu dibuat ulang. |

## Landing page dan conversion

| Istilah | Penjelasan |
|---|---|
| Landing page | Halaman yang dibuat untuk satu tujuan kampanye atau conversion tertentu. |
| CTA | Call to Action; tombol atau tautan yang meminta pengunjung melakukan tindakan. |
| CTWA | Click to WhatsApp; model landing page dengan WhatsApp sebagai tujuan conversion utama. |
| FORM | Model landing page yang menyimpan data pengunjung melalui formulir. |
| Conversion | Tindakan bernilai yang menjadi tujuan landing page, misalnya lead atau payment. |
| Funnel | Urutan tahapan perilaku dari visit hingga conversion. |
| Lead | Data calon pelanggan. Pada FORM, lead tercatat setelah server berhasil menyimpan form. |
| Direct Checkout | Pengunjung menuju halaman checkout eksternal tanpa melalui form internal. |
| Landing source | Path halaman pertama dalam sesi, misalnya `/hero-a`; digunakan sebagai identitas varian A/B. |
| Referral source/referrer | Halaman atau domain yang mengirim pengunjung ke landing page. |
| UTM | Parameter pada URL untuk mengidentifikasi sumber kampanye, misalnya `utm_source`, `utm_medium`, dan `utm_campaign`. |

## Analytics

| Istilah | Penjelasan |
|---|---|
| Event | Satu catatan aktivitas, misalnya visit, scroll, atau lead. |
| Taxonomy event | Daftar nama event resmi beserta arti dan aturan penggunaannya. |
| Session | Sekumpulan aktivitas dalam satu sesi browser Laravel. Digunakan untuk menghitung visit dan funnel unik. |
| Visitor ID | UUID pada cookie `pbm_vid` untuk mengenali browser tanpa menyimpan identitas personal. UUID adalah identifier acak yang sangat kecil kemungkinannya bertabrakan. |
| Event ID | Identifier unik untuk mencegah event yang sama diproses dua kali. |
| Deduplication | Proses menghapus hitungan ganda berdasarkan identifier yang sama. |
| Engagement | Visit yang tidak berstatus bounce. |
| Bounce | Visit yang tidak mencapai durasi aktif, batas scroll, atau tindakan funnel yang ditentukan. |
| Intent | Tindakan yang menunjukkan minat tetapi belum menjadi lead, misalnya klik anchor menuju pricing. |
| Scroll depth | Persentase kedalaman halaman yang telah dicapai pengunjung. |
| Section view | Catatan bahwa sebuah section sempat terlihat sesuai batas visibilitas. |
| CR | Conversion Rate; persentase jumlah conversion dibandingkan jumlah visit. |
| Lead CR | Total lead dibagi visit, lalu dikali 100%. |
| Sales CR | Payment berhasil dibagi visit, lalu dikali 100%. |
| RPV | Revenue per Visit; total revenue dibagi jumlah visit. |
| Attribution | Pengaitan event/conversion ke sumber, campaign, halaman, device, atau CTA. |
| Retention | Durasi data aktif disimpan sebelum dipindahkan ke tabel arsip. |
| Telemetry | Data teknis yang dikumpulkan untuk pengukuran penggunaan, seperti event dan ringkasan sesi. |

## A/B testing

| Istilah | Penjelasan |
|---|---|
| A/B test | Perbandingan dua atau lebih varian landing page untuk melihat varian yang berkinerja lebih baik. |
| Variant | Versi halaman yang dibandingkan. Di boilerplate ini varian diidentifikasi oleh `landing_source`. |
| Primary metric | Metrik utama untuk membandingkan varian: Total Lead pada CTWA dan Lead pada FORM. |
| Eligible | Varian yang telah mencapai minimum visit untuk ditandai sebagai kandidat pemenang. |
| Winner | Varian eligible dengan primary metric terbaik di dashboard. Label ini adalah petunjuk, bukan bukti statistik final. |

## Integrasi dan server

| Istilah | Penjelasan |
|---|---|
| API | Cara dua aplikasi berkomunikasi melalui request terstruktur. |
| API key/access token | Secret yang mengizinkan aplikasi mengakses API pihak ketiga. |
| Meta Pixel | Script browser untuk mengirim event ke Meta. |
| CAPI | Meta Conversions API; pengiriman event dari server ke Meta. |
| GTM | Google Tag Manager; container untuk mengelola tag/script marketing. |
| GA4 | Google Analytics 4. |
| Clarity | Microsoft Clarity; layanan recording dan heatmap perilaku pengguna. |
| Queue browser | Antrean singkat di memori browser untuk menggabungkan beberapa event analytics sebelum dikirim ke server. Ini tidak memerlukan proses worker di server. |
| Scheduler | Penjadwal perintah Laravel. Production memanggilnya melalui cron. |
| Cron | Penjadwal command pada Linux. |
| Webhook/callback | Request dari layanan eksternal menuju server untuk melaporkan perubahan status. |
| Idempotent | Aman dipanggil berulang karena hasil akhirnya tetap satu; callback payment berulang tidak membuat payment ganda. |
| Signature | Nilai verifikasi untuk memastikan callback berasal dari pihak yang memiliki secret. |
| Hash | Hasil transformasi satu arah untuk melindungi data. |
| PII | Personally Identifiable Information; data pribadi seperti email dan nomor telepon. |
| HTTPS | Koneksi website terenkripsi. Wajib digunakan pada production. |
| Staging | Server uji yang menyerupai production. |
| Production | Server yang melayani pengunjung sebenarnya. |
| Document root | Folder yang dibuka web server. Untuk Laravel harus mengarah ke folder `public`. |
| Rate limit/throttle | Batas jumlah request dalam periode tertentu untuk mengurangi spam dan penyalahgunaan. |
| CSRF | Perlindungan agar request perubahan data hanya berasal dari halaman yang sah. |
