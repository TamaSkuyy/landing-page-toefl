import type { HTMLAttributes, ReactNode } from 'react';

type Props = HTMLAttributes<HTMLElement> & { id: string; children: ReactNode };

export function TrackedSection({ id, children, ...props }: Props) {
    return (
        <section {...props} id={id}>
            {children}
        </section>
    );
}
