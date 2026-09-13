import {
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export type ChartSeries = { key: string; label: string; color: string };

export function DailyChart({
    rows,
    series,
}: {
    rows: Array<Record<string, number | string>>;
    series: ChartSeries[];
}) {
    return (
        <Card className="border-border/50 bg-card/30 backdrop-blur-sm">
            <CardHeader>
                <CardTitle>Funnel Trends</CardTitle>
            </CardHeader>
            <CardContent>
                {rows.length === 0 ? (
                    <div className="flex h-[320px] items-center justify-center text-sm text-muted-foreground">
                        Belum ada data untuk periode ini.
                    </div>
                ) : (
                    <div className="h-[320px] w-full">
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart
                                data={rows}
                                margin={{
                                    top: 8,
                                    right: 12,
                                    left: -20,
                                    bottom: 0,
                                }}
                            >
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    className="stroke-border"
                                />
                                <XAxis
                                    dataKey="date"
                                    className="fill-muted-foreground text-xs"
                                    tickFormatter={(value) =>
                                        new Date(
                                            `${value}T00:00:00`,
                                        ).toLocaleDateString('id-ID', {
                                            day: '2-digit',
                                            month: 'short',
                                        })
                                    }
                                    minTickGap={24}
                                />
                                <YAxis
                                    allowDecimals={false}
                                    className="fill-muted-foreground text-xs"
                                />
                                <Tooltip
                                    contentStyle={{
                                        backgroundColor: 'var(--popover)',
                                        border: '1px solid var(--border)',
                                        borderRadius: '8px',
                                        color: 'var(--popover-foreground)',
                                    }}
                                />
                                <Legend />
                                {series.map((item) => (
                                    <Line
                                        key={item.key}
                                        type="monotone"
                                        dataKey={item.key}
                                        name={item.label}
                                        stroke={item.color}
                                        strokeWidth={2}
                                        dot={false}
                                        activeDot={{ r: 4 }}
                                    />
                                ))}
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
