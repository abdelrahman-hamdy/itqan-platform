<?php

namespace App\Http\Middleware\Api;

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
        $academy = $request->attributes->get('academy') ?? app('current_academy');

        if (!$user) {
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

        // Verify user belongs to the academy
        if ($user->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => __('You do not have access to this academy'),
                'error_code' => 'ACADEMY_MISMATCH',
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v1',
                    'user_academy_id' => $user->academy_id,
                    'requested_academy_id' => $academy->id,
                ],
            ], 403);
        }

        // Verify user is active
        if (!$user->isActive()) {
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
