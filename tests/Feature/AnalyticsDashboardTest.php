<?php

use App\Analytics\EventType;
use App\Models\AnalyticsSession;
use App\Models\User;
use App\Models\UserAnalytic;
use App\Services\AbTestingService;
use App\Services\AnalyticsMetricsService;
use Carbon\CarbonImmutable;
use Database\Seeders\AnalyticsDemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function dashboardEvent(string $session, EventType $type, array $data = []): void
{
    UserAnalytic::query()->create([
        'session_id' => $session,
        'event_type' => $type,
        'event_data' => $data,
        'landing_source' => '/test',
        'cta_zone' => $data['zone'] ?? null,
        'cta_action' => $data['action'] ?? null,
        'payment_status' => $data['status'] ?? null,
        'payment_amount' => $data['amount'] ?? null,
        'created_at' => now(),
    ]);
}

function dashboardSession(string $session): void
{
    AnalyticsSession::query()->create([
        'session_id' => $session,
        'landing_source' => '/test',
        'device_type' => 'mobile',
        'duration_seconds' => 45,
        'max_scroll_depth' => 75,
        'is_engaged' => true,
        'is_bounce' => false,
        'started_at' => now(),
        'last_seen_at' => now(),
    ]);
}

test('ctwa total lead counts a session once across both lead actions', function () {
    config()->set('analytics.mode', 'ctwa');
    dashboardEvent('one', EventType::Visit);
    dashboardEvent('one', EventType::WhatsappLead);
    dashboardEvent('one', EventType::DirectCheckout);

    $report = app(AnalyticsMetricsService::class)->dashboard(CarbonImmutable::now()->startOfDay(), CarbonImmutable::now()->endOfDay());
    expect($report['stats']['total_leads'])->toBe(1);
});

test('dashboard engagement is the exact inverse of bounce', function () {
    config()->set('analytics.mode', 'ctwa');

    foreach (['engaged', 'quick-action'] as $session) {
        dashboardSession($session);
        dashboardEvent($session, EventType::Visit);
    }

    AnalyticsSession::query()->create([
        'session_id' => 'bounce',
        'landing_source' => '/test',
        'device_type' => 'desktop',
        'duration_seconds' => 4,
        'max_scroll_depth' => 10,
        'is_engaged' => false,
        'is_bounce' => true,
        'started_at' => now(),
        'last_seen_at' => now(),
    ]);
    dashboardEvent('bounce', EventType::Visit);
    dashboardEvent('quick-action', EventType::Intent);

    $from = CarbonImmutable::now()->startOfDay();
    $to = CarbonImmutable::now()->endOfDay();
    $dashboard = app(AnalyticsMetricsService::class)->dashboard($from, $to);
    $labs = app(AbTestingService::class)->report($from, $to);

    expect($dashboard['stats'])
        ->visits->toBe(3)
        ->engagements->toBe(2)
        ->bounces->toBe(1)
        ->engagement_rate->toBe(66.67)
        ->bounce_rate->toBe(33.33)
        ->and($dashboard['daily'][0][EventType::Engagement->value])->toBe(2)
        ->and($dashboard['stats']['engagement_rate'] + $dashboard['stats']['bounce_rate'])->toBe(100.0)
        ->and($labs['performance'][0]['engagements'])->toBe(2)
        ->and($labs['performance'][0]['bounces'])->toBe(1)
        ->and($labs['performance'][0]['engagement_rate'] + $labs['performance'][0]['bounce_rate'])->toBe(100.0);
});

test('split funnel is hierarchical', function () {
    config()->set('analytics.mode', 'ctwa');
    foreach (['one', 'two'] as $session) {
        dashboardEvent($session, EventType::Visit);
    }
    dashboardEvent('one', EventType::Engagement);
    dashboardEvent('two', EventType::Intent);
    dashboardEvent('two', EventType::WhatsappLead);

    $report = app(AbTestingService::class)->report(CarbonImmutable::now()->startOfDay(), CarbonImmutable::now()->endOfDay());
    $values = collect($report['funnel'][0]['stages'])->pluck('value');
    expect($values->zip($values->slice(1))->every(fn ($pair) => $pair[1] === null || $pair[1] <= $pair[0]))->toBeTrue();
});

