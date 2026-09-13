<?php

use App\Analytics\EventType;
use App\Models\Lead;
use App\Models\Order;
use App\Models\UserAnalytic;
use App\Services\DuitkuService;

function callbackPayload(Order $order, array $overrides = []): array
{
    $merchant = 'TEST-MERCHANT';
    $amount = (string) $order->amount;
    $key = 'test-api-key';

    return [...[
        'merchantCode' => $merchant,
        'amount' => $amount,
        'merchantOrderId' => $order->order_number,
        'signature' => md5($merchant.$amount.$order->order_number.$key),
        'resultCode' => '00',
        'reference' => 'REF-'.$order->id,
        'paymentCode' => 'VC',
    ], ...$overrides];
}

beforeEach(function () {
    config()->set('analytics.mode', 'form');
    config()->set('duitku.merchant_code', 'TEST-MERCHANT');
    config()->set('duitku.api_key', 'test-api-key');
});

test('lead is stored and its event is written server side', function () {
    config()->set('analytics.payment_mode', 'none');
    config()->set('analytics.thank_you_path', '/thanks');

    $this->postJson('/lead', ['name' => 'Ayu', 'email' => 'ayu@example.test', 'phone' => '081234567', 'landing_source' => '/hero-1', 'meta_event_id' => 'lead-browser-id'])
        ->assertCreated()->assertJsonPath('redirect_url', '/thanks')->assertJsonPath('event_id', 'lead-browser-id');

    expect(Lead::query()->count())->toBe(1);
    $event = UserAnalytic::query()->where('event_type', EventType::Lead)->firstOrFail();
    expect($event->event_data)->not->toHaveKeys(['email', 'phone']);
});

test('external payment mode returns the configured client url', function () {
    config()->set('analytics.payment_mode', 'external');
    config()->set('analytics.external_payment_url', 'https://client.example/checkout');

    $this->postJson('/lead', ['name' => 'Ayu', 'phone' => '081234567'])
        ->assertCreated()->assertJsonPath('redirect_url', 'https://client.example/checkout');
});

test('internal checkout uses the server price even when client sends another amount', function () {
    config()->set('analytics.payment_mode', 'internal');
    config()->set('analytics.product_price', 199000);
    $lead = Lead::query()->create(['session_id' => 'session-1', 'name' => 'Ayu', 'phone' => '0812', 'status' => 'new']);
    $this->mock(DuitkuService::class, fn ($mock) => $mock->shouldReceive('createInvoice')->once()->andReturn('https://sandbox.duitku.test/pay'));

    $this->postJson('/checkout', ['lead_id' => $lead->id, 'amount' => 1])
        ->assertCreated()->assertJsonPath('payment_url', 'https://sandbox.duitku.test/pay');

    expect(Order::query()->firstOrFail()->amount)->toBe(199000);
});

test('verified successful callback is idempotent and records database amount once', function () {
    $order = Order::query()->create(['order_number' => 'PBM-CALLBACK', 'session_id' => 'session-2', 'name' => 'Ayu', 'phone' => '0812', 'amount' => 199000, 'status' => 'pending']);
    $payload = callbackPayload($order);

    $this->post('/payment/callback', $payload)->assertOk();
    $this->post('/payment/callback', $payload)->assertOk()->assertJson(['message' => 'Already processed']);

    $order->refresh();
    expect($order->status)->toBe('paid')->and($order->paid_at)->not->toBeNull()
        ->and(UserAnalytic::query()->where('event_type', EventType::Payment)->count())->toBe(1)
        ->and(UserAnalytic::query()->where('event_type', EventType::Payment)->value('payment_amount'))->toBe(199000);
});

test('bad signature and mismatched amount leave an order unchanged', function () {
    $order = Order::query()->create(['order_number' => 'PBM-REJECT', 'session_id' => 'session-3', 'name' => 'Ayu', 'phone' => '0812', 'amount' => 199000, 'status' => 'pending']);

    $this->post('/payment/callback', callbackPayload($order, ['signature' => 'bad']))->assertStatus(400);

    $wrongAmount = '1';
    $this->post('/payment/callback', callbackPayload($order, [
        'amount' => $wrongAmount,
        'signature' => md5('TEST-MERCHANT'.$wrongAmount.$order->order_number.'test-api-key'),
    ]))->assertStatus(400);

    expect($order->fresh()->status)->toBe('pending')->and(UserAnalytic::query()->where('event_type', EventType::Payment)->count())->toBe(0);
});
