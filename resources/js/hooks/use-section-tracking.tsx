import { useEffect } from 'react';
import { EVENT_TYPES } from '@/analytics/event-types';
import { landingSource, track } from '@/analytics/tracker';

export function useSectionTracking(enabled: boolean): void {
    useEffect(() => {
        if (!enabled) {
            return;
        }

        const observed = new WeakSet<Element>();
        const timers = new Map<Element, number>();
        const source = landingSource();

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    const id = entry.target.id;
                    const key = `pbm_section:${source}:${id}`;

                    if (
                        entry.isIntersecting &&
                        entry.intersectionRatio >= 0.2 &&
                        !sessionStorage.getItem(key)
                    ) {
                        if (!timers.has(entry.target)) {
                            timers.set(
                                entry.target,
                                window.setTimeout(() => {
                                    if (!sessionStorage.getItem(key)) {
                                        sessionStorage.setItem(key, '1');
                                        void track(EVENT_TYPES.sectionView, {
                                            section: id,
                                        });
                                    }

                                    timers.delete(entry.target);
                                }, 500),
                            );
                        }
                    } else {
                        const timer = timers.get(entry.target);

                        if (timer) {
                            window.clearTimeout(timer);
                        }

                        timers.delete(entry.target);
                    }
                }
            },
            { threshold: [0.2] },
        );

        const discover = (root: ParentNode = document) => {
            root.querySelectorAll('section[id]').forEach((section) => {
                if (!observed.has(section)) {
                    observed.add(section);
                    observer.observe(section);
                }
            });
        };

        discover();
        const mutations = new MutationObserver(() => discover());
        mutations.observe(document.body, { childList: true, subtree: true });

        return () => {
            mutations.disconnect();
            observer.disconnect();
            timers.forEach((timer) => window.clearTimeout(timer));
        };
    }, [enabled]);
}
