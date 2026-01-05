<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogAllRequests
{
    public function handle(Request $request, Closure $next)
    {
        // Log ALL POST requests and trial-related requests
        $shouldLog = $request->isMethod('POST') || str_contains($request->path(), 'trial') || str_contains($request->path(), 'test-post');

        if ($shouldLog) {
            Log::info('DEBUG: Request received', [
                'method' => $request->method(),
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'has_csrf' => $request->hasHeader('X-CSRF-TOKEN') || $request->has('_token'),
                'has_session' => $request->hasSession(),
                'content_type' => $request->header('Content-Type'),
                'is_ajax' => $request->ajax(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 50),
            ]);

            if ($request->isMethod('POST')) {
                Log::info('DEBUG: POST data', [
                    'path' => $request->path(),
                    'fields' => array_keys($request->except(['_token', 'password'])),
                ]);
            }
        }

        $response = $next($request);

        if ($shouldLog) {
            Log::info('DEBUG: Response sent', [
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'is_redirect' => $response->isRedirect(),
                'redirect_to' => $response->isRedirect() ? $response->headers->get('Location') : null,
            ]);
        }

        return $response;
    }
}
