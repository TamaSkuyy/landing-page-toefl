<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/**
 * Entry point untuk Vercel (runtime `vercel-php`).
 *
 * Di Vercel seluruh deployment bersifat read-only kecuali /tmp, dan /tmp hanya bertahan
 * selama container hidup. Karena itu semua path yang biasa ditulis Laravel diarahkan ke
 * /tmp SEBELUM framework boot — kalau tidak, request pertama akan gagal dengan error
 * "failed to open stream: Read-only file system" (lihat juga issue vercel-community/php #140
 * soal laravel.log yang tidak bisa dibuka).
 *
 * Runtime state (session, cache) tetap harus memakai database, bukan file:
 *   SESSION_DRIVER=database  CACHE_STORE=database
 * Keduanya memakai tabel `sessions` dan `cache` yang sudah ada di migration.
 *
 * File ini tidak dipakai pada deployment biasa (VPS/shared hosting) — di sana document root
 * tetap `public/` dan `public/index.php` yang bekerja.
 */

/*
 * Lapisan file statis.
 *
 * Di Vercel, aset di public/ (mis. /assets/** dan /build/**) dilayani platform sebelum
 * request sampai ke function ini, jadi blok di bawah hampir tidak pernah terpakai di sana.
 * Gunanya untuk menjalankan runtime ini secara lokal seperti yang didokumentasikan
 * (`php -S localhost:8000 api/index.php`): tanpa blok ini, router script membuat SEMUA
 * request — termasuk aset — masuk ke Laravel, sehingga halaman tampak tanpa CSS/JS.
 *
 * Berkas .php sengaja tidak dilayani supaya source code (mis. public/index.php) tidak
 * pernah bisa diunduh sebagai teks biasa.
 */
if (PHP_SAPI === 'cli-server') {
    $publicDirectory = realpath(__DIR__.'/../public');
    $requestedPath = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    $candidate = $publicDirectory !== false ? realpath($publicDirectory.$requestedPath) : false;

    $isInsidePublic = $candidate !== false
        && $publicDirectory !== false
        && str_starts_with($candidate, $publicDirectory.DIRECTORY_SEPARATOR)
        && is_file($candidate);

    if ($isInsidePublic && ! str_ends_with(strtolower($candidate), '.php')) {
        // Kalau docroot-nya memang public/, biarkan server bawaan yang mengirim berkasnya.
        if (isset($_SERVER['DOCUMENT_ROOT']) && realpath($_SERVER['DOCUMENT_ROOT']) === $publicDirectory) {
            return false;
        }

        header('Content-Type: '.(mime_content_type($candidate) ?: 'application/octet-stream'));
        header('Content-Length: '.(string) filesize($candidate));
        readfile($candidate);

        return;
    }
}

$storage = '/tmp/pbm-storage';
$directories = [
    "{$storage}/framework/views",
    "{$storage}/framework/cache/data",
    "{$storage}/framework/sessions",
    "{$storage}/logs",
    "{$storage}/bootstrap/cache",
];

foreach ($directories as $directory) {
    if (! is_dir($directory)) {
        @mkdir($directory, 0777, true);
    }
}

/**
 * Laravel membaca path ini lewat `env()`, dan `env()` membaca $_ENV/$_SERVER — bukan hanya
 * putenv(). Jadi ketiganya diisi agar nilainya pasti terbaca.
 *
 * @var array<string, string> $paths
 */
$paths = [
    'VIEW_COMPILED_PATH' => "{$storage}/framework/views",
    'APP_CONFIG_CACHE' => "{$storage}/bootstrap/cache/config.php",
    'APP_EVENTS_CACHE' => "{$storage}/bootstrap/cache/events.php",
    'APP_PACKAGES_CACHE' => "{$storage}/bootstrap/cache/packages.php",
    'APP_ROUTES_CACHE' => "{$storage}/bootstrap/cache/routes-v7.php",
    'APP_SERVICES_CACHE' => "{$storage}/bootstrap/cache/services.php",
];

foreach ($paths as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// Log ke stderr (channel bawaan Laravel) supaya tidak ada penulisan file dan tetap muncul
// di dashboard Vercel. Bisa ditimpa lewat environment variable LOG_CHANNEL.
if (empty($_ENV['LOG_CHANNEL']) && empty($_SERVER['LOG_CHANNEL'])) {
    putenv('LOG_CHANNEL=stderr');
    $_ENV['LOG_CHANNEL'] = 'stderr';
    $_SERVER['LOG_CHANNEL'] = 'stderr';
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
