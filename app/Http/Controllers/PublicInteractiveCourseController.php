<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicInteractiveCourseController extends Controller
{
    /**
     * Display a listing of interactive courses for an academy
     */
    public function index(Request $request, $subdomain)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Get published interactive courses for this academy
        $courses = InteractiveCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->with(['academy'])
            ->paginate(12);

        return view('public.interactive-courses.index', compact('academy', 'courses'));
    }

    /**
     * Display the specified interactive course (PUBLIC VIEW ONLY)
     * Authenticated users will be redirected by middleware to appropriate views
     */
    public function show(Request $request, $subdomain, $courseId)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Find the course by ID within the academy
        $course = InteractiveCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->with(['academy', 'assignedTeacher.user', 'subject', 'gradeLevel'])
            ->first();

        if (! $course) {
            abort(404, 'Course not found');
        }

        // This view is for PUBLIC (unauthenticated) users only
        // Middleware handles authenticated users
        return view('public.interactive-courses.show', compact('academy', 'course'));
    }

    /**
     * Show enrollment form for a course
     */
    public function enroll(Request $request, $subdomain, $courseId)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Check if user is authenticated
        if (! Auth::check()) {
            return redirect()->route('login', ['academy' => $subdomain])
                ->with('message', 'يجب تسجيل الدخول أولاً للتسجيل في الكورس');
        }

        // Find the course
        $course = InteractiveCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->with(['academy', 'assignedTeacher.user'])
            ->first();

        if (! $course) {
            abort(404, 'Course not found');
        }

        // Check if enrollment is open
        if (! $course->isEnrollmentOpen()) {
            return redirect()->route('interactive-courses.show', [
                'subdomain' => $subdomain,
                'course' => $courseId,
            ])->with('error', 'عذراً، التسجيل في هذا الكورس مغلق حالياً');
        }

        // Check if already enrolled
        $existingEnrollment = InteractiveCourseEnrollment::where('course_id', $course->id)
            ->where('student_id', Auth::id())
            ->first();

        if ($existingEnrollment) {
            return redirect()->route('interactive-courses.show', [
                'subdomain' => $subdomain,
                'course' => $courseId,
            ])->with('info', 'أنت مسجل بالفعل في هذا الكورس');
        }

        return view('public.interactive-courses.enroll', compact('academy', 'course'));
    }

    /**
     * Store enrollment
     */
    public function storeEnrollment(Request $request, $subdomain, $courseId)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Check if user is authenticated
        if (! Auth::check()) {
            return redirect()->route('login', ['academy' => $subdomain]);
        }

        // Validate
        $validated = $request->validate([
            'goals' => 'nullable|string|max:1000',
            'terms' => 'required|accepted',
        ]);

        // Find the course
        $course = InteractiveCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->first();

        if (! $course) {
            abort(404, 'Course not found');
        }

        // Check if enrollment is open
        if (! $course->isEnrollmentOpen()) {
            return back()->with('error', 'عذراً، التسجيل في هذا الكورس مغلق حالياً');
        }

        // Check if already enrolled
        $existingEnrollment = InteractiveCourseEnrollment::where('course_id', $course->id)
            ->where('student_id', Auth::id())
            ->first();

        if ($existingEnrollment) {
            return redirect()->route('interactive-courses.show', [
                'subdomain' => $subdomain,
                'course' => $courseId,
            ])->with('info', 'أنت مسجل بالفعل في هذا الكورس');
        }

        // Create enrollment (pending payment)
        $enrollment = InteractiveCourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => Auth::id(),
            'academy_id' => $academy->id,
            'enrollment_status' => 'pending', // Will be 'enrolled' after payment
            'enrolled_at' => now(),
            'notes' => $validated['goals'] ?? null,
        ]);

        // TODO: Redirect to payment gateway when implemented
        // For now, just show success message

        return redirect()->route('student.dashboard', ['subdomain' => $subdomain])
            ->with('success', 'تم تسجيل طلبك بنجاح! سيتم التواصل معك قريباً لإتمام عملية الدفع.');
    }
}
