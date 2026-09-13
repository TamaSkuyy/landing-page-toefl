<?php

namespace App\Services;

use App\Analytics\EventType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AbTestingService
{
    public function __construct(private readonly AnalyticsMetricsService $metrics) {}

    public function report(CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource = null): array
    {
        $sources = $this->landingSources($from, $to, $referralSource);

        return [
            'performance' => $this->performance($sources, $from, $to, $referralSource),
            'funnel' => $this->funnels($sources, $from, $to, $referralSource),
            'devices' => $this->devices($sources, $from, $to, $referralSource),
            'ctas' => $this->ctas($sources, $from, $to, $referralSource),
            'personas' => $this->personas($sources, $from, $to, $referralSource),
            'scroll_heatmap' => $this->scrollHeatmap($sources, $from, $to, $referralSource),
            'section_heatmap' => $this->sectionHeatmap($sources, $from, $to, $referralSource),
            'quality' => $this->quality($sources, $from, $to, $referralSource),
        ];
    }

    public function availableReferrals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return DB::table('analytics_sessions')
            ->whereBetween('started_at', [$from, $to])
            ->whereNotNull('referral_source')
            ->where('referral_source', '!=', '')
            ->distinct()
            ->orderBy('referral_source')
            ->pluck('referral_source')
            ->all();
    }

    private function performance(array $sources, CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $counts = $this->eventCounts($from, $to, $referralSource);
        $engagedIds = $this->engagedSessionIdsBySource($from, $to, $referralSource);
        $leadIds = $this->leadIdsBySource($from, $to, $referralSource);
        $paymentStats = $this->paymentStatsBySource($from, $to, $referralSource);
        $result = [];

        foreach ($sources as $source) {
            $sourceCounts = $counts[$source] ?? [];
            $visits = $sourceCounts[EventType::Visit->value] ?? 0;
            $engagements = min($visits, ($engagedIds[$source] ?? collect())->count());
            $bounceCount = max(0, $visits - $engagements);
            $totalLeads = ($leadIds[$source] ?? collect())->count();
            $row = [
                'source' => $source,
                'visits' => $visits,
                'engagements' => $engagements,
                'engagement_rate' => $this->rate($engagements, $visits),
                'bounces' => $bounceCount,
                'bounce_rate' => $this->rate($bounceCount, $visits),
                'intents' => $sourceCounts[EventType::Intent->value] ?? 0,
                'intent_rate' => $this->rate($sourceCounts[EventType::Intent->value] ?? 0, $visits),
                'total_leads' => $totalLeads,
                'lead_cr' => $this->rate($totalLeads, $visits),
                'eligible' => $visits >= (int) config('analytics.minimum_winner_visits'),
            ];

            if (config('analytics.mode') === 'ctwa') {
                $whatsapp = $sourceCounts[EventType::WhatsappLead->value] ?? 0;
                $checkout = $sourceCounts[EventType::DirectCheckout->value] ?? 0;
                $row += [
                    'whatsapp_leads' => $whatsapp,
                    'whatsapp_rate' => $this->rate($whatsapp, $visits),
                    'direct_checkouts' => $checkout,
                    'direct_checkout_rate' => $this->rate($checkout, $visits),
                ];
            } else {
                $formStarts = $sourceCounts[EventType::FormStart->value] ?? 0;
                $payments = $paymentStats[$source]['payments'] ?? 0;
                $revenue = $paymentStats[$source]['revenue'] ?? 0;
                $row += [
                    'form_starts' => $formStarts,
                    'form_start_rate' => $this->rate($formStarts, $visits),
                    'payments' => $payments,
                    'sales_cr' => $this->rate($payments, $visits),
                    'revenue' => $revenue,
                    'rpv' => $visits > 0 ? round($revenue / $visits) : 0,
                ];
            }

            $result[] = $row;
        }

        usort($result, fn ($a, $b) => [$b['eligible'], $b['lead_cr'], $b['visits']] <=> [$a['eligible'], $a['lead_cr'], $a['visits']]);

        return $result;
    }

    private function funnels(array $sources, CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $events = $this->eventSessionIdsBySource($from, $to, $referralSource);
        $engagedSessions = $this->engagedSessionIdsBySource($from, $to, $referralSource);

        return collect($sources)->map(function (string $source) use ($events, $engagedSessions) {
            $byEvent = $events[$source] ?? collect();
            $visits = collect($byEvent[EventType::Visit->value] ?? []);
            $engaged = $visits->intersect($engagedSessions[$source] ?? []);
            $intent = $engaged->intersect($byEvent[EventType::Intent->value] ?? []);
            $stages = [
                $this->stage(EventType::Visit, $visits->count(), $visits->count(), null, null, 'main'),
                $this->stage(EventType::Engagement, $engaged->count(), $visits->count(), $visits->count(), EventType::Visit, 'main'),
                $this->stage(EventType::Intent, $intent->count(), $visits->count(), $engaged->count(), EventType::Engagement, 'main'),
            ];

            if (config('analytics.mode') === 'ctwa') {
                foreach ([EventType::DirectCheckout, EventType::WhatsappLead] as $branch) {
                    $value = $intent->intersect($byEvent[$branch->value] ?? [])->count();
                    $stages[] = $this->stage($branch, $value, $visits->count(), $intent->count(), EventType::Intent, $branch->value);
                }
            } else {
                $eligible = $intent;
                foreach ([EventType::FormStart, EventType::Lead] as $event) {
                    $previous = $eligible->count();
                    $eligible = $eligible->intersect($byEvent[$event->value] ?? []);
                    $fromEvent = $stages[array_key_last($stages)]['event'];
                    $stages[] = $this->stage($event, $eligible->count(), $visits->count(), $previous, $fromEvent, 'main');
                }
                if (config('analytics.capabilities.payment')) {
                    $previous = $eligible->count();
                    $eligible = $eligible->intersect($byEvent[EventType::Payment->value] ?? []);
                    $stages[] = $this->stage(EventType::Payment, $eligible->count(), $visits->count(), $previous, EventType::Lead, 'main');
                }
            }

            return ['source' => $source, 'stages' => $stages];
        })->all();
    }

    private function devices(array $sources, CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $sessions = $this->sessionRows($from, $to, $referralSource)->groupBy('landing_source');
        $leadIds = $this->leadIdsBySource($from, $to, $referralSource);
        $eventIds = $this->eventSessionIdsBySource($from, $to, $referralSource);
        $result = [];

        foreach ($sources as $source) {
            foreach (($sessions[$source] ?? collect())->groupBy(fn ($row) => $row->device_type ?: 'unknown') as $device => $rows) {
                $ids = $rows->pluck('session_id')->unique();
                $leads = $ids->intersect($leadIds[$source] ?? collect())->count();
                $result[] = [
                    'source' => $source,
                    'device' => $device,
                    'visits' => $ids->count(),
                    'total_leads' => $leads,
                    'conversion_rate' => $this->rate($leads, $ids->count()),
                    'whatsapp_leads' => config('analytics.mode') === 'ctwa' ? $ids->intersect($eventIds[$source][EventType::WhatsappLead->value] ?? [])->count() : 0,
                    'direct_checkouts' => config('analytics.mode') === 'ctwa' ? $ids->intersect($eventIds[$source][EventType::DirectCheckout->value] ?? [])->count() : 0,
                ];
            }
        }

        return $result;
    }

    private function ctas(array $sources, CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $query = DB::table('user_analytics')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('cta_zone')
            ->whereIn('event_type', [EventType::Intent->value, EventType::WhatsappLead->value, EventType::DirectCheckout->value])
            ->select(['landing_source', 'cta_zone', 'cta_action', 'event_type', 'session_id']);
        $this->filterReferral($query, $referralSource);
        $rows = $query->get();
        $leadIds = $this->leadIdsBySource($from, $to, $referralSource);

        return $rows->groupBy(fn ($row) => ($row->landing_source ?: '/').'|'.$row->cta_zone.'|'.$row->cta_action)
            ->map(function (Collection $group) use ($leadIds) {
                $first = $group->first();
                $source = $first->landing_source ?: '/';
                $ids = $group->pluck('session_id')->unique();
                $leads = $ids->intersect($leadIds[$source] ?? collect());

                return [
                    'source' => $source,
                    'zone' => $first->cta_zone,
                    'action' => $first->cta_action ?: 'unknown',
                    'clicks' => $group->count(),
                    'sessions' => $ids->count(),
                    'total_leads' => $leads->count(),
                    'lead_rate' => $this->rate($leads->count(), $ids->count()),
                    'whatsapp_leads' => $group->where('event_type', EventType::WhatsappLead->value)->pluck('session_id')->unique()->count(),
                    'direct_checkouts' => $group->where('event_type', EventType::DirectCheckout->value)->pluck('session_id')->unique()->count(),
                ];
            })
            ->sortByDesc('total_leads')
            ->values()
            ->all();
    }

    private function personas(array $sources, CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $sessions = $this->sessionRows($from, $to, $referralSource)->groupBy('landing_source');

        return collect($sources)->map(function (string $source) use ($sessions) {
            $rows = $sessions[$source] ?? collect();
            $segments = ['Bouncers' => 0, 'Skimmers' => 0, 'Deep Readers' => 0, 'Casuals' => 0];
            foreach ($rows as $row) {
                if ($row->is_bounce) {
                    $segments['Bouncers']++;
                } elseif ($row->duration_seconds > 120) {
                    $segments['Deep Readers']++;
                } elseif ($row->max_scroll_depth > 75 && $row->duration_seconds < 60) {
                    $segments['Skimmers']++;
                } else {
                    $segments['Casuals']++;
                }
            }
            $total = $rows->count();

            return [
                'source' => $source,
                'total_sessions' => $total,
                'segments' => collect($segments)->map(fn ($count, $name) => [
                    'name' => $name,
                    'count' => $count,
                    'percentage' => $this->rate($count, $total),
                ])->values()->all(),
            ];
        })->all();
    }

    private function scrollHeatmap(array $sources, CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $visits = $this->eventCounts($from, $to, $referralSource);
        $query = DB::table('user_analytics')
            ->where('event_type', EventType::Scroll->value)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('landing_source, session_id, MAX(scroll_depth) AS max_depth')
            ->groupBy('landing_source', 'session_id');
        $this->filterReferral($query, $referralSource);
        $depths = $query->get()->groupBy(fn ($row) => $row->landing_source ?: '/');

        return collect($sources)->map(function (string $source) use ($depths, $visits) {
            $total = $visits[$source][EventType::Visit->value] ?? 0;
            $rows = $depths[$source] ?? collect();

            return [
                'source' => $source,
                'total_visits' => $total,
                'depths' => collect([25, 50, 75, 90])->map(fn ($depth) => [
                    'depth' => $depth,
                    'sessions' => $rows->where('max_depth', '>=', $depth)->count(),
                    'percentage' => $this->rate($rows->where('max_depth', '>=', $depth)->count(), $total),
                ])->all(),
            ];
        })->all();
    }

    private function sectionHeatmap(array $sources, CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $visits = $this->eventCounts($from, $to, $referralSource);
        $query = DB::table('user_analytics')
            ->where('event_type', EventType::SectionView->value)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('section_id')
            ->selectRaw('landing_source, section_id, COUNT(DISTINCT session_id) AS sessions, MIN(created_at) AS first_seen')
            ->groupBy('landing_source', 'section_id');
        $this->filterReferral($query, $referralSource);
        $rows = $query->get()->groupBy(fn ($row) => $row->landing_source ?: '/');

        return collect($sources)->map(function (string $source) use ($rows, $visits) {
            $previous = null;
            $total = $visits[$source][EventType::Visit->value] ?? 0;
            $sections = [];
            foreach (($rows[$source] ?? collect())->sortBy('first_seen') as $row) {
                $count = (int) $row->sessions;
                $sections[] = [
                    'section' => $row->section_id,
                    'sessions' => $count,
                    'percentage' => $this->rate($count, $total),
                    'drop_from_previous' => $previous === null ? 0 : max(0, round(100 - $this->rate($count, $previous), 2)),
                ];
                $previous = $count;
            }

            return ['source' => $source, 'sections' => $sections];
        })->filter(fn ($row) => count($row['sections']) > 0)->values()->all();
    }

    private function quality(array $sources, CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $sessions = $this->sessionRows($from, $to, $referralSource)->groupBy('landing_source');
        $leadIds = $this->leadIdsBySource($from, $to, $referralSource);

        return collect($sources)->map(function (string $source) use ($sessions, $leadIds) {
            $rows = $sessions[$source] ?? collect();
            $ids = $leadIds[$source] ?? collect();
            $leads = $rows->whereIn('session_id', $ids);
            $others = $rows->whereNotIn('session_id', $ids);

            return [
                'source' => $source,
                'leads' => $this->qualityMetrics($leads),
                'non_leads' => $this->qualityMetrics($others),
            ];
        })->all();
    }

    private function qualityMetrics(Collection $rows): array
    {
        return [
            'count' => $rows->count(),
            'avg_scroll_depth' => round((float) ($rows->avg('max_scroll_depth') ?? 0), 1),
            'avg_dwell_time' => round((float) ($rows->avg('duration_seconds') ?? 0), 1),
        ];
    }

    private function landingSources(CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $query = DB::table('user_analytics')
            ->where('event_type', EventType::Visit->value)
            ->whereBetween('created_at', [$from, $to]);
        $this->filterReferral($query, $referralSource);

        return $query->selectRaw("COALESCE(NULLIF(landing_source, ''), '/') AS source")
            ->distinct()->orderBy('source')->pluck('source')->all();
    }

    private function eventCounts(CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $query = DB::table('user_analytics')->whereBetween('created_at', [$from, $to]);
        $this->filterReferral($query, $referralSource);
        $result = [];
        foreach ($query->selectRaw("COALESCE(NULLIF(landing_source, ''), '/') AS source, event_type, COUNT(DISTINCT session_id) AS total")
            ->groupBy('landing_source', 'event_type')->get() as $row) {
            $result[$row->source][$row->event_type] = (int) $row->total;
        }

        return $result;
    }

    private function eventSessionIdsBySource(CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $query = DB::table('user_analytics')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('event_type', array_map(fn (EventType $event) => $event->value, EventType::forMode((string) config('analytics.mode'))))
            ->where(function (Builder $builder) {
                $builder->where('event_type', '!=', EventType::Payment->value)
                    ->orWhereIn('payment_status', ['paid', 'success']);
            })
            ->selectRaw("COALESCE(NULLIF(landing_source, ''), '/') AS source, event_type, session_id")
            ->distinct();
        $this->filterReferral($query, $referralSource);
        $result = [];
        foreach ($query->get() as $row) {
            $result[$row->source][$row->event_type][] = $row->session_id;
        }

        return $result;
    }

    private function leadIdsBySource(CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $query = DB::table('user_analytics')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('event_type', array_map(fn (EventType $event) => $event->value, $this->metrics->leadEvents()))
            ->selectRaw("COALESCE(NULLIF(landing_source, ''), '/') AS source, session_id")
            ->distinct();
        $this->filterReferral($query, $referralSource);
        $result = [];
        foreach ($query->get() as $row) {
            $result[$row->source][] = $row->session_id;
        }

        return array_map(fn ($ids) => collect($ids)->unique()->values(), $result);
    }

    private function paymentStatsBySource(CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): array
    {
        $query = DB::table('user_analytics')
            ->where('event_type', EventType::Payment->value)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('payment_status', ['paid', 'success'])
            ->selectRaw("COALESCE(NULLIF(landing_source, ''), '/') AS source, COUNT(DISTINCT session_id) AS payments, COALESCE(SUM(payment_amount), 0) AS revenue")
            ->groupBy('landing_source');
        $this->filterReferral($query, $referralSource);

        return $query->get()->mapWithKeys(fn ($row) => [$row->source => [
            'payments' => (int) $row->payments,
            'revenue' => (int) $row->revenue,
        ]])->all();
    }

    private function sessionRows(CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): Collection
    {
        $query = DB::table('analytics_sessions')->whereBetween('started_at', [$from, $to]);
        if ($referralSource) {
            $query->where('referral_source', $referralSource);
        }

        return $query->selectRaw("COALESCE(NULLIF(landing_source, ''), '/') AS landing_source, session_id, COALESCE(device_type, 'unknown') AS device_type, duration_seconds, max_scroll_depth, is_bounce")
            ->get();
    }

    private function engagedSessionIdsBySource(CarbonImmutable $from, CarbonImmutable $to, ?string $referralSource): Collection
    {
        $query = DB::table('user_analytics as visits')
            ->join('analytics_sessions as sessions', 'sessions.session_id', '=', 'visits.session_id')
            ->where('visits.event_type', EventType::Visit->value)
            ->whereBetween('visits.created_at', [$from, $to])
            ->where('sessions.is_bounce', false);
        if ($referralSource) {
            $query->where('sessions.referral_source', $referralSource);
        }

        return $query
            ->selectRaw("COALESCE(NULLIF(visits.landing_source, ''), '/') AS source, visits.session_id")
            ->get()
            ->groupBy('source')
            ->map(fn (Collection $rows) => $rows->pluck('session_id')->unique()->values());
    }

    private function filterReferral(Builder $query, ?string $referralSource): void
    {
        if ($referralSource) {
            $query->whereIn('session_id', DB::table('analytics_sessions')
                ->select('session_id')
                ->where('referral_source', $referralSource));
        }
    }

    private function stage(EventType $event, int $value, int $visits, ?int $previous, EventType|string|null $fromEvent, string $branch): array
    {
        return [
            'event' => $event->value,
            'label' => $event->label(),
            'value' => $value,
            'percentage' => $event === EventType::Visit ? 100.0 : $this->rate($value, $visits),
            'transition_pct' => $previous === null ? null : $this->rate($value, $previous),
            'from_event' => $fromEvent instanceof EventType ? $fromEvent->value : $fromEvent,
            'branch' => $branch,
        ];
    }

    private function rate(int|float $value, int|float $base): float
    {
        return $base > 0 ? round(($value / $base) * 100, 2) : 0.0;
    }
}
