# Kontrak Event dan Metrik Analytics

Dokumen ini adalah acuan resmi untuk nama event, arti event, dan rumus dashboard. Baca sebelum menambah CTA atau mengubah tracking.

## Konsep data

- **Event** adalah satu aktivitas, misalnya halaman dibuka atau CTA diklik.
- **Session** adalah kelompok aktivitas pada satu sesi browser Laravel.
- **Visitor ID** adalah UUID pada cookie `pbm_vid` untuk mengenali browser secara pseudonim.
- **Landing source** adalah path pertama dalam sesi dan menjadi identitas varian di A/B Labs.
- **Event ID** adalah identifier unik untuk mencegah event yang sama tersimpan atau terkirim dua kali.

Nama event backend didefinisikan di `app/Analytics/EventType.php`. Nilai yang sama tersedia untuk frontend di `resources/js/analytics/event-types.ts`. Jangan membuat sinonim dan jangan menambahkan `event_data.type`.

## Event otomatis dan event bisnis

| Event | Mode | Arti | Cara tercatat |
|---|---|---|---|
| `visit` | Semua | Landing source dibuka dalam sesi | Otomatis, sekali per sesi dan landing source |
| `engagement` | Semua | Browser aktif mencapai batas waktu | Otomatis, sekali per session storage; menjadi salah satu sinyal engaged |
| `scroll` | Semua | Mencapai 25%, 50%, 75%, atau 90% halaman | Otomatis per milestone |
| `section_view` | Semua | Section terlihat minimal 20% selama 500 ms | Otomatis per section |
| `intent` | Semua | CTA menunjukkan minat tetapi belum conversion | Dari `TrackedCTA` |
| `direct_checkout` | CTWA | CTA membuka checkout eksternal | Dari `TrackedCTA` |
| `whatsapp_lead` | CTWA | CTA membuka WhatsApp | Dari `TrackedCTA` |
| `form_start` | FORM | Pengunjung pertama kali mengisi form | Otomatis dari `TrackedForm` |
| `lead` | FORM | Form berhasil disimpan | Hanya server |
| `payment` | FORM internal | Pembayaran sukses terverifikasi | Hanya callback server atau admin |

Event `lead` dan `payment` ditolak jika dikirim melalui endpoint browser. Aturan ini mencegah angka conversion mudah dimanipulasi dari developer tools browser.

## Engagement dan bounce

Setiap session baru dimulai dengan status bounce. Session berubah menjadi engaged ketika memenuhi minimal satu kondisi:

- halaman aktif/visible selama `ANALYTICS_ENGAGEMENT_THRESHOLD` detik;
- scroll lebih dari `ANALYTICS_SCROLL_BOUNCE_THRESHOLD` persen;
- menghasilkan event funnel selain visit, misalnya intent, form start, lead, WhatsApp, checkout, atau payment.

Engagement adalah negasi bounce:

```text
Engaged Sessions = Visits - Bounced Sessions
Engagement Rate = Engaged Sessions / Visits × 100%
Bounce Rate = Bounced Sessions / Visits × 100%
Engagement Rate + Bounce Rate = 100%
```

Pengunjung yang langsung klik CTA dalam lima detik menjadi engaged walaupun belum menghasilkan event durasi `engagement` 15 detik.

## Metrik conversion

| Metrik | Rumus |
|---|---|
| Total Lead CTWA | Jumlah session unik pada gabungan `whatsapp_lead` dan `direct_checkout` |
| Total Lead FORM | Jumlah session unik dengan `lead` |
| Lead CR | Total Lead ÷ Visit × 100% |
| Sales CR | Payment sukses ÷ Visit × 100% |
| Lead-to-Payment Rate | Payment sukses ÷ Total Lead × 100% |
| Revenue | Jumlah `payment_amount` dari payment berstatus `paid` atau `success` |
| RPV | Revenue ÷ Visit |

Satu session CTWA yang membuka WhatsApp dan checkout dihitung satu kali pada Total Lead, tetapi masing-masing outcome tetap terlihat terpisah.

## Zone dan action CTA

**Zone** adalah lokasi CTA. Nilai yang tersedia:

`hero`, `pricing`, `sticky`, `floating`, `footer`, `midpage`, `faq`, `nav`.

**Action** adalah tindakan CTA. Nilai yang tersedia:

`whatsapp`, `external_checkout`, `form_anchor`, `internal_checkout`, `scroll`, `link`.

Event ditentukan oleh action, bukan zone:

| Mode | Action | Event |
|---|---|---|
| CTWA | `whatsapp` | `whatsapp_lead` |
| CTWA | `external_checkout` | `direct_checkout` |
| Semua | `scroll`, `link`, `form_anchor` | `intent` |
| FORM | `internal_checkout` | `intent`; payment tetap datang dari server |

Contoh: CTA WhatsApp yang dipindah dari `pricing` ke `floating` tetap menghasilkan `whatsapp_lead`. Hanya dimensi lokasinya yang berubah.

## Contoh payload

```json
{
  "event_type": "intent",
  "event_data": {
    "event_id": "0198f810-2e91-7ef1-a08b-f8380c5dd642",
    "landing_source": "/variant-a",
    "zone": "hero",
    "action": "scroll",
    "cta_label": "Lihat Paket"
  }
}
```

`event_type` adalah nama event. `event_data` berisi detail tambahan. Endpoint menerima satu event atau batch maksimal 20 event dan membatasi request untuk mengurangi spam.

## Attribution

Form secara otomatis meneruskan:

- `landing_source`;
- `utm_source`;
- `utm_medium`;
- `utm_campaign`;
- `utm_content`;
- `utm_term`.

UTM adalah parameter URL untuk menandai sumber campaign. Contoh:

```text
https://example.com/variant-a?utm_source=meta&utm_medium=paid&utm_campaign=launch
```

Jangan mengubah landing source selama sesi. A/B Labs menggunakannya untuk menghubungkan visit, CTA, lead, dan payment ke varian yang sama.

## Retention dan arsip

Data dashboard aktif dibatasi oleh `ANALYTICS_RETENTION_DAYS`, maksimal 90 hari. Scheduler memindahkan event dan session yang lebih lama ke tabel arsip melalui command:

```bash
php artisan analytics:archive
```

Lead dan order tidak ikut diarsipkan oleh command tersebut.

## Privasi

- Jangan memasukkan email, nomor telepon, atau data pribadi ke `event_data` browser.
- Lead menyimpan data personal pada tabel khusus `leads`.
- Data untuk Meta CAPI dinormalisasi dan di-hash SHA-256 sebelum dikirim.
- Jangan menyalakan log payload sensitif secara permanen.
