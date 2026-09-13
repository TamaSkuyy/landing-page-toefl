<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AttachVisitorCookie;
use App\Http\Middleware\CacheLandingPage;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->validateCsrfTokens(except: ['payment/callback']);

        $middleware->web(append: [
            AttachVisitorCookie::class,
            CacheLandingPage::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);

        /*
         * Di belakang proxy (Vercel, load balancer, Cloudflare Tunnel) Laravel perlu tahu
         * bahwa request aslinya HTTPS supaya URL dan cookie yang dihasilkan benar. Opt-in
         * lewat environment agar development lokal tetap tidak mempercayai header
         * X-Forwarded-* dari mana pun. Contoh untuk Vercel: TRUSTED_PROXIES=*
         */
        $proxies = env('TRUSTED_PROXIES');

        if (is_string($proxies) && $proxies !== '') {
            $middleware->trustProxies(
                at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)),
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('analytics/*') || $request->expectsJson(),
        );
    })->create();
