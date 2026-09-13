export const EVENT_TYPES = {
    visit: 'visit',
    engagement: 'engagement',
    intent: 'intent',
    directCheckout: 'direct_checkout',
    whatsappLead: 'whatsapp_lead',
    formStart: 'form_start',
    lead: 'lead',
    payment: 'payment',
    scroll: 'scroll',
    sectionView: 'section_view',
} as const;

export type EventType = (typeof EVENT_TYPES)[keyof typeof EVENT_TYPES];
export type ProjectMode = 'ctwa' | 'form';
export type CtaZone =
    | 'hero'
    | 'pricing'
    | 'sticky'
    | 'floating'
    | 'footer'
    | 'midpage'
    | 'faq'
    | 'nav';
export type CtaAction =
    | 'whatsapp'
    | 'external_checkout'
    | 'form_anchor'
    | 'internal_checkout'
    | 'scroll'
    | 'link';

const criticalEvents = new Set<EventType>([
    EVENT_TYPES.whatsappLead,
    EVENT_TYPES.directCheckout,
    EVENT_TYPES.lead,
]);

export function isCriticalEvent(event: EventType): boolean {
    return criticalEvents.has(event);
}

export function resolveCtaEvent(
    mode: ProjectMode,
    action: CtaAction,
): EventType {
    if (mode === 'ctwa' && action === 'whatsapp') {
        return EVENT_TYPES.whatsappLead;
    }

    if (mode === 'ctwa' && action === 'external_checkout') {
        return EVENT_TYPES.directCheckout;
    }

    return EVENT_TYPES.intent;
}
