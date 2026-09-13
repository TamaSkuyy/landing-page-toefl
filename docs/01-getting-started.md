# Instalasi dan Project Pertama

Panduan ini membawa project dari hasil clone sampai landing page dan dashboard dapat dibuka di komputer lokal. Jika menemukan istilah yang belum dikenal, lihat [Glosarium](00-glossary.md).

## 1. Siapkan perangkat

Pastikan command berikut dapat dijalankan dari terminal:

```bash
php --version
composer --version
node --version
npm --version
git --version
```

Gunakan PHP 8.3+, Composer 2.x, dan Node.js 22.13+. PHP memerlukan extension Laravel umum seperti `pdo_mysql`, `mbstring`, `openssl`, `json`, dan `curl`.

Pilihan lingkungan local:

- Windows: Laragon direkomendasikan karena menyediakan PHP, database, dan terminal dalam satu aplikasi.
- macOS: Laravel Herd atau Homebrew.
- Linux: PHP, Composer, Node.js, dan MySQL/MariaDB dari package manager distribusi.

## 2. Ambil source code

```bash
git clone https://github.com/pbmagency/boilerplate-lp.git nama-project
cd nama-project
```

`nama-project` adalah nama folder lokal. Untuk project klien, gunakan nama yang jelas dan buat repository Git baru sesuai workflow tim.

## 3. Pasang dependency

```bash
composer install
npm install
```

Composer memasang package backend ke folder `vendor`. npm memasang package frontend ke folder `node_modules`. Kedua folder dibuat otomatis dan tidak perlu diedit manual.

## 4. Buat file environment

```bash
# macOS/Linux/Git Bash
cp .env.example .env

# Windows PowerShell
Copy-Item .env.example .env
```

File `.env` adalah konfigurasi khusus satu environment. File ini dapat berisi secret dan tidak boleh di-commit.

Buat application key:

```bash
php artisan key:generate
```

Application key digunakan Laravel untuk enkripsi cookie dan data sensitif aplikasi.

## 5. Buat dan hubungkan database

Buat database MySQL/MariaDB kosong, misalnya `pbm_landing_page`, lalu sesuaikan `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pbm_landing_page
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migration:

```bash
php artisan migrate
```

Migration membuat tabel user, session, cache, analytics, lead, order, dan arsip analytics. Tabel queue bawaan Laravel juga tersedia untuk pengembangan fitur lain, tetapi boilerplate tidak memerlukan queue worker untuk beroperasi.

Jika command gagal, jangan lanjut ke langkah berikutnya. Cocokkan nama database, username, password, port, serta pastikan service database sedang aktif.

## 6. Pilih mode landing page

Untuk project WhatsApp:

```dotenv
APP_NAME="Nama Landing Page"
CLIENT_ID=slug-klien
PROJECT_MODE=ctwa
PAYMENT_MODE=none
WHATSAPP_NUMBER=628123456789
```

Untuk project form:

```dotenv
APP_NAME="Nama Landing Page"
CLIENT_ID=slug-klien
PROJECT_MODE=form
PAYMENT_MODE=none
THANK_YOU_PATH=/terima-kasih
```

`CLIENT_ID` adalah label singkat untuk mengidentifikasi project, misalnya `brand-program-a`. Gunakan huruf kecil dan tanda hubung, tanpa data rahasia.

Lihat [Mode Project](07-project-modes.md) sebelum mengaktifkan payment.

## 7. Buat akun admin

```bash
php artisan pbm:create-admin
```

Isi nama, email, dan password ketika diminta. Command ini juga dapat memperbarui user dengan email yang sama menjadi admin.

Untuk automation deployment, option dapat diberikan langsung. Hindari menulis password di shell history pada komputer bersama.

```bash
php artisan pbm:create-admin --name="Admin" --email="admin@example.com" --password="password-kuat"
```

Tidak ada registrasi publik. Akun admin hanya dibuat melalui command tersebut.

## 8. Jalankan project

```bash
composer dev
```

Command ini menjalankan dua proses:

1. Laravel development server.
2. Vite development server untuk frontend.

Buka `http://localhost:8000`. Login admin tersedia di `/login`.

Jika ingin menjalankan proses secara terpisah, buka dua terminal:

```bash
php artisan serve
npm run dev
```

## 9. Kenali folder yang akan sering diedit

| Path | Fungsi |
|---|---|
| `resources/js/pages/demo/` | Halaman landing page contoh yang diganti dengan desain klien. |
| `resources/js/components/` | Komponen React, termasuk wrapper tracking. |
| `resources/css/app.css` | CSS global dan token tema. |
| `routes/web.php` | Daftar URL aplikasi. |
| `app/Analytics/` | Definisi event dan service tracking. |
| `app/Services/` | Perhitungan analytics serta integrasi. |
| `config/` | Konfigurasi yang membaca `.env`. |
| `database/migrations/` | Struktur database. |
| `docs/` | Dokumentasi penggunaan. |

## 10. Ganti halaman demo

- `PROJECT_MODE=ctwa` membuka `resources/js/pages/demo/ctwa.tsx`.
- `PROJECT_MODE=form` membuka `resources/js/pages/demo/form.tsx`.

Ganti markup dan styling sesuai desain, tetapi pertahankan wrapper `TrackedCTA`, `TrackedForm`, serta atribut `id` section agar analytics tetap bekerja. Lanjutkan ke [Memasang Tracking pada Frontend](03-frontend-wiring.md).

## 11. Data contoh dashboard

Untuk development saja:

```bash
php artisan db:seed --class=AnalyticsDemoSeeder
```

Seeder membuat data dummy agar chart dan tabel mudah diperiksa. Jangan menjalankannya pada production karena data dummy akan bercampur dengan data pengunjung nyata.

## 12. Pemeriksaan awal

- Landing page dapat dibuka tanpa error console.
- `/login` menerima akun yang dibuat.
- `/admin` dan `/admin/labs` hanya dapat dibuka setelah login admin.
- Reload landing page membuat data visit pada dashboard.
- Event internal muncul di dashboard setelah landing page dibuka.

Jika salah satu gagal, lihat [Troubleshooting](10-troubleshooting.md).
