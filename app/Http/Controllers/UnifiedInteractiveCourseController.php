<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnifiedInteractiveCourseController extends Controller
{
    /**
     * Display a listing of interactive courses (Unified for both public and authenticated)
     */
    public function index(Request $request, $subdomain)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        $user = Auth::user();
        $isAuthenticated = (bool) $user;
        $studentId = null;

        // Get student ID if authenticated
        if ($isAuthenticated && $user->studentProfile) {
            $studentId = $user->studentProfile->id;
        }

        // Base query (applies to all users)
        $query = InteractiveCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->with(['assignedTeacher', 'subject', 'gradeLevel']);

        // For authenticated students, include enrollment data
        if ($isAuthenticated && $studentId) {
            $query->with(['enrollments' => function ($enrollmentQuery) use ($studentId) {
                $enrollmentQuery->where('student_id', $studentId);
            }]);

            // For authenticated students, show upcoming courses (enrollment deadline not passed)
            $query->where('enrollment_deadline', '>=', now()->toDateString());
        }

        // Apply filters (same for both)
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('description', 'LIKE', '%'.$request->search.'%');
            });
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', $request->grade_level_id);
        }

        // For authenticated students, sort by enrollment status
        if ($isAuthenticated && $studentId) {
            $allCourses = $query->get()->sortByDesc(function ($course) {
                return $course->enrollments->isNotEmpty() ? 1 : 0;
            })->values();

            // Manual pagination
            $perPage = 12;
            $currentPage = $request->get('page', 1);
            $offset = ($currentPage - 1) * $perPage;

            $courses = new \Illuminate\Pagination\LengthAwarePaginator(
                $allCourses->slice($offset, $perPage),
                $allCourses->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            // For guests, simple pagination
            $courses = $query->paginate(12);
        }

        // Student-specific data
        $enrolledCoursesCount = 0;
        if ($isAuthenticated && $studentId) {
            $enrolledCoursesCount = InteractiveCourse::where('academy_id', $academy->id)
                ->whereHas('enrollments', function ($query) use ($studentId) {
                    $query->where('student_id', $studentId)
                          ->whereIn('enrollment_status', ['enrolled', 'completed']);
                })
                ->count();
        }

        // Get filter options
        $subjects = AcademicSubject::where('academy_id', $academy->id)
            ->whereHas('interactiveCourses', function ($query) {
                $query->where('is_published', true);
            })
            ->orderBy('name')
            ->get();

        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)
            ->whereHas('interactiveCourses', function ($query) {
                $query->where('is_published', true);
            })
            ->orderBy('name')
            ->get();

        return view('student.interactive-courses', compact(
            'academy',
            'courses',
            'enrolledCoursesCount',
            'subjects',
            'gradeLevels',
            'isAuthenticated'
        ));
    }

    /**
     * Display the specified interactive course (Unified for both public and authenticated)
     */
    public function show(Request $request, $subdomain, $courseId)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        $user = Auth::user();
        $isAuthenticated = (bool) $user;
        $studentId = null;

        // Determine user type
        $isTeacher = $isAuthenticated && $user->user_type === 'academic_teacher';
        $isStudent = $isAuthenticated && $user->user_type === 'student';

        // Get student ID if authenticated as student
        if ($isStudent && $user->studentProfile) {
            $studentId = $user->studentProfile->id;
        }

        // Find the course
        $course = InteractiveCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->with(['academy', 'assignedTeacher.user', 'subject', 'gradeLevel', 'sessions', 'enrollments.student.user'])
            ->firstOrFail();

        // For teachers: Check if they have access to this course
        if ($isTeacher) {
            $teacherProfile = $user->academicTeacherProfile;
            $isAssignedTeacher = false;
            $isCreatedByCourse = $course->created_by === $user->id;

            // Check if teacher is assigned to course
            if ($teacherProfile) {
                $isAssignedTeacher = $course->assigned_teacher_id === $teacherProfile->id;
            }

            if (!$isAssignedTeacher && !$isCreatedByCourse) {
                abort(403, 'Access denied - not assigned to or creator of this course');
            }
        }

        // Check enrollment status for authenticated students
        $enrollment = null;
        $isEnrolled = false;

        if ($isStudent && $studentId) {
            $enrollment = InteractiveCourseEnrollment::where('course_id', $course->id)
                ->where('student_id', $studentId)
                ->first();

            $isEnrolled = (bool) $enrollment;
        }

        // Calculate enrollment statistics
        $totalEnrolled = InteractiveCourseEnrollment::where('course_id', $course->id)
            ->whereIn('enrollment_status', ['enrolled', 'completed'])
            ->count();

        $enrollmentStats = [
            'total_enrolled' => $totalEnrolled,
            'max_students' => $course->max_students ?? 50,
            'available_spots' => max(0, ($course->max_students ?? 50) - $totalEnrolled),
            'enrollment_deadline' => $course->enrollment_deadline ? \Carbon\Carbon::parse($course->enrollment_deadline) : null,
        ];

        // For teachers: Get additional teacher data
        $teacherData = [];
        if ($isTeacher) {
            $teacherData = [
                'total_students' => $course->enrollments->count(),
                'total_sessions' => $course->sessions->count(),
                'completed_sessions' => $course->sessions->where('status', 'completed')->count(),
                'upcoming_sessions' => $course->sessions->where('scheduled_at', '>', now())->count(),
            ];
        }

        // Separate sessions into upcoming and past
        $now = now();
        $upcomingSessions = $course->sessions
            ->filter(function ($session) use ($now) {
                $scheduledDateTime = $session->scheduled_at;
                return $scheduledDateTime && ($scheduledDateTime->gte($now) || $session->status === 'in-progress');
            })
            ->values();

        $pastSessions = $course->sessions
            ->filter(function ($session) use ($now) {
                $scheduledDateTime = $session->scheduled_at;
                return $scheduledDateTime && $scheduledDateTime->lt($now) && $session->status !== 'in-progress';
            })
            ->sortByDesc(function ($session) {
                return $session->scheduled_at ? $session->scheduled_at->timestamp : 0;
            })
            ->values();

        // Choose view based on user type
        $viewName = $isTeacher ? 'teacher.interactive-course-detail' : 'student.interactive-course-detail';

        return view($viewName, compact(
            'academy',
            'course',
            'enrollment',
            'isEnrolled',
            'isAuthenticated',
            'enrollmentStats',
            'teacherData',
            'isTeacher',
            'isStudent',
            'upcomingSessions',
            'pastSessions'
        ));
    }

    /**
     * Enroll student in a course
     */
    public function enroll(Request $request, $subdomain, $courseId)
    {
        // Must be authenticated
        if (! Auth::check()) {
            return redirect()->route('login', [
                'subdomain' => $subdomain,
                'redirect' => route('interactive-courses.show', ['subdomain' => $subdomain, 'courseId' => $courseId])
            ])->with('message', 'يجب تسجيل الدخول أولاً للتسجيل في الكورس');
        }

        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();
        $user = Auth::user();

        // Ensure user has a student profile
        if (!$user->studentProfile) {
            return redirect()->back()
                ->with('error', 'يجب إكمال الملف الشخصي للطالب أولاً قبل التسجيل في الكورس');
        }

        $studentId = $user->studentProfile->id;

        // Find the course
        $course = InteractiveCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->with(['academy', 'assignedTeacher.user'])
            ->firstOrFail();

        // Check if enrollment is open
        if (! $course->isEnrollmentOpen()) {
            return redirect()->route('interactive-courses.show', ['subdomain' => $subdomain, 'courseId' => $courseId])
                ->with('error', 'عذراً، التسجيل في هذا الكورس مغلق حالياً');
        }

        // Check if already enrolled
        $existingEnrollment = InteractiveCourseEnrollment::where('course_id', $course->id)
            ->where('student_id', $studentId)
            ->first();

        if ($existingEnrollment) {
            return redirect()->route('interactive-courses.show', ['subdomain' => $subdomain, 'courseId' => $courseId])
                ->with('info', 'أنت مسجل بالفعل في هذا الكورس');
        }

        // Calculate total possible attendance (sessions from enrollment date forward)
        // If enrolling late, only count remaining sessions
        $enrollmentDate = now();
        $totalPossibleSessions = $course->sessions()
            ->where(function($query) use ($enrollmentDate) {
                // Count sessions that haven't ended yet (scheduled or in-progress)
                $query->where('scheduled_at', '>=', $enrollmentDate)
                      ->orWhere('status', 'scheduled')
                      ->orWhere('status', 'live');
            })
            ->count();

        // If no specific sessions match, count all course sessions as fallback
        if ($totalPossibleSessions === 0) {
            $totalPossibleSessions = $course->sessions()->count();
        }

        // Create enrollment directly (bypassing payment for now)
        $enrollment = InteractiveCourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $studentId,
            'academy_id' => $academy->id,
            'enrollment_status' => 'enrolled', // Direct enrollment, bypassing payment
            'enrollment_date' => $enrollmentDate,
            'payment_status' => 'paid', // Mark as paid (bypassing payment for now)
            'payment_amount' => $course->student_price ?? 0,
            'discount_applied' => $course->student_price ?? 0, // Full discount for now
            'total_possible_attendance' => $totalPossibleSessions, // Snapshot sessions at enrollment time
        ]);

        // Redirect to course page with success message
        return redirect()->route('interactive-courses.show', ['subdomain' => $subdomain, 'courseId' => $courseId])
            ->with('success', 'تم تسجيلك بنجاح في الكورس! يمكنك الآن متابعة الجلسات والمحتوى التعليمي.');
    }
}
