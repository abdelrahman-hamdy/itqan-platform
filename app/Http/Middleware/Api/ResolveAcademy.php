<?php

namespace App\Http\Middleware\Api;

use App\Constants\DefaultAcademy;
use App\Models\Academy;
use App\Services\AcademyContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAcademy
{
    /**
     * Handle an incoming request.
     *
     * Resolves the academy from the request using the following priority:
     * 1. X-Academy-Subdomain header
     * 2. academy query parameter
     * 3. subdomain route parameter
     * 4. Default to configured default academy subdomain
     */
    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = $this->resolveSubdomain($request);

        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            return response()->json([
                'success' => false,
                'message' => __('Academy not found'),
                'error_code' => 'ACADEMY_NOT_FOUND',
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v1',
                    // Do not reflect the raw subdomain value back in the response (XSS prevention)
                ],
            ], 404);
        }

        // Store academy in request attributes for easy access in controllers
        $request->attributes->set('academy', $academy);

        // Also merge into request for form requests and validation
        $request->merge(['academy' => $academy]);

        // Set API context using the service (replaces direct container binding)
        AcademyContextService::setApiContext($academy);

        $response = $next($request);

        // PERF-005: Clear static API context after request to prevent leaking between requests
        // Critical for long-running processes (Octane, queue workers)
        AcademyContextService::clearApiContext();

        return $response;
    }

    /**
     * Resolve subdomain from request
     */
    protected function resolveSubdomain(Request $request): string
    {
        // Priority 1: Header
        if ($header = $request->header('X-Academy-Subdomain')) {
            return $this->sanitizeSubdomain($header);
        }

        // Priority 2: Query parameter
        if ($query = $request->query('academy')) {
            return $this->sanitizeSubdomain($query);
        }

        // Priority 3: Route parameter
        if ($route = $request->route('subdomain')) {
            return $this->sanitizeSubdomain($route);
        }

        // Priority 4: Default
        return config('multitenancy.default_tenant', DefaultAcademy::subdomain());
    }

    /**
     * Sanitize subdomain to prevent injection
     */
    protected function sanitizeSubdomain(string $subdomain): string
    {
        // Only allow alphanumeric characters and hyphens
        return preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($subdomain)));
    }
}
