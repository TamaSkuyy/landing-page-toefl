<?php

namespace App\Http\Controllers;

use App\Analytics\TrackingService;
use App\Http\Requests\HeartbeatRequest;
use Illuminate\Http\Response;

class HeartbeatController extends Controller
{
    public function __invoke(HeartbeatRequest $request, TrackingService $tracking): Response
    {
        $tracking->heartbeat(
            $request,
            (int) $request->validated('duration_seconds'),
            (int) $request->validated('max_scroll_depth', 0),
            $request->validated('landing_source'),
        );

        return response()->noContent();
    }
}
