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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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

        // Check if supervisor is active
        // Note: is_active column may not exist on supervisor_profiles table in current schema
        // The check uses null coalescing to default to true if the column doesn't exist
        $supervisorProfile = $user->supervisorProfile;
        $isActive = $supervisorProfile?->is_active ?? true;
        if ($supervisorProfile && ! $isActive) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'حساب المشرف الخاص بك غير نشط. يرجى التواصل مع الإدارة.',
                ], 403);
            }

            abort(403, 'حساب المشرف الخاص بك غير نشط. يرجى التواصل مع الإدارة.');
        }

        return $next($request);
    }
}
