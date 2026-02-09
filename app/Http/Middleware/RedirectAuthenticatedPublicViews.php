<?php

namespace App\Http\Middleware;

use App\Constants\DefaultAcademy;
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
    public function handle(Request $request, Closure $next, ?string $type = null): Response
    {
        // If user is not authenticated, allow access to public view
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $subdomain = $request->route('subdomain') ?? $user->academy->subdomain ?? DefaultAcademy::subdomain();

        // Redirect based on resource type and user role
        if ($type === 'interactive-course') {
            $courseId = $request->route('course');

            if ($user->isStudent()) {
                // If viewing a specific course, always redirect to authenticated student view
                if ($courseId) {
                    return redirect()->route('my.interactive-course.show', ['subdomain' => $subdomain, 'course' => $courseId]);
                }

                // If viewing course listing, redirect to student courses
                return redirect()->route('student.interactive-courses', ['subdomain' => $subdomain]);
            }

            if ($user->isAcademicTeacher()) {
                // Check if teacher is assigned to this course
                if ($courseId) {
                    $course = \App\Models\InteractiveCourse::find($courseId);
                    if ($course) {
                        $teacherProfile = $user->academicTeacherProfile;
                        $isAssignedTeacher = $teacherProfile && $course->assigned_teacher_id === $teacherProfile->id;
                        $isCreatedByCourse = $course->created_by === $user->id;

                        if ($isAssignedTeacher || $isCreatedByCourse) {
                            // Redirect teacher to their course management view
                            return redirect()->route('my.interactive-course.show', ['subdomain' => $subdomain, 'course' => $courseId])
                                ->with('info', 'عرض الكورس من لوحة التحكم');
                        }
                    }

                    // Not assigned to this course, allow access to public view
                    return $next($request);
                }

                // For listing, redirect to student profile
                return redirect()->route('student.profile', ['subdomain' => $subdomain]);
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
                return redirect()->route('student.profile', ['subdomain' => $subdomain])
                    ->with('info', 'يمكنك الوصول إلى جلساتك من الملف الشخصي');
            }
        }

        // Default: redirect authenticated users to their profile
        return redirect()->route('student.profile', ['subdomain' => $subdomain]);
    }
}
