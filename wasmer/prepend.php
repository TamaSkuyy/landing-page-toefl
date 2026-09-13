<?php

/**
 * Dijalankan otomatis di setiap request lewat `auto_prepend_file` (lihat wasmer/php.ini).
 *
 * Direktori aplikasi di Wasmer Edge bersifat sementara dan sebagian read-only, sedangkan
 * Laravel menulis view terkompilasi, cache, session, dan log ke `storage/`. Di sini path
 * tulis itu diarahkan ke direktori sementara yang bisa ditulis, dan session/cache diarahkan
 * ke database supaya tidak hilang saat instance didaur ulang.
 *
 * Berkas ini hanya terpakai di Wasmer (via PHPRC) dan tidak memengaruhi deployment lain.
 */
$temporary = null;

foreach (['/tmp', sys_get_temp_dir()] as $candidate) {
    if (is_string($candidate) && $candidate !== '' && is_dir($candidate) && is_writable($candidate)) {
        $temporary = rtrim($candidate, '/').'/pbm-storage';
        break;
    }
}

if ($temporary !== null) {
    foreach (['framework/views', 'framework/cache/data', 'framework/sessions', 'logs'] as $directory) {
        is_dir("{$temporary}/{$directory}") || @mkdir("{$temporary}/{$directory}", 0777, true);
    }

    $variables = [
        'VIEW_COMPILED_PATH' => "{$temporary}/framework/views",
        'APP_CONFIG_CACHE' => "{$temporary}/config.php",
        'APP_EVENTS_CACHE' => "{$temporary}/events.php",
        'APP_PACKAGES_CACHE' => "{$temporary}/packages.php",
        'APP_ROUTES_CACHE' => "{$temporary}/routes-v7.php",
        'APP_SERVICES_CACHE' => "{$temporary}/services.php",
    ];

    foreach ($variables as $key => $value) {
        // Tiga-tiganya diisi karena env() membaca $_ENV/$_SERVER, bukan hanya putenv().
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    $defaults = [
        // Log ke stderr: tidak ada penulisan file di filesystem yang tidak persisten.
        'LOG_CHANNEL' => 'stderr',
        // Session & cache di database (tabel `sessions` dan `cache` sudah ada di migration).
        'SESSION_DRIVER' => 'database',
        'CACHE_STORE' => 'database',
    ];

    foreach ($defaults as $key => $value) {
        // Hormati nilai yang sudah diset lewat secrets Wasmer.
        if (empty($_ENV[$key]) && empty($_SERVER[$key])) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
