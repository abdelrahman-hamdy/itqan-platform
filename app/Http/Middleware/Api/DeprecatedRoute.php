<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deprecated Route Middleware
 *
 * Adds deprecation headers to API responses for legacy routes.
 * This informs API consumers that these routes will be removed in a future version
 * and they should migrate to the v1 API endpoints.
 *
 * Headers added:
 * - Deprecation: RFC 8594 deprecation header
 * - Sunset: When the deprecated API will be removed (optional)
 * - Link: URL to migration documentation or new endpoint
 *
 * Usage:
 * Route::middleware('api.deprecated')->group(function () {
 *     // Legacy routes
 * });
 *
 * Route::middleware('api.deprecated:2025-06-01,/api/v1/sessions/{id}/status')->get(...)
 *
 * @see https://datatracker.ietf.org/doc/html/rfc8594
 */
class DeprecatedRoute
{
    /**
     * Default sunset date (6 months from now if not specified)
     */
    protected const DEFAULT_SUNSET_MONTHS = 6;

    /**
     * Documentation URL for migration guide
     */
    protected const MIGRATION_DOCS_URL = 'https://docs.itqan-academy.com/api/migration';

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string|null  $sunsetDate  When the API will be removed (ISO 8601 date)
     * @param  string|null  $alternativeUrl  URL or path to the new endpoint
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $sunsetDate = null, ?string $alternativeUrl = null): Response
    {
        $response = $next($request);

        // Add Deprecation header (RFC 8594)
        $response->headers->set('Deprecation', 'true');

        // Add Sunset header with removal date
        $sunset = $sunsetDate
            ? $this->parseSunsetDate($sunsetDate)
            : now()->addMonths(self::DEFAULT_SUNSET_MONTHS);
        $response->headers->set('Sunset', $sunset->toRfc7231String());

        // Add Link header with migration docs and/or alternative endpoint
        $links = $this->buildLinkHeader($request, $alternativeUrl);
        if (!empty($links)) {
            $response->headers->set('Link', implode(', ', $links));
        }

        // Add warning header for additional visibility
        $warningMessage = sprintf(
            'This API endpoint is deprecated and will be removed after %s. Please migrate to the v1 API.',
            $sunset->format('F j, Y')
        );
        $response->headers->set('Warning', '299 - "' . $warningMessage . '"');

        // For JSON responses, add deprecation notice to response body
        if ($this->isJsonResponse($response)) {
            $this->addDeprecationToJsonBody($response, $sunset, $alternativeUrl);
        }

        return $response;
    }

    /**
     * Parse sunset date from string
     */
    protected function parseSunsetDate(string $date): \Carbon\Carbon
    {
        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return now()->addMonths(self::DEFAULT_SUNSET_MONTHS);
        }
    }

    /**
     * Build Link header value
     */
    protected function buildLinkHeader(Request $request, ?string $alternativeUrl): array
    {
        $links = [];

        // Add migration documentation link
        $links[] = sprintf('<%s>; rel="deprecation"', self::MIGRATION_DOCS_URL);

        // Add alternative endpoint link if provided
        if ($alternativeUrl) {
            // If it's a relative path, make it absolute
            $fullUrl = str_starts_with($alternativeUrl, 'http')
                ? $alternativeUrl
                : $request->getSchemeAndHttpHost() . $alternativeUrl;

            $links[] = sprintf('<%s>; rel="successor-version"', $fullUrl);
        }

        return $links;
    }

    /**
     * Check if response is JSON
     */
    protected function isJsonResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'application/json');
    }

    /**
     * Add deprecation notice to JSON response body
     */
    protected function addDeprecationToJsonBody(Response $response, \Carbon\Carbon $sunset, ?string $alternativeUrl): void
    {
        $content = $response->getContent();
        $data = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            // Add deprecation meta
            $data['meta'] = array_merge($data['meta'] ?? [], [
                'deprecated' => true,
                'sunset' => $sunset->toISOString(),
                'migration_guide' => self::MIGRATION_DOCS_URL,
            ]);

            if ($alternativeUrl) {
                $data['meta']['alternative_endpoint'] = $alternativeUrl;
            }

            $response->setContent(json_encode($data));
        }
    }
}
