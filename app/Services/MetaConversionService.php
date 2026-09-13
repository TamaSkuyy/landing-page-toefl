<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class MetaConversionService
{
    public function enabled(): bool
    {
        return (bool) config('meta.enabled') && filled(config('meta.pixel_id')) && filled(config('meta.access_token'));
    }

    public function send(string $eventName, string $eventId, array $data, array $context): ?Response
    {
        if (! $this->enabled()) {
            return null;
        }

        $userData = array_filter([
            'client_ip_address' => $context['ip'] ?? null,
            'client_user_agent' => $context['user_agent'] ?? null,
            'fbp' => $context['fbp'] ?? null,
            'fbc' => $context['fbc'] ?? null,
            'external_id' => isset($context['visitor_id']) ? [hash('sha256', (string) $context['visitor_id'])] : null,
            'em' => isset($data['email']) ? [$this->hashEmail((string) $data['email'])] : null,
            'ph' => isset($data['phone']) ? [$this->hashPhone((string) $data['phone'])] : null,
        ], fn ($value) => $value !== null && $value !== '');

        $customData = array_filter([
            'value' => $data['amount'] ?? $data['value'] ?? null,
            'currency' => $data['currency'] ?? 'IDR',
            'content_name' => $data['product_name'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $payload = ['data' => [[
            'event_name' => $eventName,
            'event_time' => time(),
            'event_id' => $eventId,
            'action_source' => 'website',
            'event_source_url' => $context['url'] ?? null,
            'user_data' => $userData,
            'custom_data' => $customData,
        ]]];

        if (filled(config('meta.test_event_code'))) {
            $payload['test_event_code'] = config('meta.test_event_code');
        }

        return Http::asJson()
            ->connectTimeout(2)
            ->timeout(5)
            ->post(sprintf('https://graph.facebook.com/%s/%s/events?access_token=%s', config('meta.graph_version'), config('meta.pixel_id'), urlencode((string) config('meta.access_token'))), $payload)
            ->throw();
    }

    public function sendDirect(string $eventName, string $eventId, array $data, array $context): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            $response = $this->send($eventName, $eventId, $data, $context);
            $this->log($eventName, $eventId, 'sent', $response?->status(), $response?->json());

            return true;
        } catch (Throwable $exception) {
            $httpStatus = $exception instanceof RequestException ? $exception->response->status() : null;
            $error = $httpStatus ? "Meta CAPI returned HTTP {$httpStatus}." : 'Meta CAPI request could not be completed.';
            $this->log($eventName, $eventId, 'failed', $httpStatus, null, $error);

            return false;
        }
    }

    private function log(string $eventName, string $eventId, string $status, ?int $httpStatus, ?array $response, ?string $error = null): void
    {
        if (! config('meta.log_enabled')) {
            return;
        }

        try {
            DB::table('meta_capi_logs')->insert([
                'event_id' => $eventId,
                'event_name' => $eventName,
                'status' => $status,
                'http_status' => $httpStatus,
                'response' => $response ? json_encode($response) : null,
                'error' => $error,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable) {
            // Audit logging must never interrupt analytics or the visitor journey.
        }
    }

    private function hashEmail(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }

    private function hashPhone(string $phone): string
    {
        return hash('sha256', preg_replace('/\D+/', '', $phone) ?? '');
    }
}
