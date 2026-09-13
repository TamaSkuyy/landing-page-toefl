<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side HTML cache for the public landing pages.
 */
class CacheLandingPage
{
    // Caches the page for 7 days
    private const TTL_SECONDS = 604800;

    public function handle(Request $request, Closure $next): Response
    {
        $isLandingPage = $request->routeIs('home', 'demo.*');

        if (! $request->isMethod('GET') || $request->user() || ! $isLandingPage) {
            return $next($request);
        }

        // While `composer dev` runs (public/hot exists) the markup embeds the Vite dev
        // server URL and changes on every edit, so caching it only produces stale pages.
        if (file_exists(public_path('hot'))) {
            return $next($request);
        }

        $pathKey = str_replace('/', '_', $request->path());
        $cacheKey = 'landing_page_html_'.config('analytics.mode')."_{$pathKey}:".self::manifestVersion();

        if (Cache::has($cacheKey)) {
            /** @var string $html */
            $html = Cache::get($cacheKey);

            return response(self::freshCsrfToken($html), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        /** @var Response $response */
        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            Cache::put($cacheKey, $response->getContent(), self::TTL_SECONDS);
        }

        return $response;
    }

    /**
     * Ganti token CSRF di HTML yang diambil dari cache dengan token sesi yang sedang dilayani.
     *
     * Token CSRF terikat ke session, sedangkan HTML landing dipakai bersama banyak session.
     * Tanpa ini, pengunjung kedua dan seterusnya menerima token milik session pertama sehingga
     * semua request `/analytics/track` (dan `/analytics/heartbeat`) ditolak 419 — dashboard
     * analytics jadi kosong walaupun trafiknya ada.
     */
    private static function freshCsrfToken(string $html): string
    {
        return preg_replace(
            '/<meta name="csrf-token" content="[^"]*">/',
            '<meta name="csrf-token" content="'.e(csrf_token()).'">',
            $html,
            1,
        ) ?? $html;
    }

    private static function manifestVersion(): string
    {
        $manifest = public_path('build/manifest.json');

        if (file_exists($manifest)) {
            return (string) filemtime($manifest);
        }

        return 'dev';
    }
}
