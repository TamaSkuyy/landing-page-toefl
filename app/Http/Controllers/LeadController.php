<?php

namespace App\Http\Controllers;

use App\Analytics\EventType;
use App\Analytics\SessionResolver;
use App\Analytics\TrackingService;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request, SessionResolver $sessions, TrackingService $tracking): JsonResponse
    {
        $validated = $request->validated();
        $eventId = $validated['meta_event_id'] ?? (string) Str::uuid();
        abort_if(config('analytics.payment_mode') === 'external' && blank(config('analytics.external_payment_url')), 422, 'EXTERNAL_PAYMENT_URL is not configured.');

        $lead = DB::transaction(function () use ($request, $validated, $sessions, $tracking, $eventId) {
            $lead = Lead::query()->create([
                'session_id' => $sessions->sessionId($request),
                'visitor_id' => $sessions->visitorId($request),
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'],
                'landing_source' => $validated['landing_source'] ?? null,
                'utm_source' => $validated['utm_source'] ?? null,
                'utm_medium' => $validated['utm_medium'] ?? null,
                'utm_campaign' => $validated['utm_campaign'] ?? null,
                'utm_content' => $validated['utm_content'] ?? null,
                'utm_term' => $validated['utm_term'] ?? null,
                'extra' => $validated['extra'] ?? null,
                'status' => 'new',
            ]);

            $tracking->track($request, EventType::Lead, [
                'event_id' => $eventId,
                'lead_id' => $lead->id,
                'landing_source' => $lead->landing_source,
            ], ['email' => $lead->email, 'phone' => $lead->phone]);

            return $lead;
        });

        $redirect = match (config('analytics.payment_mode')) {
            'internal' => route('checkout.store', absolute: false),
            'external' => (string) config('analytics.external_payment_url'),
            default => (string) config('analytics.thank_you_path', '/terima-kasih'),
        };

        return response()->json(['lead_id' => $lead->id, 'redirect_url' => $redirect, 'event_id' => $eventId], 201);
    }
}
