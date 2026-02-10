<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSupervisor
{
    /**
     * Handle an incoming request.
     *
     * Allows: Supervisor only
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isSupervisor()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Supervisor account required.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        return $next($request);
    }
}
