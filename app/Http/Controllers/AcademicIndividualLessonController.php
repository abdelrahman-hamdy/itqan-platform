<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ApiResponses;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Enums\SessionStatus;
use App\Http\Requests\UpdateLessonSettingsRequest;

class AcademicIndividualLessonController extends Controller
{
    use ApiResponses;
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display individual lessons (subscriptions) for the academic teacher
     */
    public function index(Request $request, $subdomain = null): View
    {
        $this->authorize('viewAny', \App\Models\AcademicIndividualLesson::class);

        $user = Auth::user();

        // Get teacher profile
        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile) {
            abort(404, 'ملف المعلم غير موجود');
        }

        // Use AcademicSubscription for consistency with show view and profile page
        $subscriptions = \App\Models\AcademicSubscription::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->with(['student', 'subject', 'gradeLevel'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(15);

        return view('teacher.academic-lessons.index', compact('subscriptions'));
    }

    /**
     * Show individual lesson details (using AcademicSubscription)
     */
    public function show($subdomain, $lesson): View
    {
        $user = Auth::user();

        // Resolve academy from subdomain
        $tenantAcademy = \App\Models\Academy::where('subdomain', $subdomain)->first();
        if (! $tenantAcademy) {
            abort(404);
        }

        // Fetch subscription (private lesson) and validate tenant academy
        $subscription = \App\Models\AcademicSubscription::findOrFail($lesson);

        if ((int) $subscription->academy_id !== (int) $tenantAcademy->id) {
            abort(404);
        }

        // Determine user role and permissions
        $userRole = 'guest';
        $isTeacher = false;
        $isStudent = false;

        // Authorization check using the newly created AcademicSubscription model instance
        // Note: Using AcademicSubscription instead of AcademicIndividualLesson
        $this->authorize('view', $subscription);

        if ($user->user_type === 'academic_teacher' && (int) $subscription->teacher_id === (int) $user->academicTeacherProfile?->id) {
            $userRole = 'teacher';
            $isTeacher = true;
        } elseif ($user->user_type === 'student' && (int) $subscription->student_id === (int) $user->id) {
            $userRole = 'student';
            $isStudent = true;
        }

        $subscription->load([
            'student',
            'teacher',
            'subject',
            'gradeLevel',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at');
            },
        ]);

        // Get sessions for this subscription (matching Quran circle pattern)
        $upcomingSessions = \App\Models\AcademicSession::where('academic_subscription_id', $subscription->id)
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value])
            ->orderBy('scheduled_at')
            ->with(['student', 'academicTeacher'])
            ->get();

        $pastSessions = \App\Models\AcademicSession::where('academic_subscription_id', $subscription->id)
            ->whereIn('status', [
                SessionStatus::COMPLETED->value,
                SessionStatus::ABSENT->value,
                SessionStatus::CANCELLED->value
            ])
            ->orderBy('scheduled_at', 'desc')
            ->with(['student', 'academicTeacher'])
            ->get();

        return view('teacher.academic-lessons.show', compact(
            'subscription',
            'userRole',
            'isTeacher',
            'isStudent',
            'upcomingSessions',
            'pastSessions'
        ));
    }

    /**
     * Show progress report for academic lesson
     */
    public function progressReport($subdomain, $lesson): View
    {
        $lessonModel = AcademicIndividualLesson::findOrFail($lesson);

        $this->authorize('viewReport', $lessonModel);

        $lessonModel->load([
            'student',
            'academicSubject',
            'academicGradeLevel',
            'sessions' => function ($query) {
                $query->where('status', SessionStatus::COMPLETED->value)->orderBy('ended_at', 'desc');
            },
        ]);

        // Calculate progress statistics
        $stats = [
            'total_sessions' => $lessonModel->sessions()->count(),
            'completed_sessions' => $lessonModel->sessions()->where('status', SessionStatus::COMPLETED->value)->count(),
            'average_grade' => $lessonModel->sessions()->where('status', SessionStatus::COMPLETED->value)->avg('session_grade'),
            'attendance_rate' => 0,
        ];

        if ($stats['total_sessions'] > 0) {
            $stats['attendance_rate'] = round(($stats['completed_sessions'] / $stats['total_sessions']) * 100, 2);
        }

        return view('teacher.academic-lessons.progress', compact('lessonModel', 'stats'));
    }

    /**
     * Update lesson settings
     */
    public function updateSettings(UpdateLessonSettingsRequest $request, $subdomain, $lesson): JsonResponse
    {
        $user = Auth::user();
        $lessonModel = AcademicIndividualLesson::findOrFail($lesson);

        // Check permissions
        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile || (int) $lessonModel->academic_teacher_id !== (int) $teacherProfile->id) {
            return $this->forbiddenResponse('غير مسموح لك بتعديل هذا الدرس');
        }

        $validated = $request->validated();

        $lessonModel->update($validated);

        return $this->customResponse([
            'success' => true,
            'message' => 'تم تحديث إعدادات الدرس بنجاح',
            'lesson' => $lessonModel->fresh(),
        ], true, 200);
    }

    /**
     * Display interactive courses assigned to the academic teacher
     */
    public function interactiveCoursesIndex(Request $request, $subdomain = null): View
    {
        $this->authorize('viewAny', \App\Models\InteractiveCourse::class);

        $user = Auth::user();

        // Get teacher profile
        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile) {
            abort(404, 'ملف المعلم غير موجود');
        }

        // Get courses assigned to this teacher
        $courses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->with(['enrollments', 'subject', 'gradeLevel'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(15);

        return view('teacher.interactive-courses.index', compact('courses'));
    }
}
