<?php

namespace App\Http\Controllers;

use App\Analytics\TrackingService;
use App\Http\Requests\TrackEventRequest;
use App\Models\UserAnalytic;
use App\Services\AnalyticsMetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request, AnalyticsMetricsService $metrics): Response
    {
        [$from, $to, $days] = $this->range($request);

        return Inertia::render('admin/analytics', [
            ...$metrics->dashboard($from, $to),
            'range' => $days,
            'retentionDays' => config('analytics.retention_days'),
        ]);
    }

    public function track(TrackEventRequest $request, TrackingService $tracking): JsonResponse
    {
        $accepted = 0;
        $duplicates = 0;

        foreach ($request->events() as $payload) {
            $stored = $tracking->track($request, $payload['event_type'], $payload['event_data']);
            $stored ? $accepted++ : $duplicates++;
        }

        return response()->json(compact('accepted', 'duplicates'), 201);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->range($request);
        $rows = UserAnalytic::query()->whereBetween('created_at', [$from, $to])->orderBy('created_at')->cursor();

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['created_at', 'session_id', 'visitor_id', 'event_type', 'landing_source', 'event_data']);
            foreach ($rows as $row) {
                fputcsv($output, [$row->created_at->toIso8601String(), $row->session_id, $row->visitor_id, $row->event_type->value, $row->landing_source, json_encode($row->event_data)]);
            }
            fclose($output);
        }, 'analytics.csv', ['Content-Type' => 'text/csv']);
    }

    private function range(Request $request): array
    {
        $allowed = [3, 5, 7, 14, 30, 90];
        $days = (int) $request->integer('range', 30);
        if (! in_array($days, $allowed, true)) {
            $days = 30;
        }
        $to = CarbonImmutable::now()->endOfDay();

        return [$to->subDays($days - 1)->startOfDay(), $to, $days];
    }
}
