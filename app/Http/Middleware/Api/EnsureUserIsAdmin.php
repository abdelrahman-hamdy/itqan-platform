<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * Allows: Admin and SuperAdmin
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin account required.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        return $next($request);
    }
}
