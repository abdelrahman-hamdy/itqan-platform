<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token gate for internal-only endpoints (e.g. the LiveKit VPS
 * cleanup cron). Rejects with 401 if the token is missing or wrong.
 *
 * The token is read from config('livekit.internal_token'). If the config
 * value is empty, every request is rejected — fail-closed.
 */
class InternalApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('livekit.internal_token', '');

        if ($expected === '') {
            return response()->json(['error' => 'internal endpoint not configured'], 503);
        }

        $header = $request->header('Authorization', '');
        $presented = str_starts_with($header, 'Bearer ') ? substr($header, 7) : '';

        if (! hash_equals($expected, $presented)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        return $next($request);
    }
}
