<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanControlParticipants
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Enhanced debugging - log request details
        \Log::info('CanControlParticipants middleware - Request details', [
            'url' => $request->url(),
            'method' => $request->method(),
            'has_session' => $request->hasSession(),
            'session_id' => $request->session()->getId() ?? 'no-session',
            'auth_guard' => config('auth.defaults.guard'),
            'auth_check' => auth()->check(),
            'auth_guard_check' => auth('web')->check(),
            'headers' => $request->headers->all(),
        ]);

        // Check if user is authenticated
        if (! auth()->check()) {
            \Log::warning('CanControlParticipants - User not authenticated', [
                'session_exists' => $request->hasSession(),
                'session_data' => $request->hasSession() ? $request->session()->all() : [],
            ]);

            return response()->json(['error' => 'Authentication required'], 401);
        }

        // Check if user is a teacher (can control participants)
        $user = auth()->user();
        $allowedUserTypes = ['quran_teacher', 'academic_teacher', 'admin', 'super_admin'];

        // Log for debugging
        \Log::info('CanControlParticipants middleware - User authenticated', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_type' => $user->user_type,
            'user_name' => $user->name,
            'allowed_types' => $allowedUserTypes,
            'can_control' => in_array($user->user_type, $allowedUserTypes),
        ]);

        if (! in_array($user->user_type, $allowedUserTypes)) {
            \Log::warning('CanControlParticipants - User not authorized', [
                'user_id' => $user->id,
                'user_type' => $user->user_type,
                'required_types' => $allowedUserTypes,
            ]);

            return response()->json([
                'error' => 'Unauthorized. Teacher permissions required.',
                'user_type' => $user->user_type,
                'required_types' => $allowedUserTypes,
            ], 403);
        }

        \Log::info('CanControlParticipants - Authorization successful', [
            'user_id' => $user->id,
            'user_type' => $user->user_type,
        ]);

        return $next($request);
    }
}
