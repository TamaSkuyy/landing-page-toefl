<?php

namespace App\Analytics;

use Illuminate\Http\Request;

final class SessionResolver
{
    public function sessionId(Request $request): string
    {
        return $request->session()->getId();
    }

    public function visitorId(Request $request): ?string
    {
        return $request->attributes->get('pbm_visitor_id') ?: $request->cookie('pbm_vid');
    }
}
