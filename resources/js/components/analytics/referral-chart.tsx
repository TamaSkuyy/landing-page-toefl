import {
    Cell,
    Legend,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatNumber } from '@/lib/analytics-format';
import type { ReferralRow } from '@/types/analytics';

const colors = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

export function ReferralChart({ rows }: { rows: ReferralRow[] }) {
    return (
        <Card className="border-border/50 bg-card/30 backdrop-blur-sm">
            <CardHeader>
                <CardTitle>Referral Sources</CardTitle>
            </CardHeader>
            <CardContent>
                {rows.length === 0 ? (
                    <div className="flex h-[320px] items-center justify-center text-sm text-muted-foreground">
                        Belum ada referral untuk periode ini.
                    </div>
                ) : (
                    <div className="h-[320px] w-full">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={rows}
                                    dataKey="visits"
                                    nameKey="source"
                                    innerRadius={62}
                                    outerRadius={102}
                                    paddingAngle={2}
                                >
                                    {rows.map((row, index) => (
                                        <Cell
                                            key={row.source}
                                            fill={colors[index % colors.length]}
                                        />
                                    ))}
                                </Pie>
                                <Tooltip
                                    formatter={(value) =>
                                        formatNumber(Number(value ?? 0))
                                    }
                                    contentStyle={{
                                        backgroundColor: 'var(--popover)',
                                        border: '1px solid var(--border)',
                                        borderRadius: '8px',
                                        color: 'var(--popover-foreground)',
                                    }}
                                />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
