<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAcademyAllowsRegistration
{
    /**
     * Handle an incoming request.
     *
     * Ensures the resolved academy allows new registrations.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        if (! $academy) {
            return response()->json([
                'success' => false,
                'message' => __('Academy context not resolved'),
                'error_code' => 'ACADEMY_NOT_RESOLVED',
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v1',
                ],
            ], 500);
        }

        // Check if academy allows registration
        if (! $academy->allow_registration) {
            return response()->json([
                'success' => false,
                'message' => __('Registration is currently disabled for this academy'),
                'error_code' => 'REGISTRATION_DISABLED',
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v1',
                    'academy_subdomain' => $academy->subdomain,
                    'academy_name' => $academy->name,
                ],
            ], 403);
        }

        return $next($request);
    }
}