test('ctwa dashboard exposes both outcomes as sibling funnel branches', function () {
    config()->set('analytics.mode', 'ctwa');

    foreach (['whatsapp-path', 'checkout-path', 'direct-path'] as $session) {
        dashboardSession($session);
        dashboardEvent($session, EventType::Visit);
    }

    foreach (['whatsapp-path', 'checkout-path'] as $session) {
        dashboardEvent($session, EventType::Engagement);
        dashboardEvent($session, EventType::Intent);
    }
    dashboardEvent('whatsapp-path', EventType::WhatsappLead, ['zone' => 'pricing', 'action' => 'whatsapp']);
    dashboardEvent('checkout-path', EventType::DirectCheckout, ['zone' => 'pricing', 'action' => 'external_checkout']);
    dashboardEvent('direct-path', EventType::WhatsappLead, ['zone' => 'floating', 'action' => 'whatsapp']);

    $from = CarbonImmutable::now()->startOfDay();
    $to = CarbonImmutable::now()->endOfDay();
    $dashboard = app(AnalyticsMetricsService::class)->dashboard($from, $to);
    $stages = collect($dashboard['funnel'])->keyBy('event');

    expect($dashboard['stats'])
        ->whatsapp_leads->toBe(2)
        ->direct_checkouts->toBe(1)
        ->total_leads->toBe(3)
        ->lead_cr->toBe(100.0)
        ->and($dashboard['daily'][0]['total_lead'])->toBe(3)
        ->and($stages[EventType::WhatsappLead->value]['value'])->toBe(1)
        ->and($stages[EventType::DirectCheckout->value]['value'])->toBe(1)
        ->and($stages[EventType::WhatsappLead->value]['from_event'])->toBe(EventType::Intent->value)
        ->and($stages[EventType::DirectCheckout->value]['from_event'])->toBe(EventType::Intent->value);

    $labs = app(AbTestingService::class)->report($from, $to);
    expect($labs['performance'][0])
        ->whatsapp_leads->toBe(2)
        ->direct_checkouts->toBe(1)
        ->total_leads->toBe(3)
        ->and($labs['quality'])->toHaveCount(1)
        ->and($labs['devices'][0]['whatsapp_leads'])->toBe(2);
});

test('admin dashboards render seeded data and clamp the date range', function () {
    $this->withoutVite();
    config()->set('analytics.mode', 'ctwa');
    $this->seed(AnalyticsDemoSeeder::class);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin?range=365')->assertInertia(fn (Assert $page) => $page
        ->component('admin/analytics')->where('range', 30)->has('daily')->has('funnel')->has('insights')->where('mode', 'ctwa'));

    $this->actingAs($admin)->get('/admin/labs')->assertInertia(fn (Assert $page) => $page
        ->component('admin/labs/index')->has('performance', 2)->has('section_heatmap')->has('quality', 2)->where('filters.range', '30'));
});

test('labs custom range is clamped to analytics retention', function () {
    $this->withoutVite();
    $admin = User::factory()->admin()->create();
    $today = CarbonImmutable::now()->toDateString();
    $earliest = CarbonImmutable::now()->endOfDay()->subDays(89)->startOfDay()->toDateString();

    $this->actingAs($admin)
        ->get("/admin/labs?range=custom&start_date=2020-01-01&end_date={$today}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.range', 'custom')
            ->where('filters.start_date', $earliest)
            ->where('filters.end_date', $today));
});

test('form dashboard keeps the sequential payment funnel and revenue metrics', function () {
    config()->set('analytics.mode', 'form');
    config()->set('analytics.capabilities.payment', true);
    config()->set('analytics.capabilities.revenue', true);
    dashboardSession('form-path');

    foreach ([EventType::Visit, EventType::Engagement, EventType::Intent, EventType::FormStart, EventType::Lead] as $event) {
        dashboardEvent('form-path', $event);
    }
    dashboardEvent('form-path', EventType::Payment, ['status' => 'paid', 'amount' => 250000]);
    dashboardEvent('failed-payment', EventType::Payment, ['status' => 'failed', 'amount' => 999999]);

    $from = CarbonImmutable::now()->startOfDay();
    $to = CarbonImmutable::now()->endOfDay();
    $dashboard = app(AnalyticsMetricsService::class)->dashboard($from, $to);

    expect($dashboard['stats'])
        ->payments->toBe(1)
        ->revenue->toBe(250000)
        ->total_leads->toBe(1)
        ->and(collect($dashboard['funnel'])->pluck('event')->all())->toBe([
            EventType::Visit->value,
            EventType::Engagement->value,
            EventType::Intent->value,
            EventType::FormStart->value,
            EventType::Lead->value,
            EventType::Payment->value,
        ]);

    $performance = app(AbTestingService::class)->report($from, $to)['performance'][0];
    expect($performance)
        ->payments->toBe(1)
        ->revenue->toBe(250000)
        ->form_starts->toBe(1);
});
