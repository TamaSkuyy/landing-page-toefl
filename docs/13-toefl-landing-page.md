# Landing Page TOEFL ITP Full Bright

Dokumen ini menjelaskan bagaimana file FE klien (`LP.tsx`) di-wiring ke boilerplate ini, keputusan yang diambil, dan bukti bahwa hasil render identik dengan referensi desain `https://fullbrightindonesia.netlify.app/`.

## Ringkasan

| Item | Nilai |
|---|---|
| Halaman | `resources/js/pages/demo/ctwa.tsx` |
| Mode project | `PROJECT_MODE=ctwa`, `PAYMENT_MODE=none` |
| Route | `GET /` (`routes/web.php`) |
| Aset | `resources/assets/` (sumber) dan `public/assets/` (disajikan sebagai `/assets/*`) |
| Nomor WhatsApp | `WHATSAPP_NUMBER` di `.env`, dikirim ke halaman sebagai prop `whatsappNumber` |
| Font | Nunito (Google Fonts), dimuat di `resources/views/app.blade.php` |

Halaman dirender melalui Inertia seperti halaman demo lain, jadi `TrackingLayout`, `TrackedCTA`, dan semua analytics otomatis ikut berjalan.

## Perubahan yang dilakukan

### 1. Halaman landing page

`LP.tsx` dari klien dipindahkan ke `resources/js/pages/demo/ctwa.tsx` dengan penyesuaian berikut.

- **Import boilerplate**: `Head` dari `@inertiajs/intertia` dan `TrackedCTA` dari `@/components/tracking/TrackedCTA`.
- **38 CTA diubah dari `<a>` menjadi `TrackedCTA`** dengan `zone` dan `action` yang sesuai:

  | `zone` | CTA |
  |---|---|
  | `sticky` | Banner flash sale |
  | `nav` | Logo + tombol "Amankan Seat" |
  | `hero` | Dua CTA hero |
  | `midpage` | CTA di section value, proof, LMS, why, testimonials |
  | `pricing` | Tombol checkout, WhatsApp, dan link LMS per paket |
  | `faq` | "Chat Via WA" dan "Lihat Bukti Alumni" |
  | `footer` | Navigasi dan kontak footer |
  | `floating` | Bubble WhatsApp dan survey "Sebelum Kamu Pergi" |

  `action` mengikuti tujuan tautan: `whatsapp`, `external_checkout`, `scroll`, atau `link`.
- **Nomor WhatsApp tidak lagi hardcode**: seluruh tautan memakai `waLink(pesan)` yang dibangun dari prop `whatsappNumber` (berasal dari `WHATSAPP_NUMBER` di `.env`). Pesan per-CTA tetap seperti desain.
- **Section id unik** untuk section-view tracking: `hero`, `alumni`, `agitation`, `value`, `proof`, `lms`, `why`, `testimonials`, `pricing`, `faq`, `survey`, `site-footer`, plus `site-header` untuk navbar. Section `pricing` dipakai oleh dua blok yang saling eksklusif (`mode === 'self'` / `'tutor'`), jadi id-nya tidak pernah duplikat saat runtime.
- **Perbaikan artefak konversi** pada file ekspor:
  - `onClick={() = /> ...}` (syntax error yang juga menelan `/>` pada `<img>`) diperbaiki.
  - `<font color="#7c3aed">` diganti `<span>` karena bukan elemen JSX.
  - Tombol kategori FAQ memakai `FAQ_CATEGORIES[-1]` (selalu `undefined`) sehingga tidak pernah aktif; sekarang memakai indeks yang benar.
  - Kartu "why": ikon kartu 1 berwarna biru (seharusnya merah), padding kartu 2 `2px 4px`, ukuran teks 8px dan 17px yang tidak sesuai desain.
  - Label skor pertama pada section proof memakai `font-size:11px` + `aspect-ratio:16/9` (seharusnya 18px + 1:1).
- **FAQ default**: referensi menampilkan item 1, 7, dan 11 dalam keadaan terbuka, dan beberapa item bisa terbuka bersamaan. State diubah dari `number | null` menjadi daftar indeks (`openFaqs`).
- **Countdown & mode**: nilai awal dihitung lewat lazy initializer, bukan `setState` di dalam effect, agar lolos `react-hooks/set-state-in-effect`.

### 2. Paritas desain

File ekspor klien adalah konversi Tailwind dari sumber desain, dan beberapa nilai menyimpang dari referensi. Semua koreksi berikut diukur dari referensi (bukan diperkirakan), lalu diverifikasi ulang:

