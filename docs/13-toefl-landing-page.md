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


