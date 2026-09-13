import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    BarChart3,
    Download,
    Eye,
    MousePointerClick,
    ShoppingCart,
    Target,
    TrendingUp,
    Users,
} from 'lucide-react';
import { EVENT_TYPES } from '@/analytics/event-types';
import { ConversionFunnel } from '@/components/analytics/conversion-funnel';
import { DailyChart } from '@/components/analytics/daily-chart';
import type { ChartSeries } from '@/components/analytics/daily-chart';
import { MetricCard } from '@/components/analytics/metric-card';
import { ReferralChart } from '@/components/analytics/referral-chart';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import {
    formatCurrency,
    formatNumber,
    formatPercent,
} from '@/lib/analytics-format';
import type { AnalyticsPageProps, TrackingProps } from '@/types/analytics';

export default function Analytics(props: AnalyticsPageProps) {
    const tracking = usePage().props.tracking as TrackingProps;
    const { stats } = props;
    const label = (event: keyof typeof tracking.eventLabels) =>
        tracking.eventLabels[event] ?? event.replaceAll('_', ' ');
    const changeRange = (value: string) =>
        router.get('/admin', { range: value }, { preserveState: true });

    const series: ChartSeries[] = [
        {
            key: EVENT_TYPES.visit,
            label: label(EVENT_TYPES.visit),
            color: 'var(--chart-1)',
        },
        {
            key: EVENT_TYPES.engagement,
            label: label(EVENT_TYPES.engagement),
            color: 'var(--chart-2)',
        },
        {
            key: EVENT_TYPES.intent,
            label: label(EVENT_TYPES.intent),
            color: 'var(--chart-3)',
        },
        ...(props.mode === 'ctwa'
            ? [
                  {
                      key: EVENT_TYPES.whatsappLead,
                      label: label(EVENT_TYPES.whatsappLead),
                      color: 'var(--chart-4)',
                  },
                  {
                      key: EVENT_TYPES.directCheckout,
                      label: label(EVENT_TYPES.directCheckout),
                      color: 'var(--chart-5)',
                  },
              ]
            : [
                  {
                      key: EVENT_TYPES.formStart,
                      label: label(EVENT_TYPES.formStart),
                      color: 'var(--chart-4)',
                  },
                  {
                      key: EVENT_TYPES.lead,
                      label: label(EVENT_TYPES.lead),
                      color: 'var(--chart-5)',
                  },
              ]),
    ];

    const commonCards = [
        {
            title: 'Total Visits',
            value: formatNumber(stats.visits),
            description: `${formatNumber(stats.page_views)} page views`,
            icon: Eye,
        },
        {
            title: 'Engagement Rate',
            value: formatPercent(stats.engagement_rate, 2),
            description: `${formatNumber(stats.engagements)} engaged sessions`,
            icon: Activity,
        },
        {
            title: 'Intent Rate',
            value: formatPercent(stats.intent_rate, 2),
            description: `${formatNumber(stats.intents)} intent sessions`,
            icon: MousePointerClick,
        },
        {
            title: 'Bounce Rate',
            value: formatPercent(stats.bounce_rate, 2),
            description: `${formatNumber(stats.bounces)} bounced sessions`,
            icon: TrendingUp,
        },
    ];
    const modeCards =
        props.mode === 'ctwa'
            ? [
                  {
                      title: label(EVENT_TYPES.whatsappLead),
                      value: formatNumber(stats.whatsapp_leads),
                      description: `${formatPercent(stats.whatsapp_rate, 2)} of visits`,
                      icon: Users,
                  },
                  {
                      title: label(EVENT_TYPES.directCheckout),
                      value: formatNumber(stats.direct_checkouts),
                      description: `${formatPercent(stats.direct_checkout_rate, 2)} of visits`,
                      icon: ShoppingCart,
                  },
                  {
                      title: 'Total Lead',
                      value: formatNumber(stats.total_leads),
                      description: 'Unique WhatsApp + checkout sessions',
                      icon: Target,
                  },
                  {
                      title: 'Lead CR',
                      value: formatPercent(stats.lead_cr, 2),
                      description: 'Total Lead ÷ Visit',
                      icon: BarChart3,
                  },
              ]
            : [
                  {
                      title: 'Form Start Rate',
                      value: formatPercent(stats.form_start_rate, 2),
                      description: `${formatNumber(stats.form_starts)} form starts`,
                      icon: ShoppingCart,
                  },
                  {
                      title: 'Lead Rate',
                      value: formatPercent(stats.lead_cr, 2),
                      description: `${formatNumber(stats.total_leads)} leads`,
                      icon: Users,
                  },
                  {
                      title: tracking.capabilities.payment
                          ? 'Lead to Payment'
                          : 'Total Leads',
                      value: tracking.capabilities.payment
                          ? formatPercent(stats.lead_to_payment_rate, 2)
                          : formatNumber(stats.total_leads),
                      description: tracking.capabilities.payment
                          ? `${formatNumber(stats.payments)} successful payments`
                          : 'Completed form submissions',
                      icon: Target,
                  },
                  {
                      title: tracking.capabilities.revenue
                          ? 'Total Revenue'
                          : 'Lead CR',
                      value: tracking.capabilities.revenue
                          ? formatCurrency(stats.revenue)
                          : formatPercent(stats.lead_cr, 2),
                      description: tracking.capabilities.revenue
                          ? `${formatCurrency(stats.rpv)} revenue per visit`
                          : 'Lead ÷ Visit',
                      icon: BarChart3,
                  },
              ];

    return (
        <AdminLayout>
            <Head title="Analytics" />
            <div className="analytics-workspace min-h-screen overflow-hidden bg-background">
                <header className="analytics-header border-b border-border/50 bg-card/30 px-4 py-6 backdrop-blur-sm md:px-6 md:py-8">
                    <div className="flex w-full max-w-[1600px] flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-foreground md:text-3xl">
                                Analytics Dashboard
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Monitor funnel performance and visitor behavior.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Select
                                value={String(props.range)}
                                onValueChange={changeRange}
                            >
                                <SelectTrigger className="w-40">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {[3, 5, 7, 14, 30, 90].map((days) => (
                                        <SelectItem
                                            key={days}
                                            value={String(days)}
                                        >
                                            Last {days} Days
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button variant="outline" asChild>
                                <Link
                                    href={`/admin/export?range=${props.range}`}
                                >
                                    <Download className="h-4 w-4" />
                                    Export CSV
                                </Link>
                            </Button>
                        </div>
                    </div>
                </header>

                <main className="w-full max-w-[1600px] space-y-8 p-4 md:p-6">
                    <section>
                        <div className="mb-4">
                            <h2 className="text-lg font-semibold">
                                Key Metrics
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Distinct sessions from the last {props.range}{' '}
                                days.
                            </p>
                        </div>
                        <div className="analytics-metrics-grid grid gap-4 md:grid-cols-2 lg:grid-cols-4 lg:gap-5">
                            {[...commonCards, ...modeCards].map((card) => (
                                <MetricCard key={card.title} {...card} />
                            ))}
                        </div>
                    </section>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <DailyChart rows={props.daily} series={series} />
                        <ReferralChart rows={props.referrals} />
                    </div>

                    <ConversionFunnel stages={props.funnel} />

                    <section className="analytics-panel rounded-xl border border-border/50 bg-card/30 p-6 backdrop-blur-sm">
                        <h2 className="text-lg font-semibold">Key Insights</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-3">
                            <div className="analytics-subpanel rounded-lg border border-border/50 bg-muted/20 p-4">
                                <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                    Top Referral Source
                                </p>
                                <p className="mt-2 truncate font-semibold">
                                    {props.insights.top_referral.source}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {formatNumber(
                                        props.insights.top_referral.visits,
                                    )}{' '}
                                    visits
                                </p>
                            </div>
                            <div className="analytics-subpanel rounded-lg border border-border/50 bg-muted/20 p-4">
                                <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                    {props.mode === 'ctwa'
                                        ? 'Primary CTWA Channel'
                                        : 'Lead Conversion'}
                                </p>
                                <p className="mt-2 font-semibold">
                                    {props.mode === 'ctwa'
                                        ? props.insights.primary_channel
                                        : formatPercent(
                                              props.insights.lead_cr,
                                              2,
                                          )}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {props.mode === 'ctwa'
                                        ? `${formatNumber(props.insights.primary_channel_value)} sessions`
                                        : 'Total Lead ÷ Visit'}
                                </p>
                            </div>
                            <div className="analytics-subpanel rounded-lg border border-border/50 bg-muted/20 p-4">
                                <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                    {tracking.capabilities.revenue
                                        ? 'Revenue per Visit'
                                        : 'Data Retention'}
                                </p>
                                <p className="mt-2 font-semibold">
                                    {tracking.capabilities.revenue
                                        ? formatCurrency(props.insights.rpv)
                                        : `${props.retentionDays} days`}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Older telemetry remains in archive.
                                </p>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </AdminLayout>
    );
}