- **CSS global desain masuk ke `@layer base`.** Boilerplate memakai Tailwind v4. Aturan tanpa layer (`a { color: … }`) selalu menang atas utility di dalam `@layer`, sehingga teks putih pada tombol merah ikut menjadi merah. Dengan `@layer base`, utility kembali menang — sama seperti inline style di sumber desain.
- **`box-sizing: content-box`** untuk halaman ini (kecuali `button`/`input`/`select`/`textarea`, mengikuti default UA yang dipakai referensi).
- **`line-height: normal`** (referensi tidak memakai preflight Tailwind yang menetapkan `1.5`).
- **`img` kembali `display: inline`** seperti default HTML.
- Ukuran yang diperbaiki agar sama dengan referensi: CTA hero (`14px 28px` / `16px`, bukan `34px 76px` / `27px`), headline agitation (`clamp(28px,3.6vw,42px)`), paragraf pembuka agitation (16px, center), badge value & why (merah, bukan hijau/biru), CTA testimonials & proof, paragraf pembuka pricing (16px), headline survey (`clamp(20px,3.6vw,23px)`), header FAQ (center), padding section agitation (56px) dan LMS (80px), serta layout hero di mobile (gambar konsultan tetap tampil, `max-width:250px`, `max-height:min(28vh,215px)`, gap 12px, offset scroll cue).
- **Aset yang belum ada dilengkapi**: `beranda.gif`, `diagnostic.gif`, dan logo `Logo-Fullbright.webp` diunduh dari referensi ke `public/assets/` supaya seluruh halaman tampil tanpa tautan luar.
- **Semua gambar kini lokal.** 15 gambar yang sebelumnya diambil dari server pihak ketiga (9 logo universitas + 6 foto reviewer Google) disalin ke `public/assets/` dan `resources/assets/`, lalu seluruh referensinya diganti path lokal. Halaman tidak lagi bergantung pada `lh3.googleusercontent.com`, `upload.wikimedia.org`, `unpad.ac.id`, dan sejenisnya; hanya font Nunito yang tetap dari Google Fonts. Perbandingan tinggi halaman setelah perubahan ini tetap sama persis dengan referensi (18076 px di 1440 dan 22592 px di 390).
- **Proxy aset di mode dev.** `public/assets/**` dilayani Laravel, bukan Vite. Tanpa proxy, `url(/assets/...)` di dalam CSS yang dilayani Vite akan diresolusi ke `http://[::1]:5173/assets/...` dan gagal dimuat. `vite.config.ts` kini mem-proxy `/assets` ke `APP_URL` sehingga mode dev dan produksi sama-sama benar.

### 3. Konfigurasi

`.env` (tidak di-commit) disetel ke mode CTWA dan nomor WhatsApp klien:

```dotenv
APP_NAME="TOEFL ITP Full Bright Indonesia"
PROJECT_MODE=ctwa
PAYMENT_MODE=none
CLIENT_ID=fullbright-toefl
WHATSAPP_NUMBER=6285255499299
WHATSAPP_DEFAULT_MESSAGE="Halo Admin Full Bright Indonesia. Saya minat mau daftar kelas TOEFL. Saya mau tanya-tanya dulu."
```

`resources/views/app.blade.php` memuat Nunito dari Google Fonts (sumber yang sama dengan referensi). `routes/web.php` menambahkan prop `whatsappNumber` pada render halaman.

## Hasil verifikasi

Verifikasi dilakukan dengan Chrome headless (CDP) pada referensi dan hasil build lokal, membandingkan geometri tiap elemen (posisi, ukuran, font, warna) pada empat lebar viewport.

| Viewport | Tinggi halaman (lokal vs referensi) | Section dengan selisih |
|---|---|---|
| 1440 px | 18076 px vs 18076 px | tidak ada (0 px) |
| 1024 px | 17830 px vs 17830 px | tidak ada (0 px) |
| 768 px | 20448 px vs 20448 px | tidak ada (0 px) |
| 390 px | 22592 px vs 22592 px | tidak ada (0 px) |

Sepuluh section (agitation, value, proof, lms, testimonials, pricing, faq, survey) memiliki `y` dan `height` identik di semua lebar. Pemeriksaan elemen per elemen mencocokkan 868 elemen berteks; selisih yang tersisa hanya artefak pencocokan (teks di dalam chip/parent ber-style, `font-size` pada `<button>` yang teksnya diatur oleh `<span>` anak) dan animasi (marquee, GIF, countdown).

Analitik diuji end-to-end lewat browser dan dicek langsung di tabel `user_analytics`:

```
visit
section_view  section_id=hero, agitation, value, proof, lms, why, testimonials, pricing, faq, survey
scroll        depth=25/50/75/90
whatsapp_lead cta_zone=pricing  cta_action=whatsapp
direct_checkout cta_zone=pricing cta_action=external_checkout
```

## Menjalankan

```bash
composer install
npm install
cp .env.example .env   # lalu isi DB_* dan WHATSAPP_NUMBER
php artisan key:generate
php artisan migrate
composer dev           # php artisan serve + vite
```

Untuk mode produksi:

```bash
npm run build
php artisan serve
```

## Troubleshooting: tombol tidak berfungsi (hydration gagal)

Gejala: halaman tampil normal, link biasa jalan, tetapi tombol yang mengubah state
(toggle paket, filter FAQ, akordeon FAQ, lightbox, survey) tidak bereaksi. Di console
muncul:

```
You are calling ReactDOMClient.createRoot() on a container that has already been
passed to createRoot() before.

Error: Hydration failed because the server rendered HTML didn't match the client.
```

Penyebabnya: pohon React di entry **client** (`resources/js/app.tsx`) berbeda dengan entry
**SSR** (`resources/js/ssr.tsx`):

