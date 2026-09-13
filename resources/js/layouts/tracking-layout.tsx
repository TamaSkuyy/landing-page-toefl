import type { PropsWithChildren } from 'react';
import { AnalyticsBootstrap } from '@/hooks/use-analytics';
import type { TrackingProps } from '@/types/analytics';

type TrackingLayoutProps = PropsWithChildren<{
    tracking: TrackingProps;
}>;

export default function TrackingLayout({
    children,
    tracking,
}: TrackingLayoutProps) {
    return (
        <>
            <AnalyticsBootstrap tracking={tracking} />
            {children}
        </>
    );
}
