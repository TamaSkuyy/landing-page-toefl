# Checklist QA Sebelum Launch

QA (Quality Assurance) adalah proses memeriksa bahwa fitur bekerja sesuai kebutuhan. Jalankan checklist ini pada staging dengan credential klien sebenarnya atau sandbox.

## Konfigurasi dan keamanan

- [ ] `APP_ENV=production` dan `APP_DEBUG=false` pada production.
- [ ] Website menggunakan HTTPS.
- [ ] `.env` tidak masuk Git dan tidak dapat diakses publik.
- [ ] Document root mengarah ke folder `public`.
- [ ] Mode project dan payment sesuai alur landing page.
- [ ] Database production bukan database local/staging.
- [ ] Admin menggunakan password kuat dan registrasi publik tidak tersedia.
- [ ] ID integrasi kosong tidak memuat script yang tidak dipakai.

## Tampilan dan UX

- [ ] Landing page benar pada mobile, tablet, dan desktop.
- [ ] Tidak ada horizontal overflow yang tidak disengaja.
- [ ] Semua CTA dapat diklik dan menuju tujuan benar.
- [ ] Form memiliki label, state loading, pesan error, dan validasi yang jelas.
- [ ] Browser console bebas error.
- [ ] Asset, font, dan gambar berhasil dimuat melalui HTTPS.

## Event otomatis

- [ ] Membuka varian menghasilkan satu Visit.
- [ ] Tab aktif mencapai batas waktu menghasilkan event Engagement satu kali.
- [ ] Scroll menghasilkan milestone 25/50/75/90 satu kali.
- [ ] Section terlihat menghasilkan Section View.
- [ ] Heartbeat memperbarui durasi tanpa membuat event berulang.
- [ ] Engagement Rate + Bounce Rate = 100%.

## Mode CTWA

- [ ] Anchor hero menghasilkan Intent dengan zone/action benar.
- [ ] CTA WhatsApp menghasilkan WhatsApp Lead sebelum navigasi.
- [ ] CTA checkout menghasilkan Direct Checkout.
- [ ] Floating WhatsApp tetap tercatat sebagai WhatsApp Lead.
- [ ] Session yang melakukan WhatsApp dan checkout dihitung satu Total Lead.

## Mode FORM

- [ ] Input pertama menghasilkan Form Start satu kali.
- [ ] Submit valid membuat satu lead dan satu event Lead.
- [ ] Submit invalid menampilkan pesan tanpa menyimpan lead.
- [ ] Field tambahan tersimpan di `leads.extra`.
- [ ] Mode `none` menuju halaman terima kasih.
- [ ] Mode `external` menuju URL payment eksternal.

## Duitku

- [ ] Submit membuat order pending dengan harga dari server.
- [ ] Sandbox mengembalikan payment URL.
- [ ] Callback sukses membuat order paid dan satu Payment event.
- [ ] Callback berulang tidak menggandakan Payment/revenue.
- [ ] Signature salah dan nominal berbeda ditolak.
- [ ] Return page hanya membaca status order.
- [ ] Rekonsiliasi manual hanya dilakukan setelah verifikasi merchant.

## Dashboard

- [ ] `/admin` dan `/admin/labs` hanya dapat diakses admin.
- [ ] Semua card, chart, funnel, dan tabel responsif.
- [ ] Filter 3/5/7/14/30/90 hari bekerja.
- [ ] Custom range A/B Labs dibatasi retention.
- [ ] Funnel tidak memiliki tahap yang melebihi tahap sebelumnya.
- [ ] CTWA menggunakan dua outcome branch.
- [ ] FORM internal menampilkan Payment dan Revenue.
- [ ] Export CSV berhasil diunduh dan dapat dibuka.
- [ ] Refresh Data A/B Labs menghitung ulang laporan.

## Meta dan tag manager

- [ ] Meta Pixel Helper menemukan Pixel yang benar.
- [ ] Test Events menerima event browser dan server.
- [ ] Event yang sama memiliki Event ID sama dan deduplicated.
- [ ] Purchase hanya berasal dari server.
- [ ] GTM Preview menerima nama event internal yang benar.
- [ ] GA4 tidak dimuat dua kali.
- [ ] Clarity menerima landing source dan Visitor ID.
- [ ] Test Event Code dikosongkan sebelum production.

## Operasional production

- [ ] `composer test` lulus.
- [ ] `npm run lint:check`, `format:check`, `types:check`, dan `build` lulus.
- [ ] Meta server event muncul tanpa menjalankan queue worker.
- [ ] Cron dan scheduler aktif.
- [ ] `analytics:archive` dapat dijalankan.
- [ ] Log rotation aktif dan disk memiliki ruang cukup.
- [ ] Backup database dan prosedur rollback tersedia.
- [ ] Smoke test `/up`, landing page, login, dashboard, CTA, dan form lulus.

Jangan membuka traffic campaign sebelum seluruh item yang relevan ditandai. Simpan hasil QA per project sebagai catatan release, bukan sebagai bagian dari boilerplate umum.