| | `app.tsx` (client) | `ssr.tsx` (server) |
|---|---|---|
| Pembungkus | `<TooltipProvider><Suspense><App/></Suspense><Toaster/>` | `<TooltipProvider><App/><Toaster/>` |
| Layout auth | `lazy(() => import(...))` | import statis |
| `title` | `${title}` | `${title} - ${appName}` |

React membuang HTML hasil server dan render ulang di client, sehingga event handler bisa
lepas. Halaman ini memunculkan masalah tersebut karena React 19 otomatis mengangkat
`<link rel="preload" as="image">` untuk `<img>` di navbar.

Perbaikan yang sudah diterapkan:

- `app.tsx` kini memakai pohon yang sama dengan `ssr.tsx` (import statis `AuthLayout`, tanpa
  `<Suspense>`), dan callback `title` disamakan di kedua entry.
- Inisialisasi state yang bergantung pada browser (`localStorage` untuk countdown flash sale,
  `URLSearchParams` untuk `?mode=tutor`) dikembalikan ke `useEffect` setelah mount. Membacanya
  saat render membuat HTML server (`12:00:00`, mode `self`) berbeda dari client dan memicu
  hydration mismatch. `eslint-disable-next-line react-hooks/set-state-in-effect` dipakai di
  dua tempat itu dengan alasannya.

Hasil verifikasi ulang (Chrome headless): console bersih, dan semua interaksi lolos —
toggle paket self/tutor, filter kategori FAQ, akordeon FAQ, lightbox bukti & review,
carousel ulasan, survey, anchor scroll, serta countdown yang benar-benar berjalan.
Tinggi halaman tetap sama persis dengan referensi: 18076 px (1440) dan 22592 px (390).

## Troubleshooting: tombol mati setelah `composer dev` dimatikan

Gejala: halaman tampil tanpa CSS atau tombol tidak bereaksi sama sekali, padahal sebelumnya
normal. Penyebabnya `public/hot` yang tertinggal (Vite dimatikan paksa, atau file sempat
kosong saat proses berhenti). Selama file itu ada, semua URL `@vite` menunjuk ke dev server
yang sudah mati, sehingga CSS dan JavaScript tidak pernah termuat — hanya elemen hasil
server-render yang tampil.

Pengaman yang sudah ditambahkan di `AppServiceProvider`:

- Jika `public/hot` kosong **atau** dev server di alamatnya tidak bisa dihubungi (TCP check
  0,25 detik), aplikasi otomatis memakai manifest hasil `npm run build`.
- Kalau Vite hidup normal, tidak ada perubahan perilaku (dev mode tetap dipakai).

Karena itu **selalu jalankan `npm run build` minimal sekali** supaya fallback tersedia:

```bash
npm run build      # sekali saja, hasilnya dipakai saat Vite tidak jalan
composer dev       # dev harian: php artisan serve + vite
```

Perilaku yang diharapkan:

| Kondisi | Yang dipakai | Hasil |
|---|---|---|
| `composer dev` jalan (`public/hot` valid + Vite hidup) | Vite dev server | hot reload, aset di-proxy |
| `composer dev` dimatikan (hot dihapus Vite) | `public/build` | normal |
| hot tertinggal / kosong / Vite mati | `public/build` (fallback otomatis) | normal |

## Troubleshooting: tombol play video tidak berfungsi

Gejala: mengklik overlay "Putar video testimoni" tidak melakukan apa-apa; overlay tidak
hilang dan video tidak jalan. Di Firefox sering tanpa pesan error yang jelas.

Penyebabnya `php artisan serve`: server bawaan PHP melayani file statis di `public/` sendiri
dan **mengabaikan header `Range`**, jadi video 20 MB dibalas `200 OK` dengan isi penuh
alih-alih `206 Partial Content`. Browser butuh Range untuk memutar/menyek MP4 (apalagi
karena sumbernya memakai fragmen `#t=1.5`), sehingga `video.play()` gagal dan `onPlay`
tidak pernah terpicu.

Perbaikan: video disajikan lewat route Laravel (`/media/{file}`) yang memakai
`response()->file()`, sehingga `BinaryFileResponse` menangani Range dengan benar:

```bash
curl -s -D - -o /dev/null -H "Range: bytes=0-1023" \
  "http://localhost:8000/media/testimoni%20iyha.mp4" | head -1
# HTTP/1.1 206 Partial Content
```

Route-nya hanya menerima nama file (`[A-Za-z0-9 _().-]+`) dari `public/assets`, jadi tidak
bisa dipakai untuk path traversal, dan tetap benar di production (nginx/apache sekalipun).
Video di halaman kini memakai `/media/testimoni iyha.mp4#t=1.5`, bukan `/assets/...` langsung.
Aset lain (gambar, GIF) tidak perlu Range dan tetap dilayani sebagai file statis biasa.

## Troubleshooting: tombol/FAQ/mode toggle "tidak bisa diklik" (halaman berat, hidrasi lambat)

