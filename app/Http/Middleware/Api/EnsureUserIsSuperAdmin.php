<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * Allows: SuperAdmin only
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. SuperAdmin account required.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        return $next($request);
    }
}
