import { Clock, TrendingUp, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDuration, formatPercent } from '@/lib/analytics-format';
import type { QualityReport } from '@/types/analytics';

export function BehaviorAnalysis({ rows }: { rows: QualityReport[] }) {
    if (!rows.length) {
        return null;
    }

    return (
        <section className="space-y-4">
            <div className="flex items-center gap-3">
                <Users className="h-5 w-5 text-primary" />
                <div>
                    <h2 className="text-xl font-semibold">Behavior Analysis</h2>
                    <p className="text-sm text-muted-foreground">
                        Total Lead vs non-lead engagement
                    </p>
                </div>
            </div>
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {rows.map((row) => {
                    const gap = Math.abs(
                        row.leads.avg_scroll_depth -
                            row.non_leads.avg_scroll_depth,
                    );

                    return (
                        <Card
                            key={row.source}
                            className={gap > 30 ? 'border-chart-4/50' : ''}
                        >
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="font-mono text-sm">
                                        {row.source}
                                    </CardTitle>
                                    {gap > 30 && (
                                        <Badge
                                            variant="outline"
                                            className="border-chart-4 text-chart-4"
                                        >
                                            High gap
                                        </Badge>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                <div>
                                    <p className="mb-2 flex items-center gap-1 text-sm text-muted-foreground">
                                        <TrendingUp className="h-3.5 w-3.5" />
                                        Scroll depth
                                    </p>
                                    <MetricBar
                                        label={`Leads (${row.leads.count})`}
                                        value={row.leads.avg_scroll_depth}
                                        primary
                                    />
                                    <MetricBar
                                        label={`Others (${row.non_leads.count})`}
                                        value={row.non_leads.avg_scroll_depth}
                                    />
                                </div>
                                <div>
                                    <p className="mb-2 flex items-center gap-1 text-sm text-muted-foreground">
                                        <Clock className="h-3.5 w-3.5" />
                                        Dwell time
                                    </p>
                                    <div className="flex items-center justify-around text-center">
                                        <div>
                                            <p className="text-lg font-bold text-primary">
                                                {formatDuration(
                                                    row.leads.avg_dwell_time,
                                                )}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Leads
                                            </p>
                                        </div>
                                        <span className="text-muted-foreground">
                                            vs
                                        </span>
                                        <div>
                                            <p className="text-lg font-bold">
                                                {formatDuration(
                                                    row.non_leads
                                                        .avg_dwell_time,
                                                )}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Others
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        </section>
    );
}

function MetricBar({
    label,
    value,
    primary = false,
}: {
    label: string;
    value: number;
    primary?: boolean;
}) {
    return (
        <div className="mb-2">
            <div className="mb-1 flex justify-between text-xs">
                <span
                    className={
                        primary ? 'text-primary' : 'text-muted-foreground'
                    }
                >
                    {label}
                </span>
                <span>{formatPercent(value)}</span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-muted">
                <div
                    className={`h-full rounded-full ${primary ? 'bg-primary' : 'bg-muted-foreground'}`}
                    style={{ width: `${Math.min(value, 100)}%` }}
                />
            </div>
        </div>
    );
}