Gejala: halaman tampil lengkap, tapi **semua** tombol diam — toggle "Belajar Sendiri /
Dibimbing Tutor" tidak pindah, accordion FAQ tidak membuka, overlay video tidak hilang.
Konsol bersih tanpa error, dan `[vite] connecting…/connected.` tetap muncul, tetapi React
tidak pernah mount (di log browser Laravel Boost tidak ada baris `Download the React DevTools`,
padahal sesi Chrome pada menit yang sama menampilkannya).

Penyebabnya bukan handler yang salah, melainkan **halaman belum selesai hidrasi saat diklik**,
karena asetnya terlalu berat:

- `public/assets` berukuran ~101 MB: 7 GIF diagram 1920×1200 yang ditampilkan hanya ~500 px
  (`beranda.gif` 17 MB, `video-ai.gif` 15 MB, `diagnostic.gif` 12 MB, `drill.gif` 9,3 MB,
  `simulasi.gif` 9,2 MB, `materi.gif` 6,1 MB, `latihan.gif` 5,5 MB) plus video 19 MB.
- React 19 (SSR "Float") otomatis menyuntik `<link rel="preload" as="image">` untuk **setiap**
  `<img>` yang dirender, dan semua 46 `<img>` di halaman tidak punya atribut `loading`.
  Jadi seluruh ~74 MB GIF diunduh dengan prioritas tinggi sebelum React sempat mount.
- Di `php artisan serve` (single-threaded) kondisi ini membuat halaman butuh belasan detik
  untuk bisa diklik; klik yang jatuh di jendela itu terlihat seperti "tombol rusak".

Perbaikan: semua gambar di bawah lipatan diberi `loading="lazy" decoding="async"` **beserta
`width`/`height` intrinsik** (atribut dimensi membuat browser memesan ruang lebih dulu, jadi
tidak ada layout shift dan tinggi halaman tidak berubah). React 19 otomatis berhenti
mem-preload gambar yang `loading="lazy"`.

```bash
# sebelum: 20 gambar di-preload (termasuk semua GIF)
# sesudah: hanya 6 gambar hero/LCP yang di-preload
curl -s http://localhost:8000/ | grep -c 'rel="preload" as="image"'   # → 1 baris tag, 6 href
curl -s http://localhost:8000/ | grep -o 'loading="lazy"' | wc -l     # → 36
```

Hasil terukur (Chromium headless, 1440 px): unduhan awal ~100 MB → ~14 MB, React mount
**2,0 detik** (mesin idle; 14 detik bila mesin sedang dibebani proses QA lain), tinggi halaman
tetap **18076 px** — sama dengan referensi.

Catatan: `loading="lazy"` sengaja **tidak** dipasang pada 6 gambar hero
(`Logo-Fullbright.webp`, `People 1–3.webp`, `hero-consultant.png`, `pasted-*.png`) supaya
LCP tetap cepat.

## Troubleshooting: halaman "mati" beberapa detik di mode dev (Vite masih dingin)

Gejala: halaman kadang tampil kosong/putih atau tampil tapi semua tombol diam, lalu normal
lagi setelah reload. Di konsol browser muncul request yang gagal untuk
`http://[::1]:5173/resources/js/app.tsx`, `/resources/js/pages/demo/ctwa.tsx`, dan kadang
`/media/testimoni iyha.mp4`.

Penyebabnya bukan kode halaman, tapi **waktu kompilasi Vite saat cache transform masih dingin**
(diukur di mesin ini, sekali jalan setelah Vite restart):

| Permintaan | Waktu (cold) |
| --- | --- |
| `/@vite/client` | 0,26 s |
| `/resources/js/app.tsx` | **2,0 s** |
| `/resources/js/pages/demo/ctwa.tsx` | **9,9 s** (berkas 1,1 MB / 5.243 baris) |

Selama jendela itu React belum mount sehingga seluruh handler belum terpasang. Kalau halaman
di-reload/di-klik sebelum kompilasi selesai, request yang sedang jalan dibatalkan dan halaman
tetap mati (inilah yang terekam sebagai "RESOURCE-GAGAL SCRIPT" di log browser).

Cara paling andal untuk memverifikasi (tanpa Vite sama sekali):

```bash
composer dev          # untuk pengembangan (HMR), ATAU
npm run build && php artisan serve    # untuk verifikasi: tanpa Vite, tanpa proxy
```

Dengan build assets, React mount **~0,5 detik** (terukur) dan tinggi halaman tetap 18076 px.
`AppServiceProvider` otomatis memakai build assets kalau `public/hot` hilang/kosong atau port
Vite tidak bisa dihubungi, jadi mematikan Vite tidak membuat halaman mati.

Sejak perubahan ini Vite juga di-bind ke `127.0.0.1` (bukan `::1`) lewat `server.host` di
`vite.config.ts`, sehingga `public/hot` berisi `http://127.0.0.1:5173` — bukan origin
IPv6-literal yang oleh sebagian browser/ekstensi diperlakukan sebagai pihak ketiga tidak
tepercaya.

## Jebakan Blade: string `@vite` di dalam `<script>` = error 500

