# Membaca Dashboard Analytics dan A/B Labs

Dashboard hanya dapat diakses user dengan role admin. Buat admin menggunakan `php artisan pbm:create-admin`, lalu login melalui `/login`.

## Analytics (`/admin`)

Dashboard Analytics menjawab: berapa traffic yang masuk, seberapa banyak yang berinteraksi, di tahap mana pengunjung keluar, dan berapa conversion yang dihasilkan.

### Kartu metrik umum

| Metrik | Cara membaca |
|---|---|
| Total Visits | Jumlah session unik dengan Visit. Page views dapat lebih besar jika halaman dibuka beberapa kali. |
| Engagement Rate | Persentase visit yang tidak bounce. |
| Bounce Rate | Persentase visit tanpa sinyal engagement. Engagement + Bounce selalu 100%. |
| Intent Rate | Persentase visit yang melakukan CTA non-conversion. |

### Kartu mode CTWA

- WhatsApp Lead: session yang membuka WhatsApp.
- Direct Checkout: session yang menuju checkout eksternal.
- Total Lead: gabungan unik WhatsApp dan Direct Checkout.
- Lead CR: Total Lead dibagi Visit.

### Kartu mode FORM

- Form Start Rate: session yang mulai mengisi form dibagi Visit.
- Lead Rate: session dengan form tersimpan dibagi Visit.
- Lead to Payment: payment sukses dibagi Lead, hanya mode internal.
- Total Revenue dan RPV: hanya mode internal.

### Chart dan panel

- **Funnel Trends:** perubahan jumlah setiap tahap per hari.
- **Referral Sources:** asal traffic berdasarkan referrer.
- **Conversion Funnel:** funnel ketat; satu tahap hanya menghitung session yang melewati tahap sebelumnya.
- **Key Insights:** referral teratas, outcome utama, Lead CR, RPV, atau retensi data.
- **Export CSV:** event mentah pada rentang aktif untuk analisis tambahan.

Pada CTWA, WhatsApp Lead dan Direct Checkout adalah dua cabang sejajar setelah Intent, bukan urutan yang harus dilalui bersamaan.

## A/B Labs (`/admin/labs`)

A/B Labs membandingkan performa berdasarkan landing source. Landing source adalah path pertama pada session dan biasanya mewakili varian, misalnya `/variant-a` dan `/variant-b`.

### Filter

- Date range: 3, 5, 7, 14, 30, 90 hari, atau rentang custom maksimal retensi.
- Referral: membatasi data berdasarkan asal traffic.
- Landing page: memilih satu atau beberapa varian.
- Refresh Data: menghapus cache laporan satu menit dan menghitung ulang.

### Bagian laporan

| Bagian | Kegunaan |
|---|---|
| Performance Matrix | Membandingkan metrik utama setiap varian; dapat diurutkan dan dipaginasi. |
| Split Funnel | Membandingkan drop-off tahap funnel antarvarian. |
| Device Performance | Memeriksa performa desktop, mobile, dan tablet. |
| CTA Performance | Menilai kombinasi zone dan action CTA. |
| Personas | Mengelompokkan Bouncers, Skimmers, Deep Readers, dan Casuals dari perilaku session. |
| Scroll Heatmap | Menunjukkan persentase session yang mencapai 25/50/75/90%. |
| Section Heatmap | Menunjukkan jangkauan dan drop-off antarsection. |
| Behavior Analysis | Membandingkan scroll dan dwell time lead vs non-lead. |

**Dwell time** adalah perkiraan durasi aktif session. **Drop-off** adalah penurunan jumlah session dari satu tahap ke tahap berikutnya.

## Memilih pemenang A/B

Dashboard menandai varian eligible setelah mencapai `ANALYTICS_MINIMUM_WINNER_VISITS`. Default-nya 30 visit.

Primary metric:

- CTWA: Total Lead.
- FORM: Lead.

Jangan mengambil keputusan hanya dari label winner. Periksa juga:

1. Apakah jumlah visit tiap varian cukup dan relatif seimbang?
2. Apakah sumber traffic serta rentang waktunya setara?
3. Apakah funnel tahap sebelumnya tidak mengalami penurunan ekstrem?
4. Apakah hasil konsisten pada mobile dan desktop?
5. Apakah kualitas lead dan hasil komersial benar-benar lebih baik?

Minimum visit adalah guardrail operasional, bukan uji signifikansi statistik. Untuk keputusan bernilai besar, lakukan analisis statistik tambahan.

## Retensi data

Dashboard hanya membaca tabel aktif sampai `ANALYTICS_RETENTION_DAYS`, maksimal 90 hari. Data yang lebih lama dipindahkan ke tabel arsip dan tidak muncul pada chart dashboard. Lead serta order tidak ikut proses arsip analytics.
