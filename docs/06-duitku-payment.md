# Payment Internal dengan Duitku

Payment internal berarti boilerplate membuat order, meminta invoice ke Duitku, dan menerima status pembayaran melalui callback server. Fitur ini hanya aktif pada `PROJECT_MODE=form` dan `PAYMENT_MODE=internal`.

## Prasyarat

- Akun merchant Duitku dan credential sandbox.
- Domain HTTPS yang dapat diakses Duitku untuk callback.
- Harga produk ditentukan server melalui `.env`.

## Konfigurasi sandbox

```dotenv
PROJECT_MODE=form
PAYMENT_MODE=internal
PRODUCT_NAME="Nama Produk"
PRODUCT_PRICE=199000
DUITKU_ENV=sandbox
DUITKU_MERCHANT_CODE=DS12345
DUITKU_API_KEY=secret-sandbox
DUITKU_EXPIRY_PERIOD=60
```

`PRODUCT_PRICE` menggunakan bilangan bulat Rupiah tanpa titik atau simbol mata uang. Harga pada frontend hanya tampilan; server selalu menggunakan nilai ini ketika membuat order.

## Alur lengkap

```text
Pengunjung submit form
→ POST /lead menyimpan lead
→ TrackedForm meminta POST /checkout
→ Server membuat order pending
→ Server meminta invoice Duitku
→ Browser diarahkan ke payment URL Duitku
→ Duitku mengirim POST /payment/callback
→ Server memverifikasi signature dan nominal
→ Order menjadi paid/failed
→ Payment event dan revenue dicatat jika paid
→ Halaman return hanya membaca status order
```

Callback adalah request server-to-server. Jangan membuat status paid berdasarkan halaman return karena pengunjung dapat menutup halaman, reload, atau membuka URL secara manual.

## Status order

| Status | Arti |
|---|---|
| `pending` | Invoice dibuat dan belum menerima hasil final. |
| `paid` | Callback sukses terverifikasi atau admin melakukan rekonsiliasi. |
| `failed` | Callback gagal terverifikasi dengan kode hasil gagal. |

Callback bersifat idempotent: callback paid yang sama dapat diterima berulang tanpa membuat event Payment kedua. Signature salah atau nominal berbeda menghasilkan HTTP 400 dan tidak mengubah transaksi.

## Callback pada local development

Localhost tidak dapat dipanggil Duitku. Gunakan tunnel HTTPS seperti ngrok:

```bash
php artisan serve
ngrok http 8000
```

Set `APP_URL` ke URL HTTPS tunnel:

```dotenv
APP_URL=https://contoh.ngrok-free.app
```

Lalu jalankan:

```bash
php artisan optimize:clear
```

Buat invoice baru setelah URL berubah. Invoice lama tetap menyimpan callback URL lama.

## Beralih ke production

1. Ganti `DUITKU_ENV=production`.
2. Masukkan merchant code dan API key production.
3. Pastikan `APP_URL` adalah domain HTTPS production.
4. Bersihkan dan cache konfigurasi.
5. Buat transaksi nominal kecil.
6. Periksa order, event Payment, revenue dashboard, dan Meta Purchase jika aktif.

## Rekonsiliasi admin

Dashboard order menyediakan tindakan mark as paid untuk rekonsiliasi manual. Gunakan hanya setelah pembayaran diverifikasi pada dashboard merchant. Tindakan ini membuat Payment event menggunakan nominal order dari database.

## Troubleshooting

- Tidak mendapat payment URL: periksa credential, environment, koneksi keluar server, dan `PRODUCT_PRICE > 0`.
- Callback tidak masuk: periksa HTTPS, `APP_URL`, firewall, dan log server.
- Signature invalid: cocokkan merchant code, API key, amount, dan order number.
- Nominal berbeda: pastikan harga tidak berubah setelah order dibuat.
- Order tetap pending: pastikan callback URL invoice dapat diakses publik dan bukan URL tunnel lama.