Blade memproses direktif **di mana saja**, termasuk di dalam `<script>`. Menuliskan string
`'/@vite/client'` di JavaScript membuat Blade mengompilasinya sebagai direktif `@vite` tanpa
argumen, dan halaman langsung gagal dengan:

```
ArgumentCountError: Too few arguments to function Illuminate\Foundation\Vite::__invoke(), 0 passed
```

Solusinya bungkus skrip dengan `@verbatim ... @endverbatim` (atau pecah stringnya). Berlaku
umum untuk setiap `@kata` yang muncul di dalam JS/CSS inline, bukan hanya `@vite`.

## Bonus: integrasi analytics (status: tersambung dan terverifikasi)

Landing page memakai sistem analytics yang sudah ada di boilerplate, tanpa mengubah kontrak
event. Yang dipasang:

| Bagian | Jumlah | Aturan di `docs/03-frontend-wiring.md` |
| --- | --- | --- |
| `TrackedCTA` | 38 | semua CTA memakai wrapper resmi |
| `<a>` biasa untuk CTA | 0 | tidak ada CTA conversion yang lolos tracking |
| `<section id="...">` | 18 | id unik & stabil untuk section view |
| `TrackedForm` | 0 | mode CTWA tidak punya form lead (memang tidak diperlukan) |

Zone yang dipakai: `pricing`, `midpage`, `footer`, `floating`, `nav`, `hero`, `faq`, `sticky`.
Action: `scroll` (18), `whatsapp` (11), `link` (5), `external_checkout` (4).

Event yang benar-benar masuk database (bukan hanya terpasang), 915 event dari 158 sesi:

| Event | Jumlah | Sumber |
| --- | --- | --- |
| `visit` | 246 | otomatis |
| `section_view` | 244 | otomatis |
| `scroll` (25/50/75/90) | 161 | otomatis |
| `engagement` | 113 | otomatis (threshold 15 s) |
| `intent` | 83 | `TrackedCTA` action `scroll`/`link` |
| `whatsapp_lead` | 50 | `TrackedCTA` action `whatsapp` (termasuk bubble melayang) |
| `direct_checkout` | 18 | `TrackedCTA` action `external_checkout` |

Uji end-to-end terakhir (klik CTA nyata, lalu diperiksa di database) menghasilkan baris berurutan
`visit` → `section_view hero` → `whatsapp_lead (pricing/whatsapp)` → `scroll 25/50/75` →
`section_view pricing` → `intent (sticky/scroll)` → `whatsapp_lead` — lengkap dengan
`landing_source`, device, browser, OS, durasi, dan max scroll di `analytics_sessions`.
Dashboard `/admin` menampilkan funnel Visit → Engagement → Intent → Whatsapp Lead/Direct Checkout
beserta Lead CR dan Referral Sources tanpa error konsol.

Catatan:

1. **UTM untuk CTWA sudah ditambahkan.** Bawaan boilerplate hanya meneruskan `utm_*` pada mode
   FORM (`TrackedForm` membacanya dari URL saat submit), sedangkan tracker browser hanya mengirim
   `landing_source` — sehingga di CTWA kolom `utm_*` selalu kosong. Sekarang
   `resources/js/analytics/tracker.ts` menyimpan `utm_source`, `utm_medium`, `utm_campaign`,
   `utm_content`, dan `utm_term` dari URL kunjungan pertama ke `sessionStorage`, lalu
   menyertakannya di `event_data` setiap event. Tidak ada perubahan backend karena
   `TrackingService` memang sudah membaca `utm_*` dari `event_data`.

   Bukti (buka `/?utm_source=briefing-bonus&utm_medium=qa&utm_campaign=cek-analytics&utm_content=slot-a&utm_term=toefl-itp`
   lalu klik CTA WhatsApp) — semua event ikut membawa atribusi, termasuk outcome-nya:

   | id | event_type | cta_zone | cta_action | utm_source | utm_medium | utm_campaign | utm_content | utm_term |
   | --- | --- | --- | --- | --- | --- | --- | --- | --- |
   | 916 | visit | | | briefing-bonus | qa | cek-analytics | slot-a | toefl-itp |
   | 922 | whatsapp_lead | pricing | whatsapp | briefing-bonus | qa | cek-analytics | slot-a | toefl-itp |

   Kunjungan tanpa UTM tidak mengunci atribusi kosong, jadi URL campaign berikutnya tetap terekam.

2. **Meta Pixel/CAPI belum aktif** karena `META_PIXEL_ID` dan `META_ACCESS_TOKEN` masih kosong di
   `.env`. Rangkaiannya (`MetaEventMapper`, `META_CAPI_ENABLED`, tabel `meta_capi_logs`) sudah
   tersedia; cukup mengisi kredensialnya.

### Akun admin untuk penilaian

```
URL      : /admin  (atau /login)
Email    : demo@fullbright.test
Password : Demo-Fullbright-2026
Role     : admin
```

Akun ini dibuat khusus untuk penilaian (bukan akun pribadi) dan sudah diuji: login berhasil
diarahkan ke `/admin`, dashboard menampilkan Total Visits, Engagement Rate, Lead CR, funnel
Visit → Engagement → Intent → Whatsapp Lead/Direct Checkout, Referral Sources, dan Key Insights
tanpa error konsol. `GET /admin` tanpa login mengembalikan `302` ke halaman login.
Ganti password akun ini (atau hapus akunnya) setelah proses penilaian selesai.

