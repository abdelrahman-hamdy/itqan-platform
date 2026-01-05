<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogAllRequests
{
    public function handle(Request $request, Closure $next)
    {
        // Only log in local/testing environments - NEVER in production
        if (! app()->environment('local', 'testing')) {
            return $next($request);
        }

        // Log POST requests and trial-related requests for debugging
        $shouldLog = $request->isMethod('POST') || str_contains($request->path(), 'trial') || str_contains($request->path(), 'test-post');

        if ($shouldLog) {
            Log::debug('Request received', [
                'method' => $request->method(),
                'path' => $request->path(),
                'has_csrf' => $request->hasHeader('X-CSRF-TOKEN') || $request->has('_token'),
                'is_ajax' => $request->ajax(),
            ]);
        }

        $response = $next($request);

        if ($shouldLog) {
            Log::debug('Response sent', [
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
