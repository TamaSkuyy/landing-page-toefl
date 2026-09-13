import { useCallback } from 'react';
import { EVENT_TYPES } from '@/analytics/event-types';
import { track } from '@/analytics/tracker';

export function useFormTracking(formName: string) {
    const onInput = useCallback(() => {
        const key = `pbm_form_start:${formName}`;

        if (sessionStorage.getItem(key)) {
            return;
        }

        sessionStorage.setItem(key, '1');
        void track(EVENT_TYPES.formStart, { form_name: formName });
    }, [formName]);

    return { onInput };
}
