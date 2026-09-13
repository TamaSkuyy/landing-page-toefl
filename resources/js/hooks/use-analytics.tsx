import { usePage } from '@inertiajs/react';
import { useCallback, useEffect } from 'react';
import { EVENT_TYPES } from '@/analytics/event-types';
import type { EventType } from '@/analytics/event-types';
import {
    flushTracking,
    landingSource,
    track,
    trackVisit,
} from '@/analytics/tracker';
import { useEngagement } from '@/hooks/use-engagement';
import { useScrollTracking } from '@/hooks/use-scroll-tracking';
import { useSectionTracking } from '@/hooks/use-section-tracking';
import type { EventData, TrackingProps } from '@/types/analytics';

const pendingVisitKeys = new Set<string>();

export function useAnalytics() {
    const tracking = usePage().props.tracking as TrackingProps;
    const send = useCallback(
        (eventType: EventType, data: EventData = {}) => {
            if (!tracking.enabled) {
                return Promise.resolve();
            }

            return track(eventType, data);
        },
        [tracking.enabled],
    );

    return { track: send, tracking };
}

function useVisitTracking(enabled: boolean, pageUrl: string): void {
    useEffect(() => {
        if (!enabled) {
            return;
        }

        const source = landingSource();
        const key = `pbm_visit:${source}`;

        if (sessionStorage.getItem(key) || pendingVisitKeys.has(key)) {
            return;
        }

        pendingVisitKeys.add(key);
        sessionStorage.setItem(key, '1');
        void trackVisit({ landing_source: source }).then((delivered) => {
            pendingVisitKeys.delete(key);

            if (!delivered) {
                sessionStorage.removeItem(key);
            }
        });
    }, [enabled, pageUrl]);
}

export function AnalyticsBootstrap({ tracking }: { tracking: TrackingProps }) {
    useVisitTracking(tracking.enabled, tracking.pageUrl);
    useEngagement(tracking);
    useScrollTracking(tracking.enabled);
    useSectionTracking(tracking.enabled && tracking.sectionViewEnabled);

    useEffect(() => {
        const flush = () => flushTracking();
        const visibility = () =>
            document.visibilityState === 'hidden' && flush();
        window.addEventListener('pagehide', flush);
        document.addEventListener('visibilitychange', visibility);

        return () => {
            window.removeEventListener('pagehide', flush);
            document.removeEventListener('visibilitychange', visibility);
        };
    }, []);

    return null;
}

export { EVENT_TYPES };
