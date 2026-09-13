import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AuthLayout from '@/layouts/auth-layout';
import TrackingLayout from '@/layouts/tracking-layout';

const appName = import.meta.env.VITE_APP_NAME || 'PBM Landing Page';

createInertiaApp({
    title: (title) => (title ? `${title}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return [TrackingLayout, AuthLayout];
            default:
                return TrackingLayout;
        }
    },
    strictMode: true,
    // Keep this tree identical to resources/js/ssr.tsx. Any difference (a <Suspense>
    // wrapper or a lazily imported layout) makes React discard the server-rendered
    // HTML and re-render on the client, which breaks hydration and event handlers.
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
