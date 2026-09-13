# Meta Pixel dan Conversions API

Meta Pixel mengirim event dari browser. Meta Conversions API (CAPI) mengirim event dari server. Menggunakan keduanya membantu Meta menerima data ketika salah satu jalur terhalang, tetapi event yang sama harus memiliki Event ID yang sama agar Meta dapat melakukan deduplication.

## Kapan perlu diaktifkan?

Aktifkan ketika landing page digunakan untuk campaign Meta Ads dan akun klien memiliki Pixel serta access token CAPI. Jika tidak diperlukan, kosongkan credential; aplikasi tetap berjalan.

## Konfigurasi

Tambahkan ke `.env`:

```dotenv
META_PIXEL_ID=123456789
META_ACCESS_TOKEN=token-dari-events-manager
META_TEST_EVENT_CODE=
META_CAPI_ENABLED=true
META_CAPI_LOG_ENABLED=false
META_GRAPH_VERSION=v23.0
```

| Variabel | Fungsi |
|---|---|
| `META_PIXEL_ID` | ID dataset/pixel Meta. |
| `META_ACCESS_TOKEN` | Secret untuk mengirim server event. Jangan commit atau tampilkan di browser. |
| `META_TEST_EVENT_CODE` | Kode sementara dari menu Test Events. Kosongkan pada production. |
| `META_CAPI_ENABLED` | Mengaktifkan pengiriman server event. |
| `META_CAPI_LOG_ENABLED` | Menyimpan hasil request CAPI ke tabel audit. Aktifkan hanya saat troubleshooting. |
| `META_GRAPH_VERSION` | Versi endpoint Graph API yang digunakan aplikasi. |

Setelah `.env` berubah:

```bash
php artisan optimize:clear
php artisan config:cache
```

## Mapping event

| Event internal | Meta event | Browser Pixel | Server CAPI |
|---|---|---:|---:|
| Visit | PageView | Ya | Ya |
| Engagement | ViewContent | Ya | Ya |
| Intent | Custom event `Intent` | Ya | Ya |
| Direct Checkout | InitiateCheckout | Ya | Ya |
| WhatsApp Lead | Lead | Ya | Ya |
| Form Start | InitiateCheckout | Ya | Ya |
| Lead | Lead | Setelah server mengonfirmasi | Ya |
| Payment | Purchase | Tidak | Ya |
| Scroll/Section View | Tidak dikirim | Tidak | Tidak |

Payment hanya dikirim server karena status pembayaran harus berasal dari callback yang terverifikasi.

## Cara pengiriman server event

CAPI dikirim langsung oleh server pada request analytics yang sama. Tidak ada queue atau worker yang perlu dijalankan. Koneksi ke Meta memiliki batas waktu singkat agar gangguan Meta tidak menahan request tanpa batas.

Event analytics internal disimpan sebelum pengiriman CAPI. Jika Meta gagal merespons, data internal tetap tersimpan dan request analytics tidak dibuat gagal. Aktifkan audit log sementara jika perlu mengetahui status respons Meta.

## Verifikasi dengan Test Events

1. Buka Events Manager → Test Events dan salin test event code.
2. Isi `META_TEST_EVENT_CODE`, lalu bersihkan dan buat ulang cache konfigurasi.
3. Buka landing page staging dan lakukan satu tindakan pada setiap CTA penting.
4. Pastikan event browser dan server muncul dengan Event ID yang sama serta ditandai deduplicated.
5. Untuk FORM internal, selesaikan payment sandbox dan pastikan Purchase berasal dari Server.
6. Kosongkan `META_TEST_EVENT_CODE` setelah verifikasi.

## Data pengguna

Email dinormalisasi menjadi lowercase dan nomor telepon menjadi digit format internasional tanpa tanda `+`. Data kemudian di-hash SHA-256 sebelum dikirim. Hash adalah transformasi satu arah; nilai asli tidak ikut dikirim sebagai field hashing.

Jangan menulis email, telepon, access token, atau signature ke log.

## Troubleshooting singkat

- Server event tidak muncul: periksa Pixel ID, access token, cache konfigurasi, koneksi HTTPS keluar server, dan respons pada audit log.
- Browser event tidak muncul: periksa Pixel ID, ad blocker, dan browser console.
- Event terhitung dua kali: cocokkan Event ID browser dan server pada Test Events.
- Match quality rendah: pastikan form mengirim email/telepon yang valid.
- Perlu audit: aktifkan `META_CAPI_LOG_ENABLED=true` sementara, periksa tabel `meta_capi_logs`, lalu matikan kembali.
