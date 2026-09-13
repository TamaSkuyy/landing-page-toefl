import { ArrowDown, Eye, Layers, Users } from 'lucide-react';
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
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatNumber, formatPercent, titleCase } from '@/lib/analytics-format';
import type {
    PersonaReport,
    ScrollHeatmapReport,
    SectionHeatmapReport,
} from '@/types/analytics';

const colors: Record<string, string> = {
    Bouncers: 'var(--destructive)',
    Skimmers: 'var(--chart-3)',
    'Deep Readers': 'var(--chart-4)',
    Casuals: 'var(--chart-2)',
};
type Tab = 'personas' | 'scroll' | 'sections';

export function AudienceSegmentation({
    personas,
    scroll,
    sections,
}: {
    personas: PersonaReport[];
    scroll: ScrollHeatmapReport[];
    sections: SectionHeatmapReport[];
}) {
    const [tab, setTab] = useState<Tab>('personas');
    const personaChart = useMemo(
        () =>
            personas.map((report) => ({
                source: report.source,
                ...Object.fromEntries(
                    report.segments.map((segment) => [
                        segment.name,
                        segment.percentage,
                    ]),
                ),
            })),
        [personas],
    );
    const personaNames = [
        ...new Set(
            personas.flatMap((report) =>
                report.segments.map((segment) => segment.name),
            ),
        ),
    ];
    const scrollChart = [25, 50, 75, 90].map((depth) => ({
        depth: `${depth}%`,
        ...Object.fromEntries(
            scroll.map((report) => [
                report.source,
                report.depths.find((item) => item.depth === depth)
                    ?.percentage ?? 0,
            ]),
        ),
    }));
    const tabs: Array<{ key: Tab; label: string; icon: typeof Users }> = [
        { key: 'personas', label: 'Personas', icon: Users },
        { key: 'scroll', label: 'Scroll Heatmap', icon: Eye },
        { key: 'sections', label: 'Section Views', icon: Layers },
    ];

    if (!personas.length && !scroll.length && !sections.length) {
        return null;
    }

    return (
        <section className="space-y-4">
            <div className="flex items-center gap-3">
                <Users className="h-5 w-5 text-primary" />
                <div>
                    <h2 className="text-xl font-semibold">
                        Audience Segmentation
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Behavior patterns and content consumption
                    </p>
                </div>
            </div>
            <Card>
                <CardHeader>
                    <div className="flex flex-wrap gap-2">
                        {tabs.map(({ key, label, icon: Icon }) => (
                            <button
                                key={key}
                                onClick={() => setTab(key)}
                                className={`flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition ${tab === key ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80'}`}
                            >
                                <Icon className="h-4 w-4" />
                                {label}
                            </button>
                        ))}
                    </div>
                </CardHeader>
                <CardContent>
                    {tab === 'personas' && (
                        <div className="space-y-6">
                            <div>
                                <CardTitle className="text-base">
                                    Behavioral Personas
                                </CardTitle>
                                <CardDescription>
                                    Traffic composition by reader type
                                </CardDescription>
                            </div>
                            {personas.length ? (
                                <>
                                    <div className="h-[300px] w-full">
                                        <ResponsiveContainer
                                            width="100%"
                                            height="100%"
                                        >
                                            <BarChart
                                                data={personaChart}
                                                layout="vertical"
                                                margin={{
                                                    top: 10,
                                                    right: 20,
                                                    left: 45,
                                                    bottom: 5,
                                                }}
                                            >
                                                <CartesianGrid
                                                    strokeDasharray="3 3"
                                                    className="stroke-border"
                                                />
                                                <XAxis
                                                    type="number"
                                                    domain={[0, 100]}
                                                    unit="%"
                                                />
                                                <YAxis
                                                    dataKey="source"
                                                    type="category"
                                                    width={85}
                                                />
                                                <Tooltip
                                                    contentStyle={{
                                                        backgroundColor:
                                                            'var(--popover)',
                                                        border: '1px solid var(--border)',
                                                        borderRadius: 8,
                                                    }}
                                                />
                                                <Legend />
                                                {personaNames.map((name) => (
                                                    <Bar
                                                        key={name}
                                                        dataKey={name}
                                                        stackId="personas"
                                                        fill={
                                                            colors[name] ??
                                                            'var(--chart-1)'
                                                        }
                                                    />
                                                ))}
                                            </BarChart>
                                        </ResponsiveContainer>
                                    </div>
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {personas.map((report) => {
                                            const bounce =
                                                report.segments.find(
                                                    (item) =>
                                                        item.name ===
                                                        'Bouncers',
                                                )?.percentage ?? 0;

                                            return (
                                                <div
                                                    key={report.source}
                                                    className={`rounded-lg border p-3 ${bounce > 50 ? 'border-destructive/50 bg-destructive/5' : ''}`}
                                                >
                                                    <div className="mb-2 flex items-center justify-between">
                                                        <span className="font-mono text-sm font-medium">
                                                            {report.source}
                                                        </span>
                                                        {bounce > 50 && (
                                                            <Badge variant="destructive">
                                                                High bounce
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-2 text-xs">
                                                        {report.segments.map(
                                                            (item) => (
                                                                <div
                                                                    key={
                                                                        item.name
                                                                    }
                                                                    className="flex justify-between gap-2"
                                                                >
                                                                    <span className="text-muted-foreground">
                                                                        {
                                                                            item.name
                                                                        }
                                                                    </span>
                                                                    <span className="font-medium">
                                                                        {formatPercent(
                                                                            item.percentage,
                                                                        )}
                                                                    </span>
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </>
                            ) : (
                                <Empty label="No persona data available" />
                            )}
                        </div>
                    )}
                    {tab === 'scroll' && (
                        <div className="space-y-6">
                            <div>
                                <CardTitle className="text-base">
                                    Scroll Depth Heatmap
                                </CardTitle>
                                <CardDescription>
                                    Content consumption drop-off visualization
                                </CardDescription>
                            </div>
                            {scroll.length ? (
                                <>
                                    <div className="h-[300px] w-full">
                                        <ResponsiveContainer
                                            width="100%"
                                            height="100%"
                                        >
                                            <BarChart data={scrollChart}>
                                                <CartesianGrid
                                                    strokeDasharray="3 3"
                                                    className="stroke-border"
                                                />
                                                <XAxis dataKey="depth" />
                                                <YAxis
                                                    domain={[0, 100]}
                                                    unit="%"
                                                />
                                                <Tooltip
                                                    contentStyle={{
                                                        backgroundColor:
                                                            'var(--popover)',
                                                        border: '1px solid var(--border)',
                                                        borderRadius: 8,
                                                    }}
                                                />
                                                <Legend />
                                                {scroll.map((report, index) => (
                                                    <Bar
                                                        key={report.source}
                                                        dataKey={report.source}
                                                        fill={`var(--chart-${(index % 5) + 1})`}
                                                        radius={[4, 4, 0, 0]}
                                                    />
                                                ))}
                                            </BarChart>
                                        </ResponsiveContainer>
                                    </div>
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {scroll.map((report) => (
                                            <div
                                                key={report.source}
                                                className="rounded-lg border p-3"
                                            >
                                                <p className="mb-3 font-mono text-sm font-medium">
                                                    {report.source}
                                                </p>
                                                <div className="grid grid-cols-4 gap-2">
                                                    {report.depths.map(
                                                        (item) => (
                                                            <div
                                                                key={item.depth}
                                                                className="text-center"
                                                            >
                                                                <p className="text-xs text-muted-foreground">
                                                                    {item.depth}
                                                                    %
                                                                </p>
                                                                <div className="mt-1 flex h-20 items-end overflow-hidden rounded bg-muted">
                                                                    <div
                                                                        className="w-full bg-primary"
                                                                        style={{
                                                                            height: `${item.percentage}%`,
                                                                            opacity:
                                                                                0.45 +
                                                                                item.percentage /
                                                                                    200,
                                                                        }}
                                                                    />
                                                                </div>
                                                                <p className="mt-1 text-xs font-semibold">
                                                                    {formatPercent(
                                                                        item.percentage,
                                                                        0,
                                                                    )}
                                                                </p>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            ) : (
                                <Empty label="No scroll data available" />
                            )}
                        </div>
                    )}
                    {tab === 'sections' && (
                        <div className="space-y-6">
                            <div>
                                <CardTitle className="text-base">
                                    Section Visibility Funnel
                                </CardTitle>
                                <CardDescription>
                                    Percentage of visitors who reached each
                                    section
                                </CardDescription>
                            </div>
                            {sections.length ? (
                                sections.map((report) => (
                                    <div
                                        key={report.source}
                                        className="space-y-2"
                                    >
                                        <p className="font-mono text-sm font-semibold">
                                            {report.source}
                                        </p>
                                        {report.sections.map(
                                            (section, index) => (
                                                <div key={section.section}>
                                                    {index > 0 &&
                                                        section.drop_from_previous >
                                                            0 && (
                                                            <div className="flex items-center justify-center gap-1 py-1 text-xs text-muted-foreground">
                                                                <ArrowDown className="h-3 w-3" />
                                                                −
                                                                {formatPercent(
                                                                    section.drop_from_previous,
                                                                )}
                                                            </div>
                                                        )}
                                                    <div className="grid grid-cols-[90px_1fr_55px] items-center gap-3">
                                                        <span className="truncate text-right text-xs font-medium">
                                                            {titleCase(
                                                                section.section,
                                                            )}
                                                        </span>
                                                        <div className="relative h-8 overflow-hidden rounded-md bg-muted">
                                                            <div
                                                                className={`h-full rounded-md ${section.percentage >= 60 ? 'bg-emerald-500' : section.percentage >= 30 ? 'bg-amber-400' : 'bg-red-400'}`}
                                                                style={{
                                                                    width: `${Math.max(section.percentage, 2)}%`,
                                                                }}
                                                            />
                                                            <span className="absolute inset-0 flex items-center px-3 text-xs font-bold">
                                                                {formatPercent(
                                                                    section.percentage,
                                                                )}
                                                            </span>
                                                        </div>
                                                        <span className="text-right text-xs text-muted-foreground">
                                                            {formatNumber(
                                                                section.sessions,
                                                            )}
                                                        </span>
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ))
                            ) : (
                                <Empty label="No section data available" />
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>
        </section>
    );
}

function Empty({ label }: { label: string }) {
    return (
        <p className="py-12 text-center text-sm text-muted-foreground">
            {label}
        </p>
    );
}
