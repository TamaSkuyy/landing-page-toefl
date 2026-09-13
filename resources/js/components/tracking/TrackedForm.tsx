import { usePage } from '@inertiajs/react';
import type { FormEvent, FormHTMLAttributes, ReactNode } from 'react';
import { useState } from 'react';
import { EVENT_TYPES } from '@/analytics/event-types';
import { landingSource, trackServerConfirmed } from '@/analytics/tracker';
import { useFormTracking } from '@/hooks/use-form-tracking';
import type { TrackingProps } from '@/types/analytics';

export type LeadResponse = {
    lead_id: number;
    redirect_url: string;
    event_id: string;
};

type Props = Omit<
    FormHTMLAttributes<HTMLFormElement>,
    'onSubmit' | 'onInput' | 'onError'
> & {
    formName: string;
    children: ReactNode;
    endpoint?: string;
    onSuccess?: (response: LeadResponse) => void;
    onError?: (message: string) => void;
};

export function TrackedForm({
    formName,
    endpoint = '/lead',
    onSuccess,
    onError,
    children,
    ...props
}: Props) {
    const { onInput } = useFormTracking(formName);
    const [submitting, setSubmitting] = useState(false);
    const tracking = usePage().props.tracking as TrackingProps;

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (submitting) {
            return;
        }

        setSubmitting(true);

        const form = new FormData(event.currentTarget);
        const metaEventId =
            window.crypto?.randomUUID?.() ??
            `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        const fields = Object.fromEntries(form.entries());
        const params = new URLSearchParams(window.location.search);
        const standard = ['name', 'email', 'phone'];
        const extra = Object.fromEntries(
            Object.entries(fields).filter(([key]) => !standard.includes(key)),
        );
        const payload = {
            name: fields.name,
            email: fields.email,
            phone: fields.phone,
            extra,
            landing_source: landingSource(),
            meta_event_id: metaEventId,
            utm_source: params.get('utm_source'),
            utm_medium: params.get('utm_medium'),
            utm_campaign: params.get('utm_campaign'),
            utm_content: params.get('utm_content'),
            utm_term: params.get('utm_term'),
        };
        const csrf =
            document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                ?.content ?? '';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ ...payload, form_name: formName }),
            });
            const body = await response.json();

            if (!response.ok) {
                throw new Error(body.message ?? 'Form submission failed.');
            }

            const lead = body as LeadResponse;
            trackServerConfirmed(EVENT_TYPES.lead, lead.event_id, {
                landing_source: landingSource(),
            });

            if (tracking.paymentMode === 'internal') {
                const checkout = await fetch(lead.redirect_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ lead_id: lead.lead_id }),
                });
                const order = await checkout.json();

                if (!checkout.ok) {
                    throw new Error(order.message ?? 'Checkout failed.');
                }

                window.location.assign(order.payment_url);

                return;
            }

            onSuccess?.(lead);
        } catch (error) {
            onError?.(
                error instanceof Error
                    ? error.message
                    : 'Form submission failed.',
            );
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <form
            {...props}
            aria-busy={submitting}
            onInput={onInput}
            onSubmit={submit}
        >
            {children}
        </form>
    );
}
