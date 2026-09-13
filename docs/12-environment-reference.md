# Referensi Environment

File `.env` mengatur satu instalasi aplikasi. Nilai production berbeda dari local dan staging. Jangan commit file ini.

## Aplikasi

| Variabel | Contoh | Fungsi |
|---|---|---|
| `APP_NAME` | `PBM Landing Page` | Nama aplikasi. |
| `APP_ENV` | `local`/`production` | Jenis environment. |
| `APP_KEY` | Dibuat Artisan | Kunci enkripsi Laravel. |
| `APP_DEBUG` | `true`/`false` | Menampilkan detail error; wajib `false` di production. |
| `APP_URL` | `https://example.com` | URL dasar, termasuk callback payment. |
| `APP_LOCALE` | `id` | Bahasa default aplikasi. |

## Database dan penyimpanan aplikasi

| Variabel | Fungsi |
|---|---|
| `DB_CONNECTION` | Driver database, default `mysql`. |
| `DB_HOST`, `DB_PORT` | Host dan port database. |
| `DB_DATABASE` | Nama database. |
| `DB_USERNAME`, `DB_PASSWORD` | Credential database. |
| `SESSION_DRIVER` | Penyimpanan session; default `database`. |
| `SESSION_LIFETIME` | Durasi session Laravel dalam menit. |
| `CACHE_STORE` | Penyimpanan cache; default `database`. |
| `QUEUE_CONNECTION` | Driver queue Laravel; default `sync`. Boilerplate tidak menggunakan queue untuk Meta CAPI dan tidak membutuhkan worker. |

## Identitas dan analytics

| Variabel | Default | Fungsi |
|---|---:|---|
| `CLIENT_ID` | `client-slug` | Label project/klien. |
| `PROJECT_MODE` | `ctwa` | Mode conversion: `ctwa` atau `form`. |
| `ANALYTICS_ENABLED` | `true` | Mengaktifkan analytics internal. |
| `ANALYTICS_ENGAGEMENT_THRESHOLD` | `15` | Detik aktif minimum sebagai sinyal engagement. |
| `ANALYTICS_HEARTBEAT_INTERVAL` | `30` | Interval heartbeat browser dalam detik. |
| `ANALYTICS_SESSION_TIMEOUT` | `30` | Nilai konfigurasi timeout analytics untuk kompatibilitas; masa session server dikendalikan `SESSION_LIFETIME`. |
| `ANALYTICS_SCROLL_BOUNCE_THRESHOLD` | `25` | Persentase scroll yang harus dilewati untuk menghapus bounce. |
| `ANALYTICS_SECTION_VIEW_ENABLED` | `true` | Mengaktifkan section view tracking. |
| `ANALYTICS_MINIMUM_WINNER_VISITS` | `30` | Minimum visit sebelum varian eligible. |
| `ANALYTICS_RETENTION_DAYS` | `90` | Hari data aktif; sistem membatasi maksimal 90. |

## CTWA

| Variabel | Fungsi |
|---|---|
| `WHATSAPP_NUMBER` | Nomor internasional berupa digit tanpa `+`. |
| `WHATSAPP_DEFAULT_MESSAGE` | Pesan awal pada link WhatsApp. |
| `EXTERNAL_CHECKOUT_URL` | URL checkout eksternal opsional. |

## FORM dan payment

| Variabel | Fungsi |
|---|---|
| `PAYMENT_MODE` | `none`, `external`, atau `internal`. |
| `EXTERNAL_PAYMENT_URL` | Tujuan setelah lead pada mode external. |
| `THANK_YOU_PATH` | Path setelah lead pada mode none. |
| `PRODUCT_NAME` | Nama produk untuk checkout/CAPI. |
| `PRODUCT_PRICE` | Harga integer Rupiah dari server. |

## Duitku

| Variabel | Fungsi |
|---|---|
| `DUITKU_ENV` | `sandbox` atau `production`. |
| `DUITKU_MERCHANT_CODE` | Kode merchant. |
| `DUITKU_API_KEY` | Secret API Duitku. |
| `DUITKU_EXPIRY_PERIOD` | Masa berlaku invoice dalam menit. |

## Meta

| Variabel | Fungsi |
|---|---|
| `META_PIXEL_ID` | ID Meta Pixel/dataset. |
| `META_ACCESS_TOKEN` | Secret CAPI. |
| `META_TEST_EVENT_CODE` | Kode sementara Test Events. |
| `META_CAPI_ENABLED` | Mengaktifkan server event. |
| `META_CAPI_LOG_ENABLED` | Menyimpan audit response CAPI sementara. |
| `META_GRAPH_VERSION` | Versi Meta Graph API. |

## Google dan Microsoft

| Variabel | Fungsi |
|---|---|
| `GTM_CONTAINER_ID` | ID Google Tag Manager. |
| `GA4_MEASUREMENT_ID` | ID GA4 direct ketika GTM kosong. |
| `CLARITY_PROJECT_ID` | ID project Microsoft Clarity. |

## Logging dan email

| Variabel | Fungsi |
|---|---|
| `LOG_CHANNEL` | Channel log; `daily` direkomendasikan. |
| `LOG_LEVEL` | Level minimum log, misalnya `debug` atau `error`. |
| `LOG_DAILY_DAYS` | Jumlah file log harian yang disimpan. |
| `MAIL_MAILER` | Driver email; `log` tidak mengirim email sungguhan. |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Identitas pengirim email. |

Setelah mengubah `.env` pada production:

```bash
php artisan optimize:clear
php artisan config:cache
```
