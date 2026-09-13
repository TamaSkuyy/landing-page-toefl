import { ArrowUpDown, ChevronLeft, ChevronRight, Trophy } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    formatCurrency,
    formatNumber,
    formatPercent,
    titleCase,
} from '@/lib/analytics-format';
import type { PerformanceRow, ProjectMode } from '@/types/analytics';

type NumericKey = keyof PerformanceRow;

export function PerformanceMatrix({
    rows,
    mode,
    eventLabels,
}: {
    rows: PerformanceRow[];
    mode: ProjectMode;
    eventLabels: Partial<Record<string, string>>;
}) {
    const [sortKey, setSortKey] = useState<NumericKey>('lead_cr');
    const [direction, setDirection] = useState<'asc' | 'desc'>('desc');
    const [page, setPage] = useState(1);
    const perPage = 10;
    const sorted = useMemo(
        () =>
            [...rows].sort((a, b) => {
                const left = Number(a[sortKey] ?? 0);
                const right = Number(b[sortKey] ?? 0);

                return direction === 'desc' ? right - left : left - right;
            }),
        [rows, sortKey, direction],
    );
    const pages = Math.max(1, Math.ceil(sorted.length / perPage));
    const visible = sorted.slice((page - 1) * perPage, page * perPage);
    const winner = [...rows]
        .filter((row) => row.eligible)
        .sort((a, b) => b.lead_cr - a.lead_cr)[0];
    const columns: Array<{
        key: NumericKey;
        label: string;
        format: (value: number) => string;
    }> = [
        { key: 'visits', label: 'Visits', format: formatNumber },
        {
            key: 'bounce_rate',
            label: 'Bounce',
            format: (value) => formatPercent(value),
        },
        {
            key: 'intent_rate',
            label: 'Intent',
            format: (value) => formatPercent(value, 2),
        },
        ...(mode === 'ctwa'
            ? [
                  {
                      key: 'whatsapp_leads' as NumericKey,
                      label: eventLabels.whatsapp_lead ?? 'whatsapp lead',
                      format: formatNumber,
                  },
                  {
                      key: 'direct_checkouts' as NumericKey,
                      label: eventLabels.direct_checkout ?? 'direct checkout',
                      format: formatNumber,
                  },
              ]
            : [
                  {
                      key: 'form_start_rate' as NumericKey,
                      label: 'Form Start',
                      format: (value: number) => formatPercent(value, 2),
                  },
                  {
                      key: 'payments' as NumericKey,
                      label: 'Payments',
                      format: formatNumber,
                  },
              ]),
        {
            key: 'lead_cr',
            label: 'Lead CR',
            format: (value) => formatPercent(value, 2),
        },
        ...(mode === 'form'
            ? [
                  {
                      key: 'sales_cr' as NumericKey,
                      label: 'Sales CR',
                      format: (value: number) => formatPercent(value, 2),
                  },
                  {
                      key: 'rpv' as NumericKey,
                      label: 'RPV',
                      format: formatCurrency,
                  },
              ]
            : []),
    ];
    const sort = (key: NumericKey) => {
        if (key === sortKey) {
            setDirection((value) => (value === 'desc' ? 'asc' : 'desc'));
        } else {
            setSortKey(key);
            setDirection('desc');
        }

        setPage(1);
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center gap-3">
                    <Trophy className="h-5 w-5 text-primary" />
                    <div>
                        <CardTitle>Performance Matrix</CardTitle>
                        <CardDescription>
                            Landing page comparison sorted by the selected
                            metric
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div className="hidden overflow-x-auto lg:block">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-border">
                                <th className="p-4 text-left font-medium text-muted-foreground">
                                    Source
                                </th>
                                {columns.map((column) => (
                                    <th
                                        key={String(column.key)}
                                        className="p-4 text-left font-medium text-muted-foreground"
                                    >
                                        <button
                                            className="flex items-center gap-1 hover:text-foreground"
                                            onClick={() => sort(column.key)}
                                        >
                                            {column.label}
                                            <ArrowUpDown className="h-3 w-3" />
                                        </button>
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {visible.map((row) => {
                                const isWinner = winner?.source === row.source;

                                return (
                                    <tr
                                        key={row.source}
                                        className={`border-b border-border transition hover:bg-muted/50 ${isWinner ? 'bg-chart-4/5' : ''}`}
                                    >
                                        <td className="p-4">
                                            <div className="flex items-center gap-2">
                                                <span className="font-mono font-medium">
                                                    {row.source}
                                                </span>
                                                {isWinner && (
                                                    <Badge className="gap-1 bg-chart-4 text-foreground">
                                                        <Trophy className="h-3 w-3" />
                                                        Winner
                                                    </Badge>
                                                )}
                                                {!row.eligible && (
                                                    <Badge variant="outline">
                                                        Collecting data
                                                    </Badge>
                                                )}
                                            </div>
                                        </td>
                                        {columns.map((column) => (
                                            <td
                                                key={String(column.key)}
                                                className="p-4"
                                            >
                                                {column.key === 'lead_cr' ? (
                                                    <Badge
                                                        variant={
                                                            isWinner
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                        className={
                                                            isWinner
                                                                ? 'bg-chart-4 text-foreground'
                                                                : ''
                                                        }
                                                    >
                                                        {column.format(
                                                            Number(
                                                                row[
                                                                    column.key
                                                                ] ?? 0,
                                                            ),
                                                        )}
                                                    </Badge>
                                                ) : (
                                                    column.format(
                                                        Number(
                                                            row[column.key] ??
                                                                0,
                                                        ),
                                                    )
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                <div className="space-y-4 lg:hidden">
                    {visible.map((row) => {
                        const isWinner = winner?.source === row.source;

                        return (
                            <div
                                key={row.source}
                                className={`rounded-lg border p-4 ${isWinner ? 'border-chart-4' : 'border-border'}`}
                            >
                                <div className="mb-3 flex items-center justify-between gap-2">
                                    <span className="truncate font-mono font-medium">
                                        {row.source}
                                    </span>
                                    {isWinner ? (
                                        <Badge className="bg-chart-4 text-foreground">
                                            Winner
                                        </Badge>
                                    ) : (
                                        <Badge variant="outline">
                                            {row.eligible
                                                ? 'Eligible'
                                                : 'Collecting'}
                                        </Badge>
                                    )}
                                </div>
                                <div className="grid grid-cols-2 gap-3 text-sm">
                                    {columns.map((column) => (
                                        <div key={String(column.key)}>
                                            <p className="text-xs text-muted-foreground">
                                                {titleCase(column.label)}
                                            </p>
                                            <p className="font-semibold">
                                                {column.format(
                                                    Number(
                                                        row[column.key] ?? 0,
                                                    ),
                                                )}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {pages > 1 && (
                    <div className="mt-6 flex items-center justify-center gap-4">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={page === 1}
                            onClick={() =>
                                setPage((value) => Math.max(1, value - 1))
                            }
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </Button>
                        <span className="text-sm text-muted-foreground">
                            Page {page} of {pages}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={page === pages}
                            onClick={() =>
                                setPage((value) => Math.min(pages, value + 1))
                            }
                        >
                            <ChevronRight className="h-4 w-4" />
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
