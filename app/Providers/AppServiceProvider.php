<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->useBuiltAssetsWhenViteIsNotRunning();
        $this->configureDefaults();
    }

    /**
     * A leftover public/hot file (Vite stopped before it could clean up, or was killed
     * mid-write) points every @vite URL at a dev server that no longer answers. The page
     * then loads without CSS or JavaScript, so no button reacts. When the file is empty
     * or the dev server is unreachable, fall back to the built manifest instead.
     */
    protected function useBuiltAssetsWhenViteIsNotRunning(): void
    {
        $hotFile = public_path('hot');

        if (! file_exists($hotFile) || ! file_exists(public_path('build/manifest.json'))) {
            return;
        }

        $devServer = trim((string) @file_get_contents($hotFile));

        if ($devServer !== '' && $this->isDevServerRunning($devServer)) {
            return;
        }

        Vite::useHotFile(storage_path('framework/vite-hot-missing'));
    }

    /**
     * Cheap TCP check so a stale hot file cannot break the rendered page.
     */
    protected function isDevServerRunning(string $url): bool
    {
        $parts = parse_url($url);
        // Keep brackets for IPv6 hosts: fsockopen("[::1]", ...) works, fsockopen("::1", ...) does not.
        $host = (string) ($parts['host'] ?? '');
        $port = (int) ($parts['port'] ?? 0);

        if ($host === '' || $port <= 0) {
            return false;
        }

        $socket = @fsockopen($host, $port, $errorCode, $errorMessage, 0.25);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
