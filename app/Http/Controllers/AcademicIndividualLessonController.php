<?php

namespace App\Http\Controllers;

use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademicIndividualLessonController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display individual lessons for the academic teacher
     */
    public function index(Request $request, $subdomain = null)
    {
        $user = Auth::user();

        if (! $user->isAcademicTeacher()) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        // Get teacher profile
        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile) {
            abort(404, 'ملف المعلم غير موجود');
        }

        $lessons = AcademicIndividualLesson::where('academic_teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->with(['student', 'academicSubject', 'academicGradeLevel', 'academicSubscription'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(15);

        return view('teacher.academic-lessons.index', compact('lessons'));
    }

    /**
     * Show individual lesson details
     */
    public function show($subdomain, $lesson)
    {
        $user = Auth::user();

        // Resolve academy from subdomain
        $tenantAcademy = \App\Models\Academy::where('subdomain', $subdomain)->first();
        if (! $tenantAcademy) {
            abort(404);
        }

        // Fetch lesson and validate tenant academy
        $lessonModel = AcademicIndividualLesson::findOrFail($lesson);

        if ((int) $lessonModel->academy_id !== (int) $tenantAcademy->id) {
            abort(404);
        }

        // Determine user role and permissions
        $userRole = 'guest';
        $isTeacher = false;
        $isStudent = false;

        if ($user->user_type === 'academic_teacher' && (int) $lessonModel->academic_teacher_id === (int) $user->academicTeacherProfile?->id) {
            $userRole = 'teacher';
            $isTeacher = true;
        } elseif ($user->user_type === 'student' && (int) $lessonModel->student_id === (int) $user->id) {
            $userRole = 'student';
            $isStudent = true;
        } else {
            abort(403, 'غير مسموح لك بالوصول لهذا الدرس');
        }

        $lessonModel->load([
            'student',
            'academicSubscription',
            'academicTeacher',
            'academicSubject',
            'academicGradeLevel',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at');
            },
        ]);

        $upcomingSessions = $lessonModel->sessions()
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->orderBy('scheduled_at')
            ->get();

        $completedSessions = $lessonModel->sessions()
            ->where('status', 'completed')
            ->orderBy('ended_at', 'desc')
            ->get();

        return view('teacher.academic-lessons.show', compact(
            'lessonModel',
            'userRole',
            'isTeacher',
            'isStudent',
            'upcomingSessions',
            'completedSessions'
        ));
    }

    /**
     * Show progress report for academic lesson
     */
    public function progressReport($subdomain, $lesson)
    {
        $user = Auth::user();
        $lessonModel = AcademicIndividualLesson::findOrFail($lesson);

        // Check permissions
        if ($user->isAcademicTeacher()) {
            $teacherProfile = $user->academicTeacherProfile;
            if (!$teacherProfile || (int) $lessonModel->academic_teacher_id !== (int) $teacherProfile->id) {
                abort(403, 'غير مسموح لك بالوصول لهذا التقرير');
            }
        } else {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        $lessonModel->load([
            'student',
            'academicSubject',
            'academicGradeLevel',
            'sessions' => function ($query) {
                $query->where('status', 'completed')->orderBy('ended_at', 'desc');
            }
        ]);

        // Calculate progress statistics
        $stats = [
            'total_sessions' => $lessonModel->sessions()->count(),
            'completed_sessions' => $lessonModel->sessions()->where('status', 'completed')->count(),
            'average_grade' => $lessonModel->sessions()->where('status', 'completed')->avg('session_grade'),
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
    public function updateSettings(Request $request, $subdomain, $lesson): JsonResponse
    {
        $user = Auth::user();
        $lessonModel = AcademicIndividualLesson::findOrFail($lesson);

        // Check permissions
        if (!$user->isAcademicTeacher()) {
            return response()->json(['error' => 'غير مسموح لك بالوصول'], 403);
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || (int) $lessonModel->academic_teacher_id !== (int) $teacherProfile->id) {
            return response()->json(['error' => 'غير مسموح لك بتعديل هذا الدرس'], 403);
        }

        $validated = $request->validate([
            'default_duration_minutes' => 'nullable|integer|min:30|max:180',
            'preferred_times' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
            'teacher_notes' => 'nullable|string|max:1000',
        ]);

        $lessonModel->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث إعدادات الدرس بنجاح',
            'lesson' => $lessonModel->fresh()
        ]);
    }
}