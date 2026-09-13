<?php

use App\Analytics\EventType;
use App\Analytics\MetaEventMapper;
use App\Services\MetaConversionService;
use Illuminate\Support\Facades\Http;

test('meta mapping is centralized', function () {
    $mapper = app(MetaEventMapper::class);
    expect($mapper->map(EventType::Visit))->toBe('PageView')
        ->and($mapper->map(EventType::WhatsappLead))->toBe('Lead')
        ->and($mapper->map(EventType::Scroll))->toBeNull();
});

test('tracking sends capi directly with the browser event id', function () {
    config()->set('meta.enabled', true);
    config()->set('meta.pixel_id', '123');
    config()->set('meta.access_token', 'secret');
    Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);

    $this->postJson('/analytics/track', [
        'event_type' => EventType::Visit->value,
        'event_data' => ['event_id' => 'shared-event-id'],
    ])->assertCreated();

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $data['data'][0]['event_id'] === 'shared-event-id'
            && $data['data'][0]['event_name'] === 'PageView';
    });
});

test('capi hashes pii and includes test event code', function () {
    config()->set('meta.enabled', true);
    config()->set('meta.pixel_id', '123');
    config()->set('meta.access_token', 'secret');
    config()->set('meta.test_event_code', 'TEST42');
    Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);

    app(MetaConversionService::class)->send('Lead', 'same-id', [
        'email' => ' User@Example.COM ',
        'phone' => '+62 812-3456',
    ], ['url' => 'https://example.test', 'visitor_id' => 'visitor']);

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $data['test_event_code'] === 'TEST42'
            && $data['data'][0]['event_id'] === 'same-id'
            && $data['data'][0]['user_data']['em'][0] === hash('sha256', 'user@example.com')
            && $data['data'][0]['user_data']['ph'][0] === hash('sha256', '628123456');
    });
});

test('missing access token disables capi silently', function () {
    config()->set('meta.access_token', null);
    Http::fake();

    expect(app(MetaConversionService::class)->send('Lead', 'id', [], []))->toBeNull();
    Http::assertNothingSent();
});

test('failed direct capi requests do not break internal tracking', function () {
    config()->set('meta.enabled', true);
    config()->set('meta.pixel_id', '123');
    config()->set('meta.access_token', 'secret');
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'temporary'], 503)]);

    $this->postJson('/analytics/track', [
        'event_type' => EventType::Visit->value,
        'event_data' => ['event_id' => 'failed-meta-event-id'],
    ])->assertCreated();

    $this->assertDatabaseHas('user_analytics', [
        'event_type' => EventType::Visit->value,
    ]);
});
