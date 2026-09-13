<?php

namespace App\Analytics;

use App\Models\AnalyticsSession;
use App\Models\UserAnalytic;
use App\Services\MetaConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TrackingService
{
    public function __construct(private readonly SessionResolver $sessions) {}

    public function track(
        Request $request,
        EventType $event,
        array $eventData = [],
        array $metaData = [],
        ?string $sessionId = null,
        ?string $visitorId = null,
    ): ?UserAnalytic {
        if (! config('analytics.enabled')) {
            return null;
        }

        $data = $this->sanitizeEventData($eventData);
        $data['event_id'] ??= (string) Str::uuid();
        $data['client_id'] = Str::limit((string) config('analytics.client_id'), 255, '');
        $session = $this->touchSession($request, $event, $data, $sessionId, $visitorId);
        $eventId = Arr::get($data, 'event_id');

        if (is_string($eventId) && UserAnalytic::query()->where('event_data->event_id', $eventId)->exists()) {
            return null;
        }

        $attributes = [
            'session_id' => $session->session_id,
            'visitor_id' => $session->visitor_id,
            'event_type' => $event,
            'event_data' => $data,
            'referral_source' => $this->externalString($data['referral_source'] ?? $request->headers->get('referer'), 2048),
            'utm_source' => $this->externalString($data['utm_source'] ?? null, 255),
            'utm_medium' => $this->externalString($data['utm_medium'] ?? null, 255),
            'utm_campaign' => $this->externalString($data['utm_campaign'] ?? null, 255),
            'utm_content' => $this->externalString($data['utm_content'] ?? null, 255),
            'utm_term' => $this->externalString($data['utm_term'] ?? null, 255),
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip()) : null,
            'user_agent' => $this->externalString($request->userAgent(), 1024),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'created_at' => now(),
        ];

        if (DB::getDriverName() !== 'mysql') {
            $attributes += [
                'landing_source' => $data['landing_source'] ?? null,
                'scroll_depth' => $data['depth'] ?? null,
                'section_id' => $data['section'] ?? null,
                'cta_zone' => $data['zone'] ?? null,
                'cta_action' => $data['action'] ?? null,
                'payment_status' => $data['status'] ?? null,
                'payment_amount' => $data['amount'] ?? null,
            ];
        }

        $analytic = UserAnalytic::query()->create($attributes);
        $metaEvent = app(MetaEventMapper::class)->map($event);
        if ($metaEvent) {
            app(MetaConversionService::class)->sendDirect($metaEvent, (string) $data['event_id'], [...$data, ...$metaData], [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'fbp' => $request->cookie('_fbp'),
                'fbc' => $request->cookie('_fbc'),
                'visitor_id' => $session->visitor_id,
            ]);
        }

        return $analytic;
    }

    public function heartbeat(Request $request, int $durationSeconds, int $maxScrollDepth, ?string $landingSource): AnalyticsSession
    {
        $session = $this->baseSession($request, ['landing_source' => $landingSource]);
        $session->duration_seconds = max((int) $session->duration_seconds, $durationSeconds);
        $session->max_scroll_depth = max((int) $session->max_scroll_depth, $maxScrollDepth);
        $session->is_engaged = $this->wasEngaged($session)
            || $durationSeconds >= (int) config('analytics.engagement_threshold')
            || $session->max_scroll_depth > (int) config('analytics.scroll_bounce_threshold');
        $session->is_bounce = ! $session->is_engaged;
        $session->last_seen_at = now();
        $session->save();

        return $session;
    }

    private function touchSession(Request $request, EventType $event, array $data, ?string $sessionId = null, ?string $visitorId = null): AnalyticsSession
    {
        $session = $this->baseSession($request, $data, $sessionId, $visitorId);
        $depth = min(100, max(0, (int) ($data['depth'] ?? 0)));
        $session->max_scroll_depth = max((int) $session->max_scroll_depth, $depth);

        $conversionSignal = $event->isFunnelEvent() && $event !== EventType::Visit;
        $session->is_engaged = $this->wasEngaged($session)
            || $event === EventType::Engagement
            || $conversionSignal
            || $session->max_scroll_depth > (int) config('analytics.scroll_bounce_threshold');
        $session->is_bounce = ! $session->is_engaged;
        $session->last_seen_at = now();
        $session->save();

        return $session;
    }

    private function wasEngaged(AnalyticsSession $session): bool
    {
        return (bool) $session->is_engaged
            || ($session->exists && ! (bool) $session->is_bounce);
    }

    private function baseSession(Request $request, array $data, ?string $sessionId = null, ?string $visitorId = null): AnalyticsSession
    {
        $session = AnalyticsSession::query()->firstOrNew(['session_id' => $sessionId ?: $this->sessions->sessionId($request)]);
        $session->visitor_id ??= $visitorId ?: $this->sessions->visitorId($request);
        $session->landing_source ??= $this->externalString($data['landing_source'] ?? null, 255);
        $session->referral_source ??= $this->externalString($data['referral_source'] ?? $request->headers->get('referer'), 2048);
        $session->started_at ??= now();
        $session->last_seen_at = now();

        if (! $session->device_type) {
            [$session->device_type, $session->browser, $session->os] = $this->parseUserAgent((string) $request->userAgent());
        }

        return $session;
    }

    /** @return array{string, string, string} */
    private function parseUserAgent(string $agent): array
    {
        $device = preg_match('/Mobile|Android|iPhone/i', $agent) ? 'mobile' : (preg_match('/Tablet|iPad/i', $agent) ? 'tablet' : 'desktop');
        $browser = preg_match('/Edg\//', $agent) ? 'Edge' : (preg_match('/Chrome\//', $agent) ? 'Chrome' : (preg_match('/Firefox\//', $agent) ? 'Firefox' : (preg_match('/Safari\//', $agent) ? 'Safari' : 'Other')));
        $os = preg_match('/Windows/i', $agent) ? 'Windows' : (preg_match('/Android/i', $agent) ? 'Android' : (preg_match('/iPhone|iPad/i', $agent) ? 'iOS' : (preg_match('/Mac OS/i', $agent) ? 'macOS' : (preg_match('/Linux/i', $agent) ? 'Linux' : 'Other'))));

        return [$device, $browser, $os];
    }

    private function sanitizeEventData(array $data): array
    {
        $limits = ['text' => 500, 'landing_source' => 255, 'section' => 255, 'zone' => 64, 'action' => 64, 'event_id' => 100];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeEventData($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->externalString($value, $limits[$key] ?? 2048);
            }
        }

        return $data;
    }

    private function externalString(mixed $value, int $limit): ?string
    {
        return is_string($value) && $value !== '' ? Str::limit($value, $limit, '') : null;
    }
}
