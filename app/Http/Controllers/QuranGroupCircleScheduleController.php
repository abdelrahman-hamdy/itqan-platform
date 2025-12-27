<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\QuranCircle;
use App\Models\QuranCircleSchedule;
use App\Models\User;
use App\Services\QuranSessionSchedulingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuranGroupCircleScheduleController extends Controller
{
    private QuranSessionSchedulingService $schedulingService;

    public function __construct(QuranSessionSchedulingService $schedulingService)
    {
        $this->schedulingService = $schedulingService;
        $this->middleware('auth');
    }

    /**
     * Display teacher's group circles and their schedules
     */
    public function index($subdomain, Request $request)
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher()) {
            abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
        }

        $circles = QuranCircle::where('quran_teacher_id', $user->id)
            ->with(['schedule', 'academy'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(15);

        return view('teacher.group-circles.index', compact('circles'));
    }

    /**
     * Show form to create/edit schedule for a group circle
     */
    public function create($subdomain, $circle)
    {
        $user = Auth::user();

        // Find the circle
        $circle = QuranCircle::findOrFail($circle);

        // Check ownership
        if (! $user->quranTeacherProfile || $circle->quran_teacher_id !== $user->id) {
            abort(403, 'غير مسموح لك بإدارة هذه الحلقة');
        }

        // Load existing schedule if any
        $circle->load('schedule');

        return view('teacher.group-circles.schedule-form', compact('circle'));
    }

    /**
     * Store or update schedule for a group circle
     */
    public function store($subdomain, Request $request, QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();

        // Check ownership
        if (! $user->quranTeacherProfile || $circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'weekly_schedule' => 'required|array|min:1',
            'weekly_schedule.*.day' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'weekly_schedule.*.time' => 'required|regex:/^\d{2}:\d{2}$/',
            'weekly_schedule.*.duration' => 'integer|min:15|max:240',
            'schedule_starts_at' => 'required|date|after_or_equal:today',
            'schedule_ends_at' => 'nullable|date|after:schedule_starts_at',
            'session_title_template' => 'nullable|string|max:255',
            'session_description_template' => 'nullable|string',
            'meeting_link' => 'nullable|url',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:50',
            'recording_enabled' => 'boolean',
            'generate_ahead_days' => 'integer|min:7|max:90',
        ]);

        try {
            // Prepare schedule data
            $scheduleData = [
                'weekly_schedule' => $request->weekly_schedule,
                'schedule_starts_at' => Carbon::parse($request->schedule_starts_at),
                'schedule_ends_at' => $request->schedule_ends_at ? Carbon::parse($request->schedule_ends_at) : null,
                'session_title_template' => $request->session_title_template,
                'session_description_template' => $request->session_description_template,
                'meeting_link' => $request->meeting_link,
                'meeting_id' => $request->meeting_id,
                'meeting_password' => $request->meeting_password,
                'recording_enabled' => $request->boolean('recording_enabled'),
                'generate_ahead_days' => $request->generate_ahead_days ?? 30,
            ];

            // Determine default duration from schedule
            $defaultDuration = collect($request->weekly_schedule)->avg('duration') ?? 60;
            $scheduleData['duration'] = $defaultDuration;

            if ($circle->schedule) {
                // Update existing schedule
                $schedule = $this->schedulingService->updateGroupCircleSchedule(
                    $circle->schedule,
                    $scheduleData
                );
                $message = 'تم تحديث جدول الحلقة بنجاح';
            } else {
                // Create new schedule
                $schedule = $this->schedulingService->createGroupCircleSchedule(
                    $circle,
                    $request->weekly_schedule,
                    Carbon::parse($request->schedule_starts_at),
                    $request->schedule_ends_at ? Carbon::parse($request->schedule_ends_at) : null,
                    $scheduleData
                );
                $message = 'تم إنشاء جدول الحلقة بنجاح';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'schedule_id' => $schedule->id,
                'circle_status' => $circle->fresh()->status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حفظ الجدول: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate schedule for a group circle
     */
    public function activate($subdomain, QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();

        // Check ownership
        if (! $user->quranTeacherProfile || $circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        if (! $circle->schedule) {
            return response()->json([
                'success' => false,
                'message' => 'يجب إنشاء جدول زمني أولاً',
            ], 400);
        }

        try {
            $circle->schedule->activateSchedule();

            return response()->json([
                'success' => true,
                'message' => 'تم تفعيل جدول الحلقة بنجاح',
                'circle_status' => $circle->fresh()->status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تفعيل الجدول: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate schedule for a group circle
     */
    public function deactivate($subdomain, QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();

        // Check ownership
        if (! $user->quranTeacherProfile || $circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        if (! $circle->schedule) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد جدول زمني لإلغاء تفعيله',
            ], 400);
        }

        try {
            $circle->schedule->deactivateSchedule();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء تفعيل جدول الحلقة بنجاح',
                'circle_status' => $circle->fresh()->status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إلغاء تفعيل الجدول: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview upcoming sessions for a schedule
     */
    public function previewSessions($subdomain, Request $request, QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();

        // Check ownership
        if (! $user->quranTeacherProfile || $circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'weekly_schedule' => 'required|array|min:1',
            'weekly_schedule.*.day' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'weekly_schedule.*.time' => 'required|regex:/^\d{2}:\d{2}$/',
            'schedule_starts_at' => 'required|date',
            'schedule_ends_at' => 'nullable|date|after:schedule_starts_at',
            'preview_days' => 'integer|min:7|max:90',
        ]);

        try {
            // Create a temporary schedule object for preview
            $tempSchedule = new QuranCircleSchedule([
                'weekly_schedule' => $request->weekly_schedule,
                'schedule_starts_at' => $request->schedule_starts_at,
                'schedule_ends_at' => $request->schedule_ends_at,
                'default_duration_minutes' => 60,
            ]);

            $previewDays = $request->preview_days ?? 30;
            $startDate = Carbon::parse($request->schedule_starts_at);
            $endDate = $startDate->copy()->addDays($previewDays);

            if ($request->schedule_ends_at) {
                $endDate = $endDate->min(Carbon::parse($request->schedule_ends_at));
            }

            $upcomingSessions = $tempSchedule->getUpcomingSessionsForRange($startDate, $endDate);

            return response()->json([
                'success' => true,
                'preview_period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'days' => $startDate->diffInDays($endDate),
                ],
                'sessions_count' => count($upcomingSessions),
                'upcoming_sessions' => array_slice($upcomingSessions, 0, 10), // First 10 for preview
                'weekly_pattern' => $request->weekly_schedule,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في معاينة الجلسات: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show circle schedule details and generated sessions
     */
    public function show($subdomain, QuranCircle $circle)
    {
        $user = Auth::user();

        // Check ownership
        if (! $user->quranTeacherProfile || $circle->quran_teacher_id !== $user->id) {
            abort(403, 'غير مسموح لك بالوصول لهذه الحلقة');
        }

        $circle->load([
            'schedule',
            'sessions' => function ($query) {
                $query->where('is_auto_generated', true)
                    ->orderBy('scheduled_at', 'desc')
                    ->limit(50); // Last 50 sessions
            },
            'students',
            'academy',
        ]);

        // Get upcoming sessions
        $upcomingSessions = $circle->sessions()
            ->where('scheduled_at', '>=', now())
            ->where('status', SessionStatus::SCHEDULED->value)
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        // Get recent sessions
        $recentSessions = $circle->sessions()
            ->where('scheduled_at', '<', now())
            ->orderBy('scheduled_at', 'desc')
            ->limit(10)
            ->get();

        // Prepare teacher data structure that the view expects
        $teacherData = [
            'upcomingSessions' => $upcomingSessions,
            'recentSessions' => $recentSessions,
        ];

        // Determine user role
        $userRole = 'teacher'; // Since this is in teacher routes

        // Get the academy for the view
        $academy = $circle->academy;

        return view('teacher.group-circles.show', compact('circle', 'upcomingSessions', 'teacherData', 'userRole', 'academy'));
    }

    /**
     * Schedule a single group session
     */
    public function scheduleSession($subdomain, Request $request, QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();

        // Check ownership
        if (! $user->quranTeacherProfile || $circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'nullable|integer|min:30|max:180',
        ]);

        try {
            $session = $circle->sessions()->create([
                'title' => $request->title ?: 'جلسة قرآنية جماعية',
                'description' => $request->description,
                'scheduled_at' => Carbon::parse($request->scheduled_at),
                'duration_minutes' => $request->duration_minutes ?: $circle->default_duration_minutes ?: 60,
                'status' => SessionStatus::SCHEDULED,
                'is_auto_generated' => false,
                'academy_id' => $circle->academy_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم جدولة الجلسة بنجاح',
                'session' => $session,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جدولة الجلسة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate additional sessions manually
     */
    public function generateSessions($subdomain, QuranCircle $circle): JsonResponse
    {
        $user = Auth::user();

        // Check ownership
        if (! $user->quranTeacherProfile || $circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        if (! $circle->schedule || ! $circle->schedule->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الجدول الزمني غير نشط',
            ], 400);
        }

        try {
            $generatedCount = $circle->schedule->generateUpcomingSessions();

            return response()->json([
                'success' => true,
                'message' => $generatedCount > 0
                    ? "تم إنشاء {$generatedCount} جلسة جديدة"
                    : 'لا توجد جلسات جديدة للإنشاء',
                'generated_count' => $generatedCount,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إنشاء الجلسات: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate comprehensive progress report for the group circle
     */
    public function progressReport($subdomain, $circle)
    {
        $user = Auth::user();

        // Find the circle
        $circle = QuranCircle::findOrFail($circle);

        // Check ownership - user should be the teacher of this circle
        if (! $circle->quranTeacher || $circle->quranTeacher->id !== $user->id) {
            abort(403, 'غير مسموح لك بالوصول لهذا التقرير');
        }

        // Load comprehensive data for enhanced progress tracking
        $circle->load([
            'students.studentProfile',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'schedule',
            'academy',
        ]);

        // Get detailed session statistics for attendance analysis
        $totalSessions = $circle->sessions->count();
        $completedSessions = $circle->sessions->where('status', SessionStatus::COMPLETED->value);
        $scheduledSessions = $circle->sessions->where('status', SessionStatus::SCHEDULED->value);

        // Enhanced attendance analysis with different statuses
        $attendedSessions = $completedSessions->where('attendance_status', AttendanceStatus::ATTENDED->value)->count();
        $lateSessions = $completedSessions->where('attendance_status', AttendanceStatus::LATE->value)->count();
        $absentSessions = $completedSessions->where('attendance_status', AttendanceStatus::ABSENT->value)->count();
        $leftEarlySessions = $completedSessions->where('attendance_status', 'left_early')->count();

        // For sessions without explicit attendance_status, assume attended if completed
        $completedWithoutStatus = $completedSessions->whereNull('attendance_status')->count();
        $totalAttended = $attendedSessions + $lateSessions + $leftEarlySessions + $completedWithoutStatus;

        // Performance metrics calculation
        $avgDuration = $completedSessions->avg('actual_duration_minutes') ?? 0;

        // Group circle specific metrics
        $enrolledStudents = $circle->students->count();
        $maxStudents = $circle->max_students ?? 0;
        $enrollmentRate = $maxStudents > 0 ? ($enrolledStudents / $maxStudents) * 100 : 0;

        // Calculate average attendance rate per session for group circles
        $sessionAttendanceRates = [];
        foreach ($completedSessions as $session) {
            // For group circles, calculate how many students attended each session
            $sessionAttendance = 1; // Default for now, can be enhanced with actual student attendance tracking
            $sessionAttendanceRates[] = $sessionAttendance;
        }
        $avgSessionAttendance = count($sessionAttendanceRates) > 0 ? array_sum($sessionAttendanceRates) / count($sessionAttendanceRates) : 0;

        // Comprehensive statistics for the enhanced view
        $stats = [
            // Basic session counts
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions->count(),
            'scheduled_sessions' => $scheduledSessions->count(),
            'upcoming_sessions' => $circle->sessions()
                ->where('scheduled_at', '>=', now())
                ->where('status', SessionStatus::SCHEDULED->value)
                ->count(),

            // Group circle specific metrics
            'enrolled_students' => $enrolledStudents,
            'max_students' => $maxStudents,
            'enrollment_rate' => $enrollmentRate,
            'available_spots' => max(0, $maxStudents - $enrolledStudents),

            // Enhanced attendance metrics
            'attendance_rate' => $completedSessions->count() > 0
                ? ($totalAttended / $completedSessions->count()) * 100
                : 0,
            'attended_sessions' => $attendedSessions,
            'late_sessions' => $lateSessions,
            'absent_sessions' => $absentSessions,
            'left_early_sessions' => $leftEarlySessions,

            // Performance and progress metrics
            'avg_session_duration' => $avgDuration,
            'avg_session_attendance' => $avgSessionAttendance,

            // Learning analytics
            'consistency_score' => $this->calculateGroupConsistencyScore($circle),
            'schedule_adherence' => $this->calculateScheduleAdherence($circle),
        ];

        return view('teacher.group-circles.progress', compact('circle', 'stats'));
    }

    /**
     * Calculate consistency score for group circle based on session regularity
     */
    private function calculateGroupConsistencyScore($circle): float
    {
        $sessions = $circle->sessions()->where('status', SessionStatus::COMPLETED->value)->orderBy('scheduled_at')->get();

        if ($sessions->count() < 2) {
            return 0;
        }

        $completionPattern = $sessions->map(function ($session) {
            // Score based on session completion and quality
            $baseScore = $session->status === SessionStatus::COMPLETED ? 1.0 : 0.0;

            // Bonus for quality metrics
            if ($session->recitation_quality > 0) {
                $baseScore += ($session->recitation_quality / 10) * 0.2;
            }
            if ($session->tajweed_accuracy > 0) {
                $baseScore += ($session->tajweed_accuracy / 10) * 0.2;
            }

            return min(1.0, $baseScore);
        });

        return round($completionPattern->avg() * 10, 1);
    }

    /**
     * Calculate how well the circle adheres to its schedule
     */
    private function calculateScheduleAdherence($circle): float
    {
        if (! $circle->schedule) {
            return 0;
        }

        $scheduledSessions = $circle->sessions()->where('status', '!=', 'template')->get();
        $completedOnTime = $scheduledSessions->filter(function ($session) {
            return $session->status === SessionStatus::COMPLETED &&
                   (! $session->started_at || $session->started_at->lte($session->scheduled_at->addMinutes(15)));
        })->count();

        return $scheduledSessions->count() > 0
            ? round(($completedOnTime / $scheduledSessions->count()) * 10, 1)
            : 0;
    }

    /**
     * Get schedule statistics
     */
    public function getStats($subdomain, $circle): JsonResponse
    {
        $user = Auth::user();

        // Find the circle
        $circle = QuranCircle::findOrFail($circle);

        // Check ownership
        if (! $user->quranTeacherProfile || $circle->quran_teacher_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح'], 403);
        }

        $stats = [
            'total_sessions_generated' => $circle->sessions()->where('is_auto_generated', true)->count(),
            'completed_sessions' => $circle->sessions()->where('status', SessionStatus::COMPLETED->value)->count(),
            'upcoming_sessions' => $circle->sessions()
                ->where('scheduled_at', '>=', now())
                ->where('status', SessionStatus::SCHEDULED->value)
                ->count(),
            'cancelled_sessions' => $circle->sessions()->where('status', SessionStatus::CANCELLED->value)->count(),
            'enrolled_students' => $circle->enrolled_students,
            'attendance_rate' => $circle->sessions()->where('status', SessionStatus::COMPLETED->value)->count() > 0
                ? ($circle->sessions()->where('attendance_status', AttendanceStatus::ATTENDED->value)->count() /
                   $circle->sessions()->where('status', SessionStatus::COMPLETED->value)->count()) * 100
                : 0,
            'schedule_active' => $circle->schedule && $circle->schedule->is_active,
            'last_generated_at' => $circle->schedule?->last_generated_at?->format('Y-m-d H:i'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Generate progress report for a specific student within the group circle
     */
    public function studentProgressReport($subdomain, $circle, $student)
    {
        $user = Auth::user();

        // Find the circle and student
        $circle = QuranCircle::findOrFail($circle);
        $student = User::findOrFail($student);

        // Check ownership - user should be the teacher of this circle
        if (! $circle->quranTeacher || $circle->quranTeacher->id !== $user->id) {
            abort(403, 'غير مسموح لك بالوصول لهذا التقرير');
        }

        // Verify the student is enrolled in this circle
        if (! $circle->students->contains($student)) {
            abort(404, 'الطالب غير مسجل في هذه الحلقة');
        }

        // Get all sessions for this circle (group sessions don't have individual student_id)
        $sessions = $circle->sessions()
            ->orderBy('scheduled_at', 'desc')
            ->get();

        // Calculate student-specific statistics for this circle using new report system
        $studentReports = \App\Models\StudentSessionReport::where('student_id', $student->id)
            ->whereIn('session_id', $sessions->pluck('id'))
            ->get();

        $attendedReports = $studentReports->whereIn('attendance_status', [
            AttendanceStatus::ATTENDED->value,
            AttendanceStatus::LATE->value,
            AttendanceStatus::LEAVED->value
        ]);

        $stats = [
            'total_sessions' => $circle->sessions()->count(),
            'attended_sessions' => $attendedReports->count(),
            'missed_sessions' => $studentReports->where('attendance_status', AttendanceStatus::ABSENT->value)->count(),
            'attendance_rate' => $sessions->where('status', SessionStatus::COMPLETED->value)->count() > 0 ?
                ($attendedReports->count() / $sessions->where('status', SessionStatus::COMPLETED->value)->count()) * 100 : 0,
            'avg_memorization_degree' => $studentReports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?: 0,
            'avg_reservation_degree' => $studentReports->whereNotNull('reservation_degree')->avg('reservation_degree') ?: 0,
            'avg_attendance_percentage' => $studentReports->avg('attendance_percentage') ?: 0,
            'latest_report' => $studentReports->sortByDesc('created_at')->first(),
            'improvement_trend' => $this->calculateImprovementTrend($studentReports),
        ];

        return view('teacher.group-circles.student-progress', compact('circle', 'student', 'sessions', 'stats'));
    }

    /**
     * Calculate improvement trend based on recent performance
     */
    protected function calculateImprovementTrend($reports): string
    {
        if ($reports->count() < 2) {
            return 'insufficient_data';
        }

        $recentReports = $reports->sortByDesc('created_at')->take(5);
        $olderReports = $reports->sortByDesc('created_at')->skip(5)->take(5);

        $recentAvg = $recentReports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?: 0;
        $olderAvg = $olderReports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree') ?: 0;

        if ($recentAvg > $olderAvg + 0.5) {
            return 'improving';
        } elseif ($recentAvg < $olderAvg - 0.5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
}
