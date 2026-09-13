<?php

namespace App\Http\Middleware;

use App\Analytics\EventType;
use App\Analytics\MetaEventMapper;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'tracking' => [
                'enabled' => config('analytics.enabled'),
                'mode' => config('analytics.mode'),
                'pageUrl' => $request->getRequestUri(),
                'paymentMode' => config('analytics.payment_mode'),
                'visitorId' => $request->attributes->get('pbm_visitor_id'),
                'eventLabels' => EventType::labelsFor((string) config('analytics.mode')),
                'capabilities' => config('analytics.capabilities'),
                'engagementThreshold' => config('analytics.engagement_threshold'),
                'heartbeatInterval' => config('analytics.heartbeat_interval'),
                'sectionViewEnabled' => config('analytics.section_view_enabled'),
                'metaEvents' => app(MetaEventMapper::class)->forMode((string) config('analytics.mode')),
            ],
        ];
    }
}
