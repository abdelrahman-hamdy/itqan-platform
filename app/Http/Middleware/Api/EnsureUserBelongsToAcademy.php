<?php

namespace App\Http\Middleware\Api;

use Log;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToAcademy
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user belongs to the resolved academy.
     * This prevents cross-tenant data access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('Authentication required'),
                'error_code' => 'UNAUTHENTICATED',
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v1',
                ],
            ], 401);
        }

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

        // Super admins can access any academy (they have null academy_id)
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Verify user belongs to the academy
        if ($user->academy_id !== $academy->id) {
            // Log detailed info server-side for debugging (not exposed to client)
            Log::warning('API: Academy mismatch detected', [
                'user_id' => $user->id,
                'user_academy_id' => $user->academy_id,
                'requested_academy_id' => $academy->id,
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Access denied'),
                'error_code' => 'FORBIDDEN',
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v1',
                ],
            ], 403);
        }

        // Verify user is active
        if (! $user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => __('Your account is inactive. Please contact support.'),
                'error_code' => 'ACCOUNT_INACTIVE',
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v1',
                ],
            ], 403);
        }

        return $next($request);
    }
}
