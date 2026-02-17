<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify that the authenticated user has supervisor role.
 * Used to protect the Supervisor Filament panel.
 */
class VerifySupervisorRole
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Check if user is authenticated
        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
            }

            return redirect()->route('login');
        }

        // SuperAdmin can always access supervisor panel
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has supervisor role
        if (! $user->hasRole(UserType::SUPERVISOR->value)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'غير مصرح لك بالوصول. يجب أن تكون مشرفاً للوصول إلى هذه اللوحة.',
                ], 403);
            }

            abort(403, 'غير مصرح لك بالوصول. يجب أن تكون مشرفاً للوصول إلى هذه اللوحة.');
        }

        return $next($request);
    }
}
