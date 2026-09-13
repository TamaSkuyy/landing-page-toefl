import { MousePointerClick, TrendingUp } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatNumber, formatPercent, titleCase } from '@/lib/analytics-format';
import type { CtaRow, ProjectMode } from '@/types/analytics';

export function CtaAnalysis({
    rows,
    mode,
}: {
    rows: CtaRow[];
    mode: ProjectMode;
}) {
    if (!rows.length) {
        return null;
    }

    const sources = [...new Set(rows.map((row) => row.source))];

    return (
        <section className="space-y-4">
            <div className="flex items-center gap-3">
                <MousePointerClick className="h-5 w-5 text-primary" />
                <div>
                    <h2 className="text-xl font-semibold">CTA Performance</h2>
                    <p className="text-sm text-muted-foreground">
                        Attribution by CTA zone and action
                    </p>
                </div>
            </div>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Micro-Conversion Attribution
                    </CardTitle>
                    <CardDescription>
                        Which button placements generate the most outcomes?
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-5">
                    {sources.map((source) => {
                        const items = rows
                            .filter((row) => row.source === source)
                            .sort((a, b) => b.total_leads - a.total_leads);

                        return (
                            <div key={source} className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="font-mono font-medium">
                                        {source}
                                    </span>
                                    <Badge variant="secondary">
                                        {formatNumber(
                                            items.reduce(
                                                (sum, item) =>
                                                    sum + item.total_leads,
                                                0,
                                            ),
                                        )}{' '}
                                        attributed leads
                                    </Badge>
                                </div>
                                <div className="space-y-2 rounded-lg bg-muted/50 p-3">
                                    {items.map((row, index) => (
                                        <div
                                            key={`${row.zone}-${row.action}`}
                                            className={`grid gap-2 rounded-md p-3 sm:grid-cols-[minmax(160px,1fr)_auto] sm:items-center ${index === 0 && row.total_leads > 0 ? 'bg-chart-4/15' : ''}`}
                                        >
                                            <div className="flex items-center gap-2">
                                                {index === 0 &&
                                                    row.total_leads > 0 && (
                                                        <TrendingUp className="h-3.5 w-3.5 text-chart-4" />
                                                    )}
                                                <span className="text-sm font-medium">
                                                    {titleCase(row.zone)} ·{' '}
                                                    {titleCase(row.action)}
                                                </span>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-2 text-xs">
                                                <span className="text-muted-foreground">
                                                    {formatNumber(row.sessions)}{' '}
                                                    sessions
                                                </span>
                                                <span>
                                                    {formatNumber(
                                                        row.total_leads,
                                                    )}{' '}
                                                    leads
                                                </span>
                                                {mode === 'ctwa' && (
                                                    <>
                                                        <Badge variant="outline">
                                                            {row.whatsapp_leads}{' '}
                                                            WA
                                                        </Badge>
                                                        <Badge variant="outline">
                                                            {
                                                                row.direct_checkouts
                                                            }{' '}
                                                            checkout
                                                        </Badge>
                                                    </>
                                                )}
                                                <Badge
                                                    variant={
                                                        row.lead_rate >= 50
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {formatPercent(
                                                        row.lead_rate,
                                                        2,
                                                    )}
                                                </Badge>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </CardContent>
            </Card>
        </section>
    );
}
