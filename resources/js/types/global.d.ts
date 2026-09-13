import type { EventType } from '@/analytics/event-types';
import type { TrackingProps } from '@/types/analytics';
import type { Auth } from '@/types/auth';

declare global {
    interface Window {
        fbq?: (...args: unknown[]) => void;
        __META_PAGE_VIEW_EVENT_ID?: string;
        __PBM_META_EVENTS?: Partial<Record<EventType, string>>;
        dataLayer?: Record<string, unknown>[];
    }
}

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            tracking: TrackingProps;
            [key: string]: unknown;
        };
    }
}