## Bug: HTML landing yang di-cache membawa token CSRF milik sesi lain (event ditolak 419)

Gejala: di production, hanya pengunjung pertama yang event analytics-nya tercatat. Pengunjung
berikutnya membuka halaman dengan normal (200) tetapi semua `POST /analytics/track` dan
`/analytics/heartbeat` gagal `419 CSRF token mismatch`, sehingga dashboard tampak kosong
walaupun trafiknya ada.

Penyebabnya kombinasi dua hal:

1. `CacheLandingPage` menyimpan **HTML utuh** halaman landing selama 7 hari di cache (aktif
   saat `public/hot` tidak ada, yaitu di production).
2. HTML itu memuat `<meta name="csrf-token">` yang **terikat ke session** pembuat cache,
   sedangkan tracker browser (`resources/js/analytics/queue.ts`) mengambil token dari meta
   tersebut. Pengunjung kedua dan seterusnya jadi mengirim token milik session pertama.

Dibuktikan dengan dua sesi berbeda (cookie berbeda):

```bash
# sesi A: cache miss → merender & menyimpan; sesi B: cache hit
# sebelum perbaikan: token A == token B, lalu event dari sesi B → 419
# sesudah perbaikan: token B milik sesinya sendiri, event → {"accepted":1} 201
```

Perbaikannya di `CacheLandingPage`: saat melayani dari cache, token CSRF di HTML diganti
dengan token session yang sedang dilayani (`freshCsrfToken()`), sehingga sisa HTML tetap
tersaji dari cache tanpa mengorbankan keamanan CSRF.

## Deployment Vercel (opsional, gratis)

