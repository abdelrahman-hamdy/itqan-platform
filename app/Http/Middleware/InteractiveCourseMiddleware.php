<?php

namespace App\Http\Middleware;

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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            $subdomain = $request->route('subdomain') ?? 'itqan-academy';

            return redirect()->route('login', ['subdomain' => $subdomain]);
        }

        $user = Auth::user();

        // Check if user is active
        if (! $user->isActive()) {
            Auth::logout();
            $subdomain = $request->route('subdomain') ?? 'itqan-academy';

            return redirect()->route('login', ['subdomain' => $subdomain])
                ->withErrors(['email' => 'حسابك غير نشط. يرجى التواصل مع الإدارة']);
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
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }
    }
}
