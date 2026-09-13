import { useEffect } from 'react';
import { EVENT_TYPES } from '@/analytics/event-types';
import {
    getMaximumScrollDepth,
    sendHeartbeat,
    track,
} from '@/analytics/tracker';
import type { TrackingProps } from '@/types/analytics';

export function useEngagement(config: TrackingProps): void {
    useEffect(() => {
        if (!config.enabled) {
            return;
        }

        let activeSeconds = 0;
        const engagementKey = 'pbm_engagement_sent';

        const tick = () => {
            if (document.visibilityState !== 'visible') {
                return;
            }

            activeSeconds += 1;

            if (
                activeSeconds >= config.engagementThreshold &&
                !sessionStorage.getItem(engagementKey)
            ) {
                sessionStorage.setItem(engagementKey, '1');
                void track(EVENT_TYPES.engagement, {
                    duration_seconds: activeSeconds,
                });
            }
        };

        const heartbeat = () =>
            sendHeartbeat(activeSeconds, getMaximumScrollDepth());
        const activeTimer = window.setInterval(tick, 1_000);
        const heartbeatTimer = window.setInterval(
            heartbeat,
            config.heartbeatInterval * 1_000,
        );
        window.addEventListener('pagehide', heartbeat);

        return () => {
            window.clearInterval(activeTimer);
            window.clearInterval(heartbeatTimer);
            window.removeEventListener('pagehide', heartbeat);
        };
    }, [config.enabled, config.engagementThreshold, config.heartbeatInterval]);
}
