import { ArrowDown } from 'lucide-react';
import { formatNumber, formatPercent } from '@/lib/analytics-format';
import type { FunnelStage } from '@/types/analytics';

const gradients = [
    'from-sky-500 to-blue-500',
    'from-blue-500 to-violet-500',
    'from-violet-500 to-purple-500',
    'from-purple-500 to-fuchsia-500',
    'from-fuchsia-500 to-pink-500',
    'from-pink-500 to-rose-500',
];

function Transition({ value }: { value: number | null }) {
    if (value === null) {
        return null;
    }

    const color =
        value >= 70
            ? 'bg-emerald-500/10 text-emerald-500'
            : value >= 40
              ? 'bg-amber-500/10 text-amber-500'
              : 'bg-destructive/10 text-destructive';

    return (
        <div className="flex items-center justify-center gap-2 py-2">
            <ArrowDown className="h-3.5 w-3.5 text-muted-foreground" />
            <span
                className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${color}`}
            >
                {formatPercent(value)} retained
            </span>
        </div>
    );
}

function Stage({ stage, index }: { stage: FunnelStage; index: number }) {
    return (
        <div>
            <div className="mb-2 flex flex-wrap items-end justify-between gap-2 text-sm">
                <div>
                    <span className="font-medium text-foreground">
                        {stage.label}
                    </span>
                    {stage.from_event && (
                        <span className="ml-2 text-xs text-muted-foreground">
                            from {stage.from_event.replaceAll('_', ' ')}
                        </span>
                    )}
                </div>
                <div className="flex items-baseline gap-2">
                    <span className="font-semibold text-foreground">
                        {formatNumber(stage.value)}
                    </span>
                    <span className="text-xs text-muted-foreground">
                        {formatPercent(stage.percentage)} of visits
                    </span>
                </div>
            </div>
            <div className="h-3 overflow-hidden rounded-full bg-muted">
                <div
                    className={`h-full min-w-[2%] rounded-full bg-gradient-to-r ${gradients[index % gradients.length]} transition-all duration-500`}
                    style={{ width: `${Math.min(stage.percentage, 100)}%` }}
                />
            </div>
        </div>
    );
}

export function ConversionFunnel({ stages }: { stages: FunnelStage[] }) {
    const main = stages.filter((stage) => stage.branch === 'main');
    const branches = stages.filter((stage) => stage.branch !== 'main');

    return (
        <section className="analytics-panel rounded-xl border border-border/50 bg-card/30 p-6 backdrop-blur-sm transition hover:border-primary/30">
            <div className="mb-6">
                <h2 className="text-lg font-semibold text-foreground">
                    Conversion Funnel
                </h2>
                <p className="text-sm text-muted-foreground">
                    Strict journey: setiap tahap hanya menghitung sesi yang
                    melewati tahap sebelumnya.
                </p>
            </div>
            <div className="space-y-1">
                {main.map((stage, index) => (
                    <div key={stage.event}>
                        <Transition value={stage.transition_pct} />
                        <Stage stage={stage} index={index} />
                    </div>
                ))}
            </div>
            {branches.length > 0 && (
                <div className="mt-5 border-t border-border/50 pt-5">
                    <p className="mb-3 text-center text-xs font-medium tracking-wider text-muted-foreground uppercase">
                        CTWA outcome branches
                    </p>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {branches.map((stage, index) => (
                            <div
                                key={stage.event}
                                className="analytics-subpanel rounded-lg border border-border/50 bg-muted/20 p-4"
                            >
                                <Transition value={stage.transition_pct} />
                                <Stage
                                    stage={stage}
                                    index={index + main.length}
                                />
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </section>
    );
}
