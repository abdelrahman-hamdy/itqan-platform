<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Cache Headers Middleware
 *
 * Adds appropriate cache headers to API responses for mobile app optimization.
 * Supports ETag generation, Cache-Control, and mobile-specific cache hints.
 *
 * Usage:
 *   Route::middleware('api.cache:300')->get('/static-data', ...)
 *   Route::middleware('api.cache:0')->get('/real-time', ...)  // no-cache
 *
 * @see RFC 7234 HTTP Caching
 */
class CacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param int $maxAge Cache max-age in seconds (0 for no-cache)
     * @param bool $private Whether cache is private (user-specific)
     * @return Response
     */
    public function handle(Request $request, Closure $next, int $maxAge = 0, bool $private = true): Response
    {
        $response = $next($request);

        // Only apply to successful JSON responses
        if (!$response instanceof JsonResponse || $response->getStatusCode() >= 400) {
            return $response;
        }

        // Generate ETag from response content
        $content = $response->getContent();
        $etag = '"' . md5($content) . '"';

        // Check If-None-Match header for 304 response
        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch && $ifNoneMatch === $etag) {
            return response()->json(null, 304);
        }

        // Set ETag header
        $response->headers->set('ETag', $etag);

        // Set Cache-Control based on max-age
        if ($maxAge > 0) {
            $cacheControl = $private ? 'private' : 'public';
            $cacheControl .= ", max-age={$maxAge}";

            // Add stale-while-revalidate for better mobile experience
            $staleWhileRevalidate = min($maxAge * 2, 86400); // Max 24 hours
            $cacheControl .= ", stale-while-revalidate={$staleWhileRevalidate}";

            $response->headers->set('Cache-Control', $cacheControl);
        } else {
            // No caching for real-time data
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
        }

        // Add cache metadata to JSON response for mobile apps
        $this->addCacheMetadata($response, $maxAge, $etag);

        return $response;
    }

    /**
     * Add cache metadata to JSON response body
     *
     * This helps mobile apps understand caching behavior
     * without needing to parse HTTP headers.
     */
    private function addCacheMetadata(JsonResponse $response, int $maxAge, string $etag): void
    {
        $data = json_decode($response->getContent(), true);

        if (!is_array($data)) {
            return;
        }

        // Add cache info to meta if it exists
        if (isset($data['meta'])) {
            $data['meta']['cache'] = [
                'cacheable' => $maxAge > 0,
                'max_age' => $maxAge,
                'etag' => $etag,
                'expires_at' => $maxAge > 0 ? now()->addSeconds($maxAge)->toISOString() : null,
            ];

            $response->setData($data);
        }
    }
}
