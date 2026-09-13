import { Activity } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { formatNumber, formatPercent } from '@/lib/analytics-format';
import type { FunnelReport } from '@/types/analytics';

const colors = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

export function SplitFunnel({ reports }: { reports: FunnelReport[] }) {
    const [selected, setSelected] = useState(() =>
        reports.slice(0, 2).map((item) => item.source),
    );
    const stages = useMemo(
        () => [
            ...new Set(
                reports.flatMap((item) =>
                    item.stages.map((stage) => stage.label),
                ),
            ),
        ],
        [reports],
    );
    const chart = stages.map((stage) => {
        const row: Record<string, string | number> = { stage };
        selected.forEach((source) => {
            row[source] =
                reports
                    .find((item) => item.source === source)
                    ?.stages.find((item) => item.label === stage)?.value ?? 0;
        });

        return row;
    });

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center gap-3">
                    <Activity className="h-5 w-5 text-primary" />
                    <div>
                        <CardTitle>Split Funnel</CardTitle>
                        <CardDescription>
                            Compare strict conversion journeys across landing
                            pages
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="flex flex-wrap gap-4">
                    {reports.map((report, index) => (
                        <div
                            key={report.source}
                            className="flex items-center gap-2"
                        >
                            <Checkbox
                                id={`funnel-${index}`}
                                checked={selected.includes(report.source)}
                                onCheckedChange={() =>
                                    setSelected((value) =>
                                        value.includes(report.source)
                                            ? value.filter(
                                                  (item) =>
                                                      item !== report.source,
                                              )
                                            : [...value, report.source],
                                    )
                                }
                            />
                            <Label
                                htmlFor={`funnel-${index}`}
                                className="cursor-pointer"
                            >
                                <span
                                    className="mr-2 inline-block h-3 w-3 rounded-full"
                                    style={{
                                        backgroundColor:
                                            colors[index % colors.length],
                                    }}
                                />
                                {report.source}
                            </Label>
                        </div>
                    ))}
                </div>
                {selected.length ? (
                    <div className="h-[390px] w-full">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart
                                data={chart}
                                margin={{
                                    top: 20,
                                    right: 20,
                                    left: -10,
                                    bottom: 10,
                                }}
                            >
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    className="stroke-border"
                                />
                                <XAxis
                                    dataKey="stage"
                                    className="fill-muted-foreground text-xs"
                                    interval={0}
                                    angle={-12}
                                    textAnchor="end"
                                    height={65}
                                />
                                <YAxis
                                    allowDecimals={false}
                                    className="fill-muted-foreground text-xs"
                                />
                                <Tooltip
                                    contentStyle={{
                                        backgroundColor: 'var(--popover)',
                                        border: '1px solid var(--border)',
                                        borderRadius: 8,
                                    }}
                                />
                                <Legend />
                                {selected.map((source) => (
                                    <Bar
                                        key={source}
                                        dataKey={source}
                                        fill={
                                            colors[
                                                reports.findIndex(
                                                    (item) =>
                                                        item.source === source,
                                                ) % colors.length
                                            ]
                                        }
                                        radius={[4, 4, 0, 0]}
                                    />
                                ))}
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                ) : (
                    <p className="py-12 text-center text-muted-foreground">
                        Select at least one landing page.
                    </p>
                )}
                {selected.length > 0 && (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="p-3 text-left text-muted-foreground">
                                        Stage
                                    </th>
                                    {selected.map((source) => (
                                        <th
                                            key={source}
                                            className="p-3 text-left text-muted-foreground"
                                        >
                                            {source}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {stages.map((stage) => (
                                    <tr key={stage} className="border-b">
                                        <td className="p-3 font-medium">
                                            {stage}
                                        </td>
                                        {selected.map((source) => {
                                            const value = reports
                                                .find(
                                                    (item) =>
                                                        item.source === source,
                                                )
                                                ?.stages.find(
                                                    (item) =>
                                                        item.label === stage,
                                                );

                                            return (
                                                <td
                                                    key={source}
                                                    className="p-3"
                                                >
                                                    {formatNumber(
                                                        value?.value ?? 0,
                                                    )}{' '}
                                                    <span className="text-xs text-muted-foreground">
                                                        (
                                                        {formatPercent(
                                                            value?.percentage,
                                                        )}
                                                        )
                                                    </span>
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
