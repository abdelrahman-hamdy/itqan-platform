<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectAuthenticatedPublicViews
{
    /**
     * Handle an incoming request to public education views.
     * Redirect authenticated users to their appropriate role-based views.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = null): Response
    {
        // If user is not authenticated, allow access to public view
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $subdomain = $request->route('subdomain') ?? $user->academy->subdomain ?? 'itqan-academy';

        // Redirect based on resource type and user role
        if ($type === 'interactive-course') {
            $courseId = $request->route('course');

            if ($user->isStudent()) {
                // If viewing a specific course
                if ($courseId) {
                    // Check if student is enrolled
                    $enrollment = \App\Models\InteractiveCourseEnrollment::where('course_id', $courseId)
                        ->where('student_id', $user->student->id ?? $user->id)
                        ->where('status', 'active')
                        ->first();

                    if ($enrollment) {
                        // Redirect enrolled student to their course view
                        return redirect("/my-courses/interactive/{$courseId}");
                    }

                    // Not enrolled, redirect to student courses listing
                    return redirect()->route('student.interactive-courses', ['subdomain' => $subdomain])
                        ->with('info', 'يمكنك التسجيل في هذا الكورس من خلال صفحة الكورسات');
                }

                // If viewing course listing, redirect to student courses
                return redirect()->route('student.interactive-courses', ['subdomain' => $subdomain]);
            }

            if ($user->isAcademicTeacher()) {
                // Redirect teacher to their course management view
                if ($courseId) {
                    return redirect("/my-courses/interactive/{$courseId}")
                        ->with('info', 'عرض الكورس من لوحة التحكم');
                }

                // For listing, redirect to teacher dashboard
                return redirect()->route('student.dashboard', ['subdomain' => $subdomain]);
            }
        }

        if ($type === 'quran-circle') {
            $circleId = $request->route('circle');

            if ($user->isStudent()) {
                // Check if student is subscribed
                $subscription = \App\Models\QuranSubscription::where('circle_id', $circleId)
                    ->where('student_id', $user->student->id ?? $user->id)
                    ->where('status', 'active')
                    ->first();

                if ($subscription) {
                    // Redirect subscribed student to their circle view
                    return redirect()->route('student.quran-circles.show', ['subdomain' => $subdomain, 'circle' => $circleId]);
                }

                // Not subscribed, redirect to student circles listing
                return redirect()->route('student.quran-circles', ['subdomain' => $subdomain])
                    ->with('info', 'يمكنك التسجيل في هذه الحلقة من خلال صفحة حلقات القرآن');
            }

            if ($user->isQuranTeacher()) {
                // Redirect teacher to their circle management view
                return redirect()->route('teacher.group-circles.show', ['subdomain' => $subdomain, 'circle' => $circleId]);
            }
        }

        if ($type === 'academic-session') {
            // Similar logic for academic sessions
            if ($user->isStudent() || $user->isAcademicTeacher()) {
                return redirect()->route('student.dashboard', ['subdomain' => $subdomain])
                    ->with('info', 'يمكنك الوصول إلى جلساتك من لوحة التحكم');
            }
        }

        // Default: redirect authenticated users to their dashboard
        return redirect()->route('student.dashboard', ['subdomain' => $subdomain]);
    }
}
