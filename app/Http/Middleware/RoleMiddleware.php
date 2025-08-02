<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user is active
        if (!$user->isActive()) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'حسابك غير نشط. يرجى التواصل مع الإدارة']);
        }

        // Check role permissions
        $hasRole = match($role) {
            'super_admin' => $user->isSuperAdmin(),
            'academy_admin' => $user->isAcademyAdmin(),
            'admin' => $user->isAdmin(),
            'teacher' => $user->isTeacher(),
            'quran_teacher' => $user->isQuranTeacher(),
            'academic_teacher' => $user->isAcademicTeacher(),
            'supervisor' => $user->isSupervisor(),
            'student' => $user->isStudent(),
            'parent' => $user->isParent(),
            'staff' => $user->isStaff(),
            'end_user' => $user->isEndUser(),
            default => false,
        };

        if (!$hasRole) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        return $next($request);
    }
}
