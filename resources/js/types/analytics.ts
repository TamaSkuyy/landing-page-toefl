import type { EventType, ProjectMode } from '@/analytics/event-types';

export type { ProjectMode } from '@/analytics/event-types';

export type EventData = {
    event_id?: string;
    landing_source?: string;
    zone?: string;
    action?: string;
    cta_label?: string;
    depth?: number;
    section?: string;
    value?: number;
    currency?: string;
    [key: string]: unknown;
};

export type TrackingProps = {
    enabled: boolean;
    mode: ProjectMode;
    pageUrl: string;
    paymentMode: 'none' | 'external' | 'internal';
    visitorId: string;
    eventLabels: Partial<Record<EventType, string>>;
    capabilities: Record<string, boolean>;
    engagementThreshold: number;
    heartbeatInterval: number;
    sectionViewEnabled: boolean;
    metaEvents: Partial<Record<EventType, string>>;
};

export type QueuedEvent = {
    event_type: EventType;
    event_data: EventData;
};

export type AnalyticsStats = {
    page_views: number;
    visits: number;
    engagements: number;
    engagement_rate: number;
    bounces: number;
    bounce_rate: number;
    intents: number;
    intent_rate: number;
    whatsapp_leads: number;
    whatsapp_rate: number;
    direct_checkouts: number;
    direct_checkout_rate: number;
    form_starts: number;
    form_start_rate: number;
    total_leads: number;
    lead_cr: number;
    payments: number;
    sales_cr: number;
    lead_to_payment_rate: number;
    revenue: number;
    rpv: number;
};

export type FunnelStage = {
    event: string;
    label: string;
    value: number;
    percentage: number;
    transition_pct: number | null;
    from_event: string | null;
    branch: string;
};

export type ReferralRow = { source: string; visits: number };

export type AnalyticsInsights = {
    top_referral: ReferralRow;
    primary_channel: string;
    primary_channel_value: number;
    lead_cr: number;
    rpv: number;
};

export type AnalyticsPageProps = {
    mode: ProjectMode;
    stats: AnalyticsStats;
    daily: Array<Record<string, number | string>>;
    referrals: ReferralRow[];
    funnel: FunnelStage[];
    insights: AnalyticsInsights;
    range: number;
    retentionDays: number;
};

export type PerformanceRow = {
    source: string;
    visits: number;
    engagements: number;
    engagement_rate: number;
    bounces: number;
    bounce_rate: number;
    intents: number;
    intent_rate: number;
    total_leads: number;
    lead_cr: number;
    eligible: boolean;
    whatsapp_leads?: number;
    whatsapp_rate?: number;
    direct_checkouts?: number;
    direct_checkout_rate?: number;
    form_starts?: number;
    form_start_rate?: number;
    payments?: number;
    sales_cr?: number;
    revenue?: number;
    rpv?: number;
};

export type FunnelReport = { source: string; stages: FunnelStage[] };

export type DeviceRow = {
    source: string;
    device: string;
    visits: number;
    total_leads: number;
    conversion_rate: number;
    whatsapp_leads: number;
    direct_checkouts: number;
};

export type CtaRow = {
    source: string;
    zone: string;
    action: string;
    clicks: number;
    sessions: number;
    total_leads: number;
    lead_rate: number;
    whatsapp_leads: number;
    direct_checkouts: number;
};

export type PersonaReport = {
    source: string;
    total_sessions: number;
    segments: Array<{
        name: string;
        count: number;
        percentage: number;
    }>;
};

export type ScrollHeatmapReport = {
    source: string;
    total_visits: number;
    depths: Array<{ depth: number; sessions: number; percentage: number }>;
};

export type SectionHeatmapReport = {
    source: string;
    sections: Array<{
        section: string;
        sessions: number;
        percentage: number;
        drop_from_previous: number;
    }>;
};

export type QualityMetrics = {
    count: number;
    avg_scroll_depth: number;
    avg_dwell_time: number;
};

export type QualityReport = {
    source: string;
    leads: QualityMetrics;
    non_leads: QualityMetrics;
};

export type LabsFilters = {
    range: string;
    source: string | null;
    start_date: string;
    end_date: string;
};

export type LabsPageProps = {
    performance: PerformanceRow[];
    funnel: FunnelReport[];
    devices: DeviceRow[];
    ctas: CtaRow[];
    personas: PersonaReport[];
    scroll_heatmap: ScrollHeatmapReport[];
    section_heatmap: SectionHeatmapReport[];
    quality: QualityReport[];
    availableSources: string[];
    filters: LabsFilters;
    minimumWinnerVisits: number;
    primaryMetric: string;
    retentionDays: number;
};
