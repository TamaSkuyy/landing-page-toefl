<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/favicon.ico" sizes="any">

    {{-- Landing page typeface (same source as the design reference). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300..900&display=swap" rel="stylesheet">

    <script>
        window.__META_PAGE_VIEW_EVENT_ID = window.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        window.__PBM_META_EVENTS = @js(app(\App\Analytics\MetaEventMapper::class)->forMode(config('analytics.mode')));
    </script>

    @if (filled(config('meta.pixel_id')))
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=true;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=true;
            t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', @js(config('meta.pixel_id')));
        </script>
    @endif

    @if ($gtmId = config('integrations.gtm_container_id'))
        <script>
            window.dataLayer = window.dataLayer || [];
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':Date.now(),event:'gtm.js'});
            var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
            j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@js($gtmId));
        </script>
    @elseif ($ga4Id = config('integrations.ga4_measurement_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($ga4Id) }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments)}
            gtag('js', new Date());
            gtag('config', @js($ga4Id));
        </script>
    @endif

    @if ($clarityId = config('integrations.clarity_project_id'))
        <script>
            (function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,'clarity','script',@js($clarityId));
            const pbmLandingSource = sessionStorage.getItem('pbm_landing_source') || location.pathname;
            clarity('set', 'landing_source', pbmLandingSource);
            clarity('identify', @js(request()->attributes->get('pbm_visitor_id')));
        </script>
    @endif

    <script>
        (() => {
            const appearance = @js($appearance ?? 'system');
            if (appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @if (file_exists(public_path('hot')))
        {{-- Pemulihan otomatis mode dev: kalau modul dari Vite dev server gagal dimuat —
             misalnya server baru restart sehingga cache transformnya masih dingin, atau
             halaman di-reload sebelum kompilasi selesai — muat ulang sekali supaya halaman
             tidak berakhir sebagai halaman tampil-tapi-mati (semua handler belum terpasang).
             Dibungkus @verbatim supaya tidak ada string di dalam JS yang dikompilasi Blade. --}}
        @verbatim
        <script>
            (() => {
                window.addEventListener('error', (e) => {
                    const el = e.target || {};
                    if (el.tagName !== 'SCRIPT') return;

                    const src = String(el.src || '');
                    // Hanya modul dari dev server (origin-nya beda dari halaman ini).
                    if (!src || src.startsWith(location.origin) || sessionStorage.getItem('vite-reload-tried')) return;

                    sessionStorage.setItem('vite-reload-tried', '1');
                    setTimeout(() => location.reload(), 700);
                }, true);

                // Setelah React benar-benar mount, izinkan percobaan pemulihan berikutnya.
                const timer = setInterval(() => {
                    const app = document.querySelector('#app');
                    const mounted = !!app && Object.keys(app).some((k) => k.startsWith('__reactContainer') || k.startsWith('__reactFiber'));
                    if (mounted) {
                        sessionStorage.removeItem('vite-reload-tried');
                        clearInterval(timer);
                    }
                }, 1000);
            })();
        </script>
        @endverbatim
    @endif

    @fonts
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    <x-inertia::head>
        <title>{{ config('app.name', 'PBM Landing Page Boilerplate') }}</title>
    </x-inertia::head>
</head>
<body class="font-sans antialiased">
    @if ($gtmId = config('integrations.gtm_container_id'))
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode($gtmId) }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    <x-inertia::app />
</body>
</html>
