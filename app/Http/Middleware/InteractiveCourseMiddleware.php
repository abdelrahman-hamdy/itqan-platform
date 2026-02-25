<?php

namespace App\Http\Middleware;

use App\Constants\DefaultAcademy;
use App\Services\AcademyContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InteractiveCourseMiddleware
{
    /**
     * Handle an incoming request for interactive course access.
     * Routes users to appropriate controller method based on their role.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            $subdomain = $request->route('subdomain') ?? DefaultAcademy::subdomain();

            return redirect()->route('login', ['subdomain' => $subdomain]);
        }

        $user = Auth::user();

        // Check if user is active
        if (! $user->isActive()) {
            Auth::logout();
            $subdomain = $request->route('subdomain') ?? DefaultAcademy::subdomain();

            return redirect()->route('login', ['subdomain' => $subdomain])
                ->withErrors(['email' => __('auth.account_inactive')]);
        }

        // Determine the current academy from context
        $currentAcademyId = AcademyContextService::getApiContextAcademyId()
            ?? app('current_academy')?->id;

        // Bypass for admin/supervisor roles: allow access to any course in the current academy
        if ($user->isAdmin() || $user->isSupervisor() || $user->isSuperAdmin()) {
            return $next($request);
        }

        // Cross-tenant check: verify the requested course belongs to the current academy
        // This prevents a student or teacher from academy A from accessing academy B's courses
        $courseId = $request->route('course') ?? $request->route('id');
        if ($courseId && $currentAcademyId) {
            $course = \App\Models\InteractiveCourse::find($courseId);
            if ($course && $course->academy_id !== $currentAcademyId) {
                abort(403, __('auth.unauthorized_access'));
            }
        }

        // Check user role and route accordingly
        if ($user->isStudent()) {
            // Student access - use existing student logic
            return $next($request);
        } elseif ($user->isAcademicTeacher()) {
            // Teacher access - modify request to use teacher view
            $request->attributes->set('use_teacher_view', true);

            return $next($request);
        } else {
            abort(403, __('auth.unauthorized_access'));
        }
    }
}
