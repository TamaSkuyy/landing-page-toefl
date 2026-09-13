import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';

import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import AuthLayout from '@/layouts/auth-layout';
import TrackingLayout from '@/layouts/tracking-layout';

const appName = import.meta.env.VITE_APP_NAME || 'PBM Landing Page';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        // Must match resources/js/app.tsx; a different <title> between the server and
        // client render makes React fail hydration and re-render the whole page.
        title: (title) => (title ? `${title}` : appName),
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob('./pages/**/*.tsx'),
            ) as any,
        layout: (name) => {
            switch (true) {
                case name.startsWith('auth/'):
                    return [TrackingLayout, AuthLayout];
                default:
                    return TrackingLayout;
            }
        },
        setup: ({ App, props }) => {
            return (
                <TooltipProvider delayDuration={0}>
                    <App {...props} />
                    <Toaster />
                </TooltipProvider>
            );
        },
    }),
);
