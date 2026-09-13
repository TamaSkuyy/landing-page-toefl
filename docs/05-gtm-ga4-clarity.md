# Google Tag Manager, GA4, dan Microsoft Clarity

Google Tag Manager (GTM) mengelola tag marketing dari satu container. Google Analytics 4 (GA4) mengukur traffic dan perilaku. Microsoft Clarity menyediakan recording sesi serta heatmap.

## Konfigurasi

```dotenv
GTM_CONTAINER_ID=GTM-XXXXXXX
GA4_MEASUREMENT_ID=G-XXXXXXXXXX
CLARITY_PROJECT_ID=xxxxxxxxxx
```

Aturan pemuatan:

- Jika `GTM_CONTAINER_ID` terisi, container GTM dimuat dan script GA4 langsung tidak dimuat.
- Jika GTM kosong tetapi `GA4_MEASUREMENT_ID` terisi, aplikasi memuat `gtag.js` langsung.
- Clarity dimuat jika `CLARITY_PROJECT_ID` terisi.
- Jika semuanya kosong, tidak ada script tersebut yang dimuat.

Jangan memasang GA4 langsung dan melalui GTM secara bersamaan karena dapat menggandakan page view.

## Data layer

Data layer adalah array JavaScript yang dibaca GTM. Setiap event internal mendorong objek seperti berikut:

```js
{
    event: 'whatsapp_lead',
    zone: 'pricing',
    action: 'whatsapp',
    cta_label: 'Chat Sekarang',
    landing_source: '/variant-a',
    value: undefined,
    currency: undefined,
}
```

Di GTM, buat Custom Event Trigger menggunakan nama event internal persis, misalnya `whatsapp_lead`. Jangan membuat alias berbeda karena akan menyulitkan perbandingan dengan dashboard internal.

## Verifikasi GTM

1. Buka Tag Assistant Preview.
2. Hubungkan URL staging.
3. Buka landing page dan pastikan `visit` muncul.
4. Klik anchor dan pastikan `intent` beserta `zone`/`action` benar.
5. Scroll halaman dan pastikan milestone `scroll` muncul.
6. Klik WhatsApp atau checkout dan pastikan event terkirim sebelum browser berpindah.
7. Pastikan tag GA4 hanya dijalankan sekali.

## Clarity dan A/B testing

Boilerplate mengirim `landing_source` melalui `clarity('set', ...)` dan Visitor ID melalui `clarity('identify', ...)`. Gunakan landing source untuk memfilter recording berdasarkan varian halaman.

Visitor ID adalah UUID pseudonim dari cookie `pbm_vid`, bukan email atau nomor telepon.

## Setelah mengubah konfigurasi

```bash
php artisan optimize:clear
php artisan config:cache
```

Gunakan browser incognito tanpa ad blocker saat verifikasi karena extension privasi dapat memblokir GTM, GA4, atau Clarity.
