import type { LucideIcon } from 'lucide-react';

export function MetricCard({
    title,
    value,
    description,
    icon: Icon,
}: {
    title: string;
    value: string;
    description: string;
    icon: LucideIcon;
}) {
    return (
        <div className="analytics-metric-card group relative overflow-hidden rounded-xl border border-border/50 bg-card/30 p-6 backdrop-blur-sm transition-all duration-300 hover:border-primary/50 hover:shadow-lg hover:shadow-primary/5">
            <div className="analytics-metric-glow absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100" />
            <div className="relative flex items-start justify-between gap-4">
                <div className="min-w-0 space-y-2">
                    <p className="text-sm font-medium text-muted-foreground">
                        {title}
                    </p>
                    <p className="truncate text-2xl font-bold tracking-tight text-foreground transition-colors group-hover:text-primary">
                        {value}
                    </p>
                    <p className="text-xs leading-relaxed text-muted-foreground">
                        {description}
                    </p>
                </div>
                <div className="analytics-metric-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary transition-transform group-hover:scale-110">
                    <Icon className="h-5 w-5" />
                </div>
            </div>
        </div>
    );
}
