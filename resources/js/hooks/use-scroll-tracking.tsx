import { useEffect } from 'react';
import { EVENT_TYPES } from '@/analytics/event-types';
import { landingSource, rememberScrollDepth, track } from '@/analytics/tracker';

const milestones = [25, 50, 75, 90] as const;

export function useScrollTracking(enabled: boolean): void {
    useEffect(() => {
        if (!enabled) {
            return;
        }

        let timer: number | undefined;
        const source = landingSource();

        const measure = () => {
            timer = undefined;
            const available =
                document.documentElement.scrollHeight - window.innerHeight;
            const depth =
                available <= 0
                    ? 100
                    : Math.round((window.scrollY / available) * 100);
            rememberScrollDepth(depth);

            for (const milestone of milestones) {
                const key = `pbm_scroll:${source}:${milestone}`;

                if (depth >= milestone && !sessionStorage.getItem(key)) {
                    sessionStorage.setItem(key, '1');
                    void track(EVENT_TYPES.scroll, { depth: milestone });
                }
            }
        };

        const onScroll = () => {
            if (timer) {
                return;
            }

            timer = window.setTimeout(measure, 200);
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        measure();

        return () => {
            window.removeEventListener('scroll', onScroll);

            if (timer) {
                window.clearTimeout(timer);
            }
        };
    }, [enabled]);
}
