<?php

namespace App\Http\Controllers;

use App\Services\AbTestingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class LabsController extends Controller
{
    public function index(Request $request, AbTestingService $labs): Response|JsonResponse
    {
        [$from, $to, $range] = $this->range($request);
        $source = $request->string('source')->trim()->value() ?: null;
        $cacheKey = $this->cacheKey($from, $to, $source);
        $report = Cache::remember($cacheKey, now()->addMinute(), fn () => $labs->report($from, $to, $source));
        $availableSources = $labs->availableReferrals($from, $to);

        if ($request->wantsJson()) {
            return response()->json([
                ...$report,
                'available_sources' => $availableSources,
                'meta' => [
                    'mode' => config('analytics.mode'),
                    'range' => $range,
                    'source' => $source,
                    'start_date' => $from->toIso8601String(),
                    'end_date' => $to->toIso8601String(),
                ],
            ]);
        }

        return Inertia::render('admin/labs/index', [
            ...$report,
            'availableSources' => $availableSources,
            'filters' => [
                'range' => $range,
                'source' => $source,
                'start_date' => $from->toDateString(),
                'end_date' => $to->toDateString(),
            ],
            'minimumWinnerVisits' => config('analytics.minimum_winner_visits'),
            'primaryMetric' => config('analytics.primary_metric'),
            'retentionDays' => config('analytics.retention_days'),
        ]);
    }

    public function clearCache(Request $request)
    {
        [$from, $to] = $this->range($request);
        $source = $request->string('source')->trim()->value() ?: null;
        Cache::forget($this->cacheKey($from, $to, $source));

        return back()->with('success', 'Analytics data refreshed.');
    }

    private function range(Request $request): array
    {
        $request->validate([
            'range' => ['nullable', 'in:3,5,7,14,30,90,custom'],
            'start_date' => ['nullable', 'date', 'required_if:range,custom'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'required_if:range,custom'],
            'source' => ['nullable', 'string', 'max:2048'],
        ]);

        $range = (string) $request->input('range', '30');
        $to = CarbonImmutable::now()->endOfDay();
        if ($range === 'custom') {
            $to = CarbonImmutable::parse((string) $request->input('end_date'))->endOfDay()->min($to);
            $earliest = $to->subDays((int) config('analytics.retention_days') - 1)->startOfDay();
            $from = CarbonImmutable::parse((string) $request->input('start_date'))->startOfDay()->max($earliest)->min($to->startOfDay());

            return [$from, $to, $range];
        }

        $days = (int) $range;

        return [$to->subDays($days - 1)->startOfDay(), $to, $range];
    }

    private function cacheKey(CarbonImmutable $from, CarbonImmutable $to, ?string $source): string
    {
        return 'labs:v3:'.config('analytics.mode').':'.$from->toDateString().':'.$to->toDateString().':'.sha1($source ?? 'all');
    }
}
