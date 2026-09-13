import { AlertTriangle, Monitor, Smartphone, Tablet } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatNumber, formatPercent, titleCase } from '@/lib/analytics-format';
import type { DeviceRow, ProjectMode } from '@/types/analytics';

const DeviceIcon = ({ device }: { device: string }) => {
    if (device === 'mobile') {
        return <Smartphone className="h-4 w-4" />;
    }

    if (device === 'tablet') {
        return <Tablet className="h-4 w-4" />;
    }

    return <Monitor className="h-4 w-4" />;
};

export function DeviceComparison({
    rows,
    mode,
}: {
    rows: DeviceRow[];
    mode: ProjectMode;
}) {
    const sources = [...new Set(rows.map((row) => row.source))];

    if (!rows.length) {
        return null;
    }

    return (
        <section className="space-y-4">
            <div className="flex items-center gap-3">
                <Smartphone className="h-5 w-5 text-primary" />
                <div>
                    <h2 className="text-xl font-semibold">
                        Device Performance
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Lead conversion by device type
                    </p>
                </div>
            </div>
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {sources.map((source) => {
                    const devices = rows.filter((row) => row.source === source);
                    const best = Math.max(
                        ...devices.map((row) => row.conversion_rate),
                        0.01,
                    );
                    const mobile = devices.find(
                        (row) => row.device === 'mobile',
                    );
                    const desktop = devices.find(
                        (row) => row.device === 'desktop',
                    );
                    const issue = Boolean(
                        mobile &&
                        desktop &&
                        desktop.conversion_rate > 0 &&
                        mobile.conversion_rate < desktop.conversion_rate * 0.5,
                    );

                    return (
                        <Card
                            key={source}
                            className={
                                issue
                                    ? 'border-destructive/50 bg-destructive/5'
                                    : ''
                            }
                        >
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between gap-2">
                                    <CardTitle className="truncate font-mono text-sm">
                                        {source}
                                    </CardTitle>
                                    {issue && (
                                        <Badge
                                            variant="destructive"
                                            className="gap-1"
                                        >
                                            <AlertTriangle className="h-3 w-3" />
                                            Mobile issue
                                        </Badge>
                                    )}
                                </div>
                                <CardDescription>
                                    {issue
                                        ? 'Mobile converts below half of desktop'
                                        : 'Device conversion comparison'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {devices.map((row) => (
                                    <div key={row.device} className="space-y-2">
                                        <div className="flex items-center justify-between gap-3 text-sm">
                                            <span className="flex items-center gap-2 text-muted-foreground">
                                                <DeviceIcon
                                                    device={row.device}
                                                />
                                                {titleCase(row.device)}
                                            </span>
                                            <span className="font-bold">
                                                {formatPercent(
                                                    row.conversion_rate,
                                                    2,
                                                )}
                                            </span>
                                        </div>
                                        <div className="h-3 overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full bg-primary transition-all"
                                                style={{
                                                    width: `${(row.conversion_rate / best) * 100}%`,
                                                }}
                                            />
                                        </div>
                                        <div className="flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                                            <span>
                                                {formatNumber(row.visits)}{' '}
                                                visits
                                            </span>
                                            <span>
                                                {formatNumber(row.total_leads)}{' '}
                                                leads
                                            </span>
                                            {mode === 'ctwa' && (
                                                <>
                                                    <span>
                                                        {formatNumber(
                                                            row.whatsapp_leads,
                                                        )}{' '}
                                                        WA
                                                    </span>
                                                    <span>
                                                        {formatNumber(
                                                            row.direct_checkouts,
                                                        )}{' '}
                                                        checkout
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        </section>
    );
}
