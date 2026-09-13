<?php

namespace App\Services;

use App\Analytics\EventType;
use App\Models\UserAnalytic;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsMetricsService
{
    public function dashboard(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $mode = (string) config('analytics.mode');
        $counts = $this->eventCounts($from, $to);
        $visitIds = $this->eventSessionIds($from, $to, EventType::Visit);
        $visits = $visitIds->count();
        $engagements = $this->engagedSessionIds($from, $to)->count();
        $intents = $counts[EventType::Intent->value] ?? 0;
        $bounces = max(0, $visits - $engagements);
        $totalLeads = count($this->leadSessionIds($from, $to));
        $payments = $this->successfulPayments($from, $to)
            ->distinct('session_id')
            ->count('session_id');
        $revenue = (int) $this->successfulPayments($from, $to)->sum('payment_amount');
        $whatsappLeads = $counts[EventType::WhatsappLead->value] ?? 0;
        $directCheckouts = $counts[EventType::DirectCheckout->value] ?? 0;
        $formStarts = $counts[EventType::FormStart->value] ?? 0;

        $stats = [
            'page_views' => UserAnalytic::query()
                ->where('event_type', EventType::Visit)
                ->whereBetween('created_at', [$from, $to])
                ->count(),
            'visits' => $visits,
            'engagements' => $engagements,
            'engagement_rate' => $this->rate($engagements, $visits),
            'bounces' => $bounces,
            'bounce_rate' => $this->rate($bounces, $visits),
            'intents' => $intents,
            'intent_rate' => $this->rate($intents, $visits),
            'whatsapp_leads' => $whatsappLeads,
            'whatsapp_rate' => $this->rate($whatsappLeads, $visits),
            'direct_checkouts' => $directCheckouts,
            'direct_checkout_rate' => $this->rate($directCheckouts, $visits),
            'form_starts' => $formStarts,
            'form_start_rate' => $this->rate($formStarts, $visits),
            'total_leads' => $totalLeads,
            'lead_cr' => $this->rate($totalLeads, $visits),
            'payments' => $payments,
            'sales_cr' => $this->rate($payments, $visits),
            'lead_to_payment_rate' => $this->rate($payments, $totalLeads),
            'revenue' => $revenue,
            'rpv' => $visits > 0 ? round($revenue / $visits) : 0,
        ];

        return [
            'mode' => $mode,
            'stats' => $stats,
            'daily' => $this->daily($from, $to),
            'referrals' => $this->referrals($from, $to),
            'funnel' => $this->hierarchicalFunnel($from, $to),
            'insights' => $this->insights($stats, $from, $to),
        ];
    }

    /** @return list<string> */
    public function leadSessionIds(CarbonImmutable $from, CarbonImmutable $to, ?string $landingSource = null): array
    {
        $query = UserAnalytic::query()->whereBetween('created_at', [$from, $to]);

        if ($landingSource !== null) {
            $query->where('landing_source', $landingSource);
        }

        $query->whereIn('event_type', $this->leadEvents());

        return $query->distinct()->pluck('session_id')->all();
    }

    /** @return list<EventType> */
    public function leadEvents(): array
    {
        return config('analytics.mode') === 'ctwa'
            ? [EventType::WhatsappLead, EventType::DirectCheckout]
            : [EventType::Lead];
    }

    /** @return array<string, int> */
    private function eventCounts(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return DB::table('user_analytics')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('event_type, COUNT(DISTINCT session_id) AS total')
            ->groupBy('event_type')
            ->pluck('total', 'event_type')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function successfulPayments(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return UserAnalytic::query()
            ->where('event_type', EventType::Payment)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('payment_status', ['paid', 'success']);
    }

    private function daily(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $events = config('analytics.mode') === 'ctwa'
            ? [EventType::Visit, EventType::Engagement, EventType::Intent, EventType::WhatsappLead, EventType::DirectCheckout]
            : [EventType::Visit, EventType::Engagement, EventType::Intent, EventType::FormStart, EventType::Lead, EventType::Payment];
        $eventValues = array_map(fn (EventType $event) => $event->value, $events);
        $rows = DB::table('user_analytics')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('event_type', $eventValues)
            ->where(function ($query) {
                $query->where('event_type', '!=', EventType::Payment->value)
                    ->orWhereIn('payment_status', ['paid', 'success']);
            })
            ->selectRaw('DATE(created_at) AS date, event_type, COUNT(DISTINCT session_id) AS total')
            ->groupByRaw('DATE(created_at), event_type')
            ->get()
            ->groupBy('date');
        $leadRows = DB::table('user_analytics')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('event_type', array_map(fn (EventType $event) => $event->value, $this->leadEvents()))
            ->selectRaw('DATE(created_at) AS date, COUNT(DISTINCT session_id) AS total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'date');
        $engagementRows = DB::table('user_analytics as events')
            ->join('analytics_sessions as sessions', 'sessions.session_id', '=', 'events.session_id')
            ->where('events.event_type', EventType::Visit->value)
            ->where('sessions.is_bounce', false)
            ->whereBetween('events.created_at', [$from, $to])
            ->selectRaw('DATE(events.created_at) AS date, COUNT(DISTINCT events.session_id) AS total')
            ->groupByRaw('DATE(events.created_at)')
            ->pluck('total', 'date');

        $result = [];
        for ($date = $from->startOfDay(); $date->lte($to); $date = $date->addDay()) {
            $key = $date->toDateString();
            $item = ['date' => $key];
            foreach ($eventValues as $event) {
                $item[$event] = 0;
            }
            foreach ($rows->get($key, collect()) as $row) {
                $item[$row->event_type] = (int) $row->total;
            }
            $item[EventType::Engagement->value] = (int) ($engagementRows[$key] ?? 0);
            $item['total_lead'] = (int) ($leadRows[$key] ?? 0);
            $result[] = $item;
        }

        return $result;
    }

    private function referrals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return DB::table('user_analytics')
            ->where('event_type', EventType::Visit->value)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("COALESCE(NULLIF(referral_source, ''), 'Direct') AS source, COUNT(DISTINCT session_id) AS visits")
            ->groupBy('referral_source')
            ->orderByDesc('visits')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['source' => $row->source, 'visits' => (int) $row->visits])
            ->all();
    }

    private function hierarchicalFunnel(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $visitIds = $this->eventSessionIds($from, $to, EventType::Visit);
        $engagedIds = $visitIds->intersect($this->engagedSessionIds($from, $to));
        $intentIds = $engagedIds->intersect($this->eventSessionIds($from, $to, EventType::Intent));
        $stages = [
            $this->stage(EventType::Visit, $visitIds->count(), $visitIds->count(), null, null, 'main'),
            $this->stage(EventType::Engagement, $engagedIds->count(), $visitIds->count(), $visitIds->count(), EventType::Visit, 'main'),
            $this->stage(EventType::Intent, $intentIds->count(), $visitIds->count(), $engagedIds->count(), EventType::Engagement, 'main'),
        ];

        if (config('analytics.mode') === 'ctwa') {
            foreach ([EventType::DirectCheckout, EventType::WhatsappLead] as $branch) {
                $ids = $intentIds->intersect($this->eventSessionIds($from, $to, $branch));
                $stages[] = $this->stage($branch, $ids->count(), $visitIds->count(), $intentIds->count(), EventType::Intent, $branch->value);
            }

            return $stages;
        }

        $eligible = $intentIds;
        foreach ([EventType::FormStart, EventType::Lead] as $event) {
            $previous = $eligible->count();
            $eligible = $eligible->intersect($this->eventSessionIds($from, $to, $event));
            $fromEvent = $stages[array_key_last($stages)]['event'];
            $stages[] = $this->stage($event, $eligible->count(), $visitIds->count(), $previous, $fromEvent, 'main');
        }

        if (config('analytics.capabilities.payment')) {
            $previous = $eligible->count();
            $paymentIds = $this->successfulPayments($from, $to)->distinct()->pluck('session_id');
            $eligible = $eligible->intersect($paymentIds);
            $stages[] = $this->stage(EventType::Payment, $eligible->count(), $visitIds->count(), $previous, EventType::Lead, 'main');
        }

        return $stages;
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

    private function insights(array $stats, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $topReferral = $this->referrals($from, $to)[0] ?? ['source' => '—', 'visits' => 0];

        return [
            'top_referral' => $topReferral,
            'primary_channel' => $stats['whatsapp_leads'] >= $stats['direct_checkouts'] ? EventType::WhatsappLead->label() : EventType::DirectCheckout->label(),
            'primary_channel_value' => max($stats['whatsapp_leads'], $stats['direct_checkouts']),
            'lead_cr' => $stats['lead_cr'],
            'rpv' => $stats['rpv'],
        ];
    }

    private function eventSessionIds(CarbonImmutable $from, CarbonImmutable $to, EventType $event): Collection
    {
        $query = $event === EventType::Payment
            ? $this->successfulPayments($from, $to)
            : UserAnalytic::query()->where('event_type', $event)->whereBetween('created_at', [$from, $to]);

        return $query->distinct()->pluck('session_id');
    }

    private function engagedSessionIds(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return DB::table('user_analytics as visits')
            ->join('analytics_sessions as sessions', 'sessions.session_id', '=', 'visits.session_id')
            ->where('visits.event_type', EventType::Visit->value)
            ->whereBetween('visits.created_at', [$from, $to])
            ->where('sessions.is_bounce', false)
            ->distinct()
            ->pluck('visits.session_id');
    }

    private function rate(int|float $value, int|float $base): float
    {
        return $base > 0 ? round(($value / $base) * 100, 2) : 0.0;
    }
}
