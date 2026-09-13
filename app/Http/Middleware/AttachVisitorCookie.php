<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AttachVisitorCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $visitorId = $request->cookie('pbm_vid');

        if (! is_string($visitorId) || ! Str::isUuid($visitorId)) {
            $visitorId = (string) Str::uuid();
        }

        $request->attributes->set('pbm_visitor_id', $visitorId);
        $response = $next($request);

        if ($request->cookie('pbm_vid') !== $visitorId) {
            $response->headers->setCookie(cookie(
                name: 'pbm_vid',
                value: $visitorId,
                minutes: 60 * 24 * 365,
                secure: $request->isSecure(),
                httpOnly: true,
                sameSite: 'lax',
            ));
        }

        return $response;
    }
}
