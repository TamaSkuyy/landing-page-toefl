import { Head, router, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Filter,
    FlaskConical,
    Globe,
    RefreshCw,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { DateRangePicker } from '@/components/date-range-picker';
import type { SimpleDateRange } from '@/components/date-range-picker';
import { AudienceSegmentation } from '@/components/labs/audience-segmentation';
import { BehaviorAnalysis } from '@/components/labs/behavior-analysis';
import { CtaAnalysis } from '@/components/labs/cta-analysis';
import { DeviceComparison } from '@/components/labs/device-comparison';
import { PerformanceMatrix } from '@/components/labs/performance-matrix';
import { SplitFunnel } from '@/components/labs/split-funnel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import type { LabsPageProps, TrackingProps } from '@/types/analytics';

export default function Labs(props: LabsPageProps) {
    const tracking = usePage().props.tracking as TrackingProps;
    const [refreshing, setRefreshing] = useState(false);
    const [date, setDate] = useState<SimpleDateRange>({
        from: props.filters.start_date,
        to: props.filters.end_date,
    });
    const pages = useMemo(
        () => [...new Set(props.performance.map((row) => row.source))].sort(),
        [props.performance],
    );
    const [selectedPages, setSelectedPages] = useState<string[]>(() => {
        if (typeof window === 'undefined') {
            return [];
        }

        try {
            const stored = JSON.parse(
                localStorage.getItem('labs_page_filter') ?? '[]',
            ) as string[];

            return stored.filter((item) => pages.includes(item));
        } catch {
            return [];
        }
    });

    useEffect(
        () =>
            localStorage.setItem(
                'labs_page_filter',
                JSON.stringify(selectedPages),
            ),
        [selectedPages],
    );
    const matches = (source: string) =>
        !selectedPages.length || selectedPages.includes(source);
    const filtered = {
        performance: props.performance.filter((row) => matches(row.source)),
        funnel: props.funnel.filter((row) => matches(row.source)),
        devices: props.devices.filter((row) => matches(row.source)),
        ctas: props.ctas.filter((row) => matches(row.source)),
        personas: props.personas.filter((row) => matches(row.source)),
        scroll: props.scroll_heatmap.filter((row) => matches(row.source)),
        sections: props.section_heatmap.filter((row) => matches(row.source)),
        quality: props.quality.filter((row) => matches(row.source)),
    };
    const requestParams = (
        overrides: Record<string, string | undefined> = {},
    ) => ({
        range: props.filters.range,
        source: props.filters.source ?? undefined,
        ...(props.filters.range === 'custom'
            ? {
                  start_date: props.filters.start_date,
                  end_date: props.filters.end_date,
              }
            : {}),
        ...overrides,
    });
    const navigate = (params: Record<string, string | undefined>) =>
        router.get('/admin/labs', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    const updateRange = (range: string) => {
        if (range === 'custom') {
            navigate(
                requestParams({
                    range,
                    start_date: date.from,
                    end_date: date.to,
                }),
            );
        } else {
            navigate({ range, source: props.filters.source ?? undefined });
        }
    };
    const updateDate = (next: SimpleDateRange) => {
        setDate(next);

        if (next.from && next.to && next.from <= next.to) {
            navigate(
                requestParams({
                    range: 'custom',
                    start_date: next.from,
                    end_date: next.to,
                }),
            );
        }
    };
    const updateSource = (source: string) =>
        navigate(
            requestParams({ source: source === 'all' ? undefined : source }),
        );
    const refresh = () => {
        setRefreshing(true);
        router.post('/admin/labs/clear-cache', requestParams(), {
            preserveScroll: true,
            onFinish: () => setRefreshing(false),
        });
    };

    return (
        <AdminLayout>
            <Head title="A/B Testing Labs" />
            <div className="analytics-workspace min-h-screen w-full max-w-[1600px] space-y-8 p-4 md:p-6">
                <header className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex items-center gap-4">
                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/20">
                            <FlaskConical className="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold md:text-3xl">
                                A/B Testing Labs
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Optimize landing page performance with
                                structured comparisons
                            </p>
                        </div>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="flex items-center gap-2">
                            <Filter className="h-4 w-4 text-muted-foreground" />
                            <Select
                                value={props.filters.source ?? 'all'}
                                onValueChange={updateSource}
                            >
                                <SelectTrigger className="w-[170px]">
                                    <SelectValue placeholder="All Referrals" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All Referrals
                                    </SelectItem>
                                    {props.availableSources.map((source) => (
                                        <SelectItem key={source} value={source}>
                                            {source}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {props.filters.source && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8"
                                    onClick={() => updateSource('all')}
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                        {pages.length > 1 && (
                            <div className="flex items-center gap-2">
                                <Globe className="h-4 w-4 text-muted-foreground" />
                                <Select
                                    value={
                                        selectedPages.length === 1
                                            ? selectedPages[0]
                                            : 'all'
                                    }
                                    onValueChange={(value) =>
                                        setSelectedPages(
                                            value === 'all'
                                                ? []
                                                : selectedPages.includes(value)
                                                  ? selectedPages.filter(
                                                        (item) =>
                                                            item !== value,
                                                    )
                                                  : [...selectedPages, value],
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-[170px]">
                                        <SelectValue>
                                            {selectedPages.length
                                                ? `${selectedPages.length} page selected`
                                                : 'All Pages'}
                                        </SelectValue>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All Pages
                                        </SelectItem>
                                        {pages.map((page) => (
                                            <SelectItem key={page} value={page}>
                                                {selectedPages.includes(page)
                                                    ? '● '
                                                    : '○ '}
                                                {page}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {selectedPages.length > 0 && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8"
                                        onClick={() => setSelectedPages([])}
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        )}
                        <Select
                            value={props.filters.range}
                            onValueChange={updateRange}
                        >
                            <SelectTrigger className="w-[150px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {['3', '5', '7', '14', '30', '90'].map(
                                    (days) => (
                                        <SelectItem key={days} value={days}>
                                            Last {days} Days
                                        </SelectItem>
                                    ),
                                )}
                                <SelectItem value="custom">
                                    Custom Range
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        {props.filters.range === 'custom' && (
                            <DateRangePicker
                                date={date}
                                onUpdate={updateDate}
                            />
                        )}
                        <Button
                            variant="outline"
                            onClick={refresh}
                            disabled={refreshing}
                        >
                            <RefreshCw
                                className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`}
                            />
                            <span className="hidden sm:inline">
                                Refresh Data
                            </span>
                        </Button>
                    </div>
                </header>

                <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                    <span>
                        Showing{' '}
                        <strong className="text-foreground">
                            {props.filters.start_date}
                        </strong>{' '}
                        to{' '}
                        <strong className="text-foreground">
                            {props.filters.end_date}
                        </strong>
                    </span>
                    <Badge variant="secondary">
                        Primary: {props.primaryMetric.replaceAll('_', ' ')}
                    </Badge>
                    <Badge variant="outline">
                        Winner ≥ {props.minimumWinnerVisits} visits
                    </Badge>
                    {props.filters.source && (
                        <Badge variant="secondary">
                            Referral: {props.filters.source}
                        </Badge>
                    )}
                    {selectedPages.length > 0 && (
                        <Badge variant="secondary">
                            {selectedPages.length} pages
                        </Badge>
                    )}
                </div>

                {!filtered.performance.length ? (
                    <Card className="py-16 text-center">
                        <CardContent>
                            <BarChart3 className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                            <h2 className="text-lg font-semibold">
                                No Analytics Data
                            </h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Adjust the date, referral, or landing page
                                filters.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <PerformanceMatrix
                            rows={filtered.performance}
                            mode={tracking.mode}
                            eventLabels={tracking.eventLabels}
                        />
                        <SplitFunnel reports={filtered.funnel} />
                        <DeviceComparison
                            rows={filtered.devices}
                            mode={tracking.mode}
                        />
                        <CtaAnalysis
                            rows={filtered.ctas}
                            mode={tracking.mode}
                        />
                        <AudienceSegmentation
                            personas={filtered.personas}
                            scroll={filtered.scroll}
                            sections={
                                tracking.capabilities.section_view
                                    ? filtered.sections
                                    : []
                            }
                        />
                        <BehaviorAnalysis rows={filtered.quality} />
                    </>
                )}
                <p className="text-xs text-muted-foreground">
                    Dashboard is limited to {props.retentionDays} days. Older
                    telemetry remains available in archive tables.
                </p>
            </div>
        </AdminLayout>
    );
}