Vercel **tidak** punya runtime PHP resmi; yang dipakai adalah runtime komunitas
[`vercel-community/php`](https://github.com/vercel-community/php) — sudah dirawat, mendukung PHP
7.4–8.5, dan **menjalankan `composer install` sendiri saat build**. Berkas yang disiapkan di repo:

| Berkas | Fungsi |
| --- | --- |
| `api/index.php` | Entry point function: mengarahkan semua path tulis Laravel ke `/tmp` sebelum boot, melayani file statis saat dijalankan lokal |
| `api/php.ini` | `memory_limit`, `variables_order = "EGPCS"` (agar env var Vercel terbaca `env()`), opcache |
| `vercel.json` | Runtime `vercel-php@0.7.4` (PHP 8.3), `memory` 1024, `maxDuration` 60, aturan route |
| `.vercelignore` | Mengecualikan `/vendor` (di-install saat build), `/node_modules`, log, dll |

Batasan paket Hobby yang perlu diketahui: fungsi maksimum **250 MB** (unzipped), durasi request
**60 detik**, `/tmp` **hilang** setiap container baru, dan **tidak ada cron**. Bawaan boilerplate
sudah cocok dengan itu (vendor tanpa dev ±56 MB + aset 101 MB ⇒ ±172 MB).

### Langkah

1. **Commit aset build.** `/public/build` sengaja di-unignore di `.gitignore` karena runtime ini
   hanya bisa menyajikan aset sebagai file statis kalau berkasnya ada di repository. Setiap kali
   frontend berubah: `npm run build` lalu commit `public/build`.
2. **Import repo** di Vercel (framework preset: *Other*), lalu set environment variable:
   ```dotenv
   APP_NAME="TOEFL ITP Full Bright Indonesia"
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:...            # php artisan key:generate --show
   APP_URL=https://<project>.vercel.app
   LOG_CHANNEL=stderr
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=sync
   INERTIA_SSR_ENABLED=false     # Vercel tidak menjalankan server Node SSR
   TRUSTED_PROXIES=*             # Vercel berada di belakang proxy
   PROJECT_MODE=ctwa
   PAYMENT_MODE=none
   ANALYTICS_ENABLED=true
   CLIENT_ID=fullbright-toefl
   WHATSAPP_NUMBER=6285255499299
   DB_CONNECTION=mysql
   DB_HOST=... DB_PORT=... DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=...
   MYSQL_ATTR_SSL_CA=/var/task/certs/ca.pem   # kalau providernya mewajibkan TLS
   ```
   `config/database.php` sudah mendukung `MYSQL_ATTR_SSL_CA`, jadi MySQL gratis yang mewajibkan
   TLS (mis. Aiven free 1 GB atau TiDB Cloud Serverless 5 GB) bisa dipakai — cukup commit berkas
   CA-nya. **PostgreSQL tidak disarankan**: `AnalyticsMetricsService` memakai `DATE(created_at)`
   yang bukan fungsi di Postgres.
3. **Jalankan migrasi dan buat admin** dari komputer lokal dengan `DB_*` diarahkan ke database
   production (Vercel Hobby tidak memberi shell):
   ```bash
   php artisan migrate --force
   php artisan pbm:create-admin --name="Demo Admin" \
     --email=demo@fullbright.test --password="Demo-Fullbright-2026"
   ```
4. **Verifikasi setelah deploy**: `/` tampil utuh dan bisa diklik, `/build/assets/app-*.js`
   berstatus 200 dengan `content-type: application/javascript`, video testimoni jalan
   (range request), `/admin` menampilkan funnel, dan event baru muncul saat CTA diklik.

Catatan: `routes` di `vercel.json` sengaja menangani **dua konvensi** static Vercel
(`handle: filesystem` lebih dulu, lalu rewrite ke `/public/$1`), karena Vercel bisa menyajikan
`public/` sebagai root statis atau menyimpannya di bawah `/public/`. `/index.php` dan
`/api/*.php` diblokir agar source code tidak pernah tersaji sebagai teks.

Uji lokal meniru Vercel (`php -S 127.0.0.1:8099 -t public api/index.php` dengan
`SESSION_DRIVER=database CACHE_STORE=database INERTIA_SSR_ENABLED=false`): React mount **400 ms**,
CSS/JS/gambar tersaji dengan content-type benar, tanpa error konsol, dan event analytics beserta
UTM benar-benar tercatat ke database.

## Deployment Wasmer Edge (opsional, gratis)

Wasmer Edge **mendukung Laravel secara resmi** ([panduan Laravel](https://docs.wasmer.io/edge/guides/laravel))
dan PHP-nya berjalan di WebAssembly dengan database eksternal atau database terkelola Wasmer.
Berkas yang disiapkan di repo:

| Berkas | Fungsi |
| --- | --- |
| `wasmer.toml` | Paket `php/php` (WASI), mapping `[fs] "/app/" = "."`, perintah `php -t /app/public -S localhost:8080` |
| `wasmer/php.ini` | `variables_order="EGPCS"` (agar secrets terbaca `env()`), opcache, `auto_prepend_file` |
| `wasmer/prepend.php` | Mengarahkan path tulis Laravel ke `/tmp` + log ke `stderr` + session/cache ke database |

Catatan penting: `[fs] "/app/" = "."` memetakan **direktori proyek apa adanya, bukan dari git**.
Jadi `vendor/` dan `public/build/` ikut terkirim walaupun keduanya ada di `.gitignore` —
pastikan keduanya sudah dibangun di lokal sebelum deploy.

### Langkah

```bash
# 1. Siapkan isi direktori yang akan dipaketkan
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Uji lokal lewat runtime Wasmer (belum deploy) → http://localhost:8080
wasmer run .

# 3. Deploy
wasmer deploy
```

Secrets/environment (setelah app dibuat, lewat CLI):

```bash
wasmer app secrets create APP_KEY "$(php artisan key:generate --show)"
wasmer app secrets create APP_ENV production
wasmer app secrets create APP_DEBUG false
wasmer app secrets create APP_URL https://<app>.wasmer.app
wasmer app secrets create PROJECT_MODE ctwa
wasmer app secrets create PAYMENT_MODE none
wasmer app secrets create WHATSAPP_NUMBER 6285255499299
wasmer app secrets create ANALYTICS_ENABLED true
wasmer app secrets create CLIENT_ID fullbright-toefl
wasmer app secrets create INERTIA_SSR_ENABLED false
wasmer app secrets create TRUSTED_PROXIES '*'
wasmer app secrets create DB_CONNECTION mysql
wasmer app secrets create DB_HOST ... DB_PORT ... DB_DATABASE ... DB_USERNAME ... DB_PASSWORD ...
```

Database: Wasmer Edge menyediakan database terkelola (`wasmer app database create`,
`wasmer app database list --with-password`), atau pakai MySQL gratis eksternal
(TiDB Cloud / PlanetScale). **Hindari Postgres** karena `AnalyticsMetricsService` memakai
`DATE(created_at)` yang bukan fungsi di Postgres.

Migrasi dan akun admin dijalankan dari lokal dengan `DB_*` diarahkan ke database tersebut:

```bash
php artisan migrate --force
php artisan pbm:create-admin --name="Demo Admin" \
  --email=demo@fullbright.test --password="Demo-Fullbright-2026"
```

### Yang perlu diwaspadai

1. **Versi paket PHP vs `pdo_mysql`.** Panduan Laravel memakai `php/php = "=8.3.4"`, sedangkan
   dukungan MySQL/Postgres (`mysqli`, `pdo`) diperkenalkan pada paket yang lebih baru (blog
   Wasmer menyebut `php/php@8.3.400`). Kalau muncul `could not find driver`, ganti versinya di
   `wasmer.toml`.
2. **`vendor/` harus ada di direktori yang dipaketkan.** Karena `[fs]` memetakan direktori
   lokal, jalankan `composer install --no-dev` sebelum `wasmer run .` / `wasmer deploy`.
   Kalau ternyata Wasmer menghormati `.gitignore` saat memaketkan sehingga `vendor` ikut
   terbuang, solusinya satu baris: hapus `/vendor` dari `.gitignore` (atau commit `vendor/`).
   Gejalanya langsung ketahuan lewat `wasmer run .` sebelum deploy.
3. **Filesystem tidak persisten.** `wasmer/prepend.php` sudah menanganinya (view ke `/tmp`, log
   ke `stderr`, session & cache ke database), jadi tidak ada penulisan ke direktori aplikasi.
4. **Selalu di belakang proxy** → set `TRUSTED_PROXIES=*` supaya URL dan cookie HTTPS benar.
5. **Cold start** bisa dipercepat dengan Instaboot di `app.yaml` (mem-pre-warm request `/`), dan
   `scaling.mode: single_concurrency` disarankan untuk PHP.

## Catatan environment

- Ekstensi `pdo_sqlite` tidak tersedia di mesin ini, jadi `php artisan test` bawaan (SQLite in-memory) tidak bisa jalan. Dengan MySQL, 40 dari 45 test lulus; 5 test di `AnalyticsDashboardTest` gagal karena skema memakai generated column yang tidak bisa di-insert eksplisit di MySQL — masalah portabilitas test yang sudah ada sebelum perubahan ini.
- `vendor/bin/phpstan analyse` berhenti karena batas memori 128M pada konfigurasi PHP mesin ini; jalankan dengan `--memory-limit=1G` bila ingin hasil lengkap.
- Situs referensi `fullbrightindonesia.netlify.app` sempat tidak dapat diakses ("Site not available") setelah proses verifikasi selesai. Semua angka pembanding diambil sebelum itu dan tersimpan pada artefak QA.

## Troubleshooting: error CORS / halaman tampil tanpa CSS & font

Gejala di browser (Firefox paling jelas):

```
Cross-Origin Request Blocked: The Same Origin Policy disallows reading the remote
resource at http://127.0.0.1:8123/build/assets/...woff2. (Reason: CORS request did not succeed)
```

Penyebabnya kombinasi dua hal:

1. `App\Http\Middleware\CacheLandingPage` menyimpan HTML landing page selama 7 hari di cache store (database). HTML yang tersimpan memuat URL aset **absolut**, dan URL itu mengikuti host/port saat halaman pertama kali dirender.
2. Karena itu, HTML yang ter-cache dari `127.0.0.1:8123` akan diputar ulang untuk request ke `localhost:8000` — aset dan font menunjuk ke port yang salah/mati, dan Firefox melaporkannya sebagai error CORS.

Perbaikan yang sudah diterapkan:

- `ASSET_URL=/` pada `.env` (dan `.env.example`), sehingga `@vite` dan `@fonts` menghasilkan URL root-relative (`/build/assets/...`). HTML yang ter-cache jadi tetap benar di host/port mana pun.
- `CacheLandingPage::manifestVersion()` kini mengembalikan `dev` selama `public/hot` ada, supaya HTML mode Vite dev (yang memuat URL `http://[::1]:5173`) tidak pernah tercampur dengan HTML hasil `npm run build`.

Kalau gejalanya masih muncul (misalnya karena HTML lama sudah tersimpan di browser):

```bash
# buang HTML hasil render host lama
php artisan cache:clear
# atau hanya entri landing page:
mysql -uroot -p landing_page_toefl -e "delete from cache where \`key\` like '%landing_page_html%';"
```

Lalu lakukan hard reload di browser (Ctrl+Shift+R) untuk membuang HTML/CSS lama yang menyebut port tersebut.

Catatan mode:

| Kondisi | Aset disajikan dari | CORS |
|---|---|---|
| `npm run dev` berjalan (`public/hot` ada) | Vite dev server (`http://[::1]:5173`) | Vite mengirim `Access-Control-Allow-Origin`, aman |
| tanpa `public/hot` (setelah `npm run build`) | `public/build` (root-relative) | tidak ada request lintas origin |

Untuk pengujian yang paling mirip produksi, jalankan `npm run build` lalu `php artisan serve` tanpa Vite.

## Troubleshooting: gambar /assets gagal di mode Vite dev

Gejala di console browser:

```
GET http://[::1]:5173/assets/toefl1.webp   NS_ERROR_DOM_NETWORK_ERR
```

`public/assets/**` hanya dilayani Laravel. Saat Vite dev server aktif, CSS diambil dari
`http://[::1]:5173`, sehingga `url(/assets/...)` di dalam CSS ikut diresolusi ke origin Vite
dan tidak ditemukan. `vite.config.ts` sudah mem-proxy path `/assets` ke `APP_URL`, jadi
gambar tetap dimuat. Bila gejalanya muncul, pastikan:

1. `APP_URL` di `.env` sesuai alamat `php artisan serve` (default `http://localhost:8000`).
2. Vite dev server di-restart setelah mengubah `vite.config.ts` (Vite biasanya restart otomatis).
3. Semua berkas benar-benar ada: `ls public/assets | wc -l`.

## Troubleshooting: gambar dari domain luar gagal

Foto reviewer Google (`lh3.googleusercontent.com`) dan logo universitas dari situs pihak
ketiga bisa diblokir browser (atau sumbernya menolak hotlink). Semua gambar tersebut kini
disalin ke `public/assets/`, jadi tidak ada lagi request ke domain luar selain Google Fonts.
Total 15 berkas tambahan: `univ-*.png|webp|svg` dan `reviewer-1..6.png`.


