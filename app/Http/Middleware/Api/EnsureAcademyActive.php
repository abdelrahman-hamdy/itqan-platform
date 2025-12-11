<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAcademyActive
{
    /**
     * Handle an incoming request.
     *
     * Ensures the resolved academy is active and not in maintenance mode.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $academy = $request->attributes->get('academy') ?? app('current_academy');

        if (!$academy) {
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

        // Check if academy is active
        if (!$academy->is_active) {
            return response()->json([
                'success' => false,
                'message' => __('This academy is currently inactive'),
                'error_code' => 'ACADEMY_INACTIVE',
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v1',
                    'academy_subdomain' => $academy->subdomain,
                ],
            ], 403);
        }

        // Check if academy is in maintenance mode
        if ($academy->maintenance_mode) {
            return response()->json([
                'success' => false,
                'message' => __('This academy is currently under maintenance. Please try again later.'),
                'error_code' => 'ACADEMY_MAINTENANCE',
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v1',
                    'academy_subdomain' => $academy->subdomain,
                    'academy_name' => $academy->name,
                ],
            ], 503);
        }

        return $next($request);
    }
}
