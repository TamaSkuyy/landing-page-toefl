# Memilih Mode Project

Mode menentukan event conversion, route, kartu dashboard, dan alur sesudah CTA/form. Satu deployment menggunakan satu `PROJECT_MODE`.

## Tabel keputusan

| Kebutuhan | `PROJECT_MODE` | `PAYMENT_MODE` | Hasil utama |
|---|---|---|---|
| CTA WhatsApp | `ctwa` | `none` | WhatsApp Lead |
| CTA checkout pihak ketiga tanpa form | `ctwa` | `none` | Direct Checkout |
| Form lalu thank-you | `form` | `none` | Lead |
| Form lalu payment milik klien | `form` | `external` | Lead; payment tidak diketahui tanpa integrasi tambahan |
| Form lalu Duitku | `form` | `internal` | Lead, order, payment, revenue |

## Mode CTWA

Gunakan ketika conversion terjadi melalui WhatsApp atau checkout eksternal dan boilerplate tidak perlu menyimpan form.

```dotenv
CLIENT_ID=nama-klien
PROJECT_MODE=ctwa
PAYMENT_MODE=none
WHATSAPP_NUMBER=628123456789
WHATSAPP_DEFAULT_MESSAGE="Halo, saya tertarik."
EXTERNAL_CHECKOUT_URL=https://example.com/checkout
```

Nomor WhatsApp menggunakan format internasional berupa digit tanpa `+`, spasi, atau tanda hubung.

Dashboard menampilkan WhatsApp Lead, Direct Checkout, Total Lead, dan Lead CR. Total Lead adalah gabungan session unik kedua outcome.

## FORM tanpa payment

```dotenv
CLIENT_ID=nama-klien
PROJECT_MODE=form
PAYMENT_MODE=none
THANK_YOU_PATH=/terima-kasih
```

Setelah form disimpan, frontend diarahkan ke `THANK_YOU_PATH`. Dashboard menampilkan Form Start, Lead, dan Lead CR.

## FORM dengan payment eksternal

```dotenv
PROJECT_MODE=form
PAYMENT_MODE=external
EXTERNAL_PAYMENT_URL=https://payment-klien.example.com
```

Setelah lead tersimpan, frontend diarahkan ke URL tersebut. Boilerplate tidak menerima status payment eksternal secara otomatis, sehingga dashboard hanya dapat menjamin data sampai Lead. Untuk menghitung payment, gateway eksternal harus mengirim callback yang dipetakan ke order dan Payment event.

## FORM dengan Duitku

```dotenv
PROJECT_MODE=form
PAYMENT_MODE=internal
PRODUCT_NAME="Nama Produk"
PRODUCT_PRICE=199000
DUITKU_ENV=sandbox
DUITKU_MERCHANT_CODE=
DUITKU_API_KEY=
```

Boilerplate menyimpan lead, membuat order, meminta invoice, memverifikasi callback, lalu menghitung payment dan revenue. Lihat [Payment Duitku](06-duitku-payment.md).

## Perbedaan fitur

| Fitur | CTWA | FORM none/external | FORM internal |
|---|---:|---:|---:|
| Route `/lead` | Tidak | Ya | Ya |
| Penyimpanan lead | Tidak | Ya | Ya |
| Order | Tidak | Tidak | Ya |
| WhatsApp/Direct Checkout | Ya | Tidak | Tidak |
| Form Start/Lead | Tidak | Ya | Ya |
| Payment/Revenue | Tidak | Tidak | Ya |
| Dashboard Orders | Tidak relevan | Kosong | Digunakan |

## Setelah mengganti mode

Route FORM didaftarkan berdasarkan konfigurasi saat aplikasi boot. Setelah mode berubah:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

Periksa route aktif:

```bash
php artisan route:list
```

Jangan mengganti mode pada project yang sudah menerima traffic tanpa rencana migrasi data dan verifikasi dashboard.
