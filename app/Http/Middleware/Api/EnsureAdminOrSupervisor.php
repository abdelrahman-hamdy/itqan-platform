<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOrSupervisor
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user is an Admin or Supervisor.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Allow super_admin, admin, or supervisor
        if ($user && ($user->isSuperAdmin() || $user->isAdmin() || $user->isSupervisor())) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access denied. Admin or Supervisor account required.',
            'error_code' => 'FORBIDDEN',
        ], 403);
    }
}
