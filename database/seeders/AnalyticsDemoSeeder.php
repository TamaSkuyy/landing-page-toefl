<?php

namespace Database\Seeders;

use App\Analytics\EventType;
use App\Models\AnalyticsSession;
use App\Models\UserAnalytic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyticsDemoSeeder extends Seeder
{
    public function run(): void
    {
        UserAnalytic::query()->where('session_id', 'like', 'demo-%')->delete();
        AnalyticsSession::query()->where('session_id', 'like', 'demo-%')->delete();

        foreach (['/hero-1', '/hero-2'] as $sourceIndex => $source) {
            for ($i = 1; $i <= 60; $i++) {
                $sessionId = "demo-{$sourceIndex}-{$i}";
                $visitorId = (string) Str::uuid();
                $createdAt = now()->subDays($i % 28)->setTime(10 + ($i % 8), $i % 60);
                $scroll = min(90, 25 * ($i % 5));

                AnalyticsSession::query()->create([
                    'session_id' => $sessionId,
                    'visitor_id' => $visitorId,
                    'landing_source' => $source,
                    'referral_source' => $i % 3 === 0 ? 'instagram.com' : null,
                    'device_type' => $i % 2 ? 'mobile' : 'desktop',
                    'browser' => 'Chrome',
                    'os' => $i % 2 ? 'Android' : 'Windows',
                    'duration_seconds' => 10 + ($i * 3),
                    'max_scroll_depth' => $scroll,
                    'is_engaged' => $i <= 48,
                    'is_bounce' => $i > 48,
                    'started_at' => $createdAt,
                    'last_seen_at' => $createdAt->addMinutes(2),
                ]);

                $this->event($sessionId, $visitorId, $source, EventType::Visit, $createdAt);
                if ($i <= 48) {
                    $this->event($sessionId, $visitorId, $source, EventType::Engagement, $createdAt->addSeconds(15));
                }
                if ($i <= 38) {
                    $this->event($sessionId, $visitorId, $source, EventType::Intent, $createdAt->addSeconds(30), ['zone' => 'hero', 'action' => 'scroll']);
                }
                if ($scroll >= 25) {
                    $this->event($sessionId, $visitorId, $source, EventType::Scroll, $createdAt->addSeconds(40), ['depth' => $scroll]);
                }
                if ($i <= 42) {
                    $this->event($sessionId, $visitorId, $source, EventType::SectionView, $createdAt->addSeconds(35), ['section' => 'pricing']);
                }

                if (config('analytics.mode') === 'ctwa') {
                    if ($i <= (20 + $sourceIndex * 5)) {
                        $this->event($sessionId, $visitorId, $source, EventType::WhatsappLead, $createdAt->addMinute(), ['zone' => 'pricing', 'action' => 'whatsapp']);
                    }
                    if ($i <= 7) {
                        $this->event($sessionId, $visitorId, $source, EventType::DirectCheckout, $createdAt->addMinute(), ['zone' => 'pricing', 'action' => 'external_checkout']);
                    }
                } else {
                    if ($i <= 32) {
                        $this->event($sessionId, $visitorId, $source, EventType::FormStart, $createdAt->addSeconds(45));
                    }
                    if ($i <= (18 + $sourceIndex * 4)) {
                        $this->event($sessionId, $visitorId, $source, EventType::Lead, $createdAt->addMinute());
                    }
                    if ($i <= (9 + $sourceIndex * 3) && config('analytics.payment_mode') === 'internal') {
                        $this->event($sessionId, $visitorId, $source, EventType::Payment, $createdAt->addMinutes(2), ['status' => 'paid', 'amount' => 199000]);
                    }
                }
            }
        }
    }

    private function event(string $sessionId, string $visitorId, string $source, EventType $type, mixed $createdAt, array $data = []): void
    {
        $data = ['event_id' => (string) Str::uuid(), 'landing_source' => $source, ...$data];
        $attributes = ['session_id' => $sessionId, 'visitor_id' => $visitorId, 'event_type' => $type, 'event_data' => $data, 'created_at' => $createdAt];
        if (DB::getDriverName() !== 'mysql') {
            $attributes += ['landing_source' => $source, 'scroll_depth' => $data['depth'] ?? null, 'section_id' => $data['section'] ?? null, 'cta_zone' => $data['zone'] ?? null, 'cta_action' => $data['action'] ?? null, 'payment_status' => $data['status'] ?? null, 'payment_amount' => $data['amount'] ?? null];
        }
        UserAnalytic::query()->create($attributes);
    }
}
