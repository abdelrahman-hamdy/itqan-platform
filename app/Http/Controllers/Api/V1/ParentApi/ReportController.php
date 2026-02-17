<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Models\StudentSessionReport;
use App\Models\AcademicSessionReport;
use BackedEnum;
use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseSession;
use App\Models\ParentStudentRelationship;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    use ApiResponses;

    /**
     * Get progress report for all children or a specific child.
     */
    public function progress(Request $request, ?int $childId = null): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get children
        $childrenQuery = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user');

        if ($childId) {
            $childrenQuery->where('student_id', $childId);
        }

        $children = $childrenQuery->get();

        if ($children->isEmpty()) {
            return $this->notFound(__('No children found.'));
        }

        $reports = [];

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $student->user?->id ?? $student->id;

            // Quran progress
            $quranProgress = $this->getQuranProgress($studentUserId);

            // Academic progress
            $academicProgress = $this->getAcademicProgress($studentUserId);

            // Course progress
            $courseProgress = $this->getCourseProgress($studentUserId);

            $reports[] = [
                'child' => [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'avatar' => $student->avatar ? asset('storage/'.$student->avatar) : null,
                    'grade_level' => $student->gradeLevel?->name,
                ],
                'quran' => $quranProgress,
                'academic' => $academicProgress,
                'courses' => $courseProgress,
                'overall_stats' => [
                    'total_sessions_completed' => $quranProgress['completed_sessions'] +
                        $academicProgress['completed_sessions'] +
                        $courseProgress['completed_sessions'],
                    'attendance_rate' => $this->calculateOverallAttendanceRate($studentUserId),
                    'active_subscriptions' => $quranProgress['active_subscriptions'] +
                        $academicProgress['active_subscriptions'] +
                        $courseProgress['active_enrollments'],
                ],
            ];
        }

        return $this->success([
            'reports' => $childId ? $reports[0] ?? null : $reports,
        ], __('Progress report retrieved successfully'));
    }

    /**
     * Get attendance report for all children or a specific child.
     */
    public function attendance(Request $request, ?int $childId = null): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get children
        $childrenQuery = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user');

        if ($childId) {
            $childrenQuery->where('student_id', $childId);
        }

        $children = $childrenQuery->get();

        if ($children->isEmpty()) {
            return $this->notFound(__('No children found.'));
        }

        // Date range
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->subDays(30);
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now();

        $reports = [];

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $student->user?->id ?? $student->id;

            // Get session attendance
            $quranAttendance = $this->getSessionAttendance('quran', $studentUserId, $startDate, $endDate);
            $academicAttendance = $this->getSessionAttendance('academic', $studentUserId, $startDate, $endDate);
            $courseAttendance = $this->getSessionAttendance('course', $studentUserId, $startDate, $endDate);

            $totalSessions = $quranAttendance['total'] + $academicAttendance['total'] + $courseAttendance['total'];
            $totalAttended = $quranAttendance['attended'] + $academicAttendance['attended'] + $courseAttendance['attended'];
            $totalMissed = $quranAttendance['missed'] + $academicAttendance['missed'] + $courseAttendance['missed'];

            $reports[] = [
                'child' => [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'avatar' => $student->avatar ? asset('storage/'.$student->avatar) : null,
                ],
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'summary' => [
                    'total_sessions' => $totalSessions,
                    'attended' => $totalAttended,
                    'missed' => $totalMissed,
                    'attendance_rate' => $totalSessions > 0
                        ? round(($totalAttended / $totalSessions) * 100, 1)
                        : 0,
                ],
                'by_type' => [
                    'quran' => $quranAttendance,
                    'academic' => $academicAttendance,
                    'course' => $courseAttendance,
                ],
            ];
        }

        return $this->success([
            'reports' => $childId ? $reports[0] ?? null : $reports,
        ], __('Attendance report retrieved successfully'));
    }

    /**
     * Get subscription report.
     */
    public function subscription(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get all linked children's user IDs
        $childUserIds = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user')
            ->get()
            ->map(fn ($r) => $r->student->user?->id ?? $r->student->id)
            ->filter()
            ->toArray();

        $subscription = match ($type) {
            'quran' => QuranSubscription::where('id', $id)
                ->whereIn('student_id', $childUserIds)
                ->with(['quranTeacher.user', 'student.user', 'sessions.reports'])
                ->first(),
            'academic' => AcademicSubscription::where('id', $id)
                ->whereIn('student_id', $childUserIds)
                ->with(['academicTeacher.user', 'student.user', 'sessions.reports'])
                ->first(),
            default => null,
        };

        if (! $subscription) {
            return $this->notFound(__('Subscription not found.'));
        }

        // Build detailed report
        $sessions = $subscription->sessions ?? collect();
        $completedSessions = $sessions->where('status', SessionStatus::COMPLETED->value);
        $upcomingSessions = $sessions->whereIn('status', [
            SessionStatus::SCHEDULED->value,
            SessionStatus::READY->value,
            SessionStatus::ONGOING->value,
        ]);

        $report = [
            'subscription' => [
                'id' => $subscription->id,
                'type' => $type,
                'name' => $type === 'quran'
                    ? ($subscription->individualCircle?->name ?? $subscription->circle?->name ?? 'اشتراك قرآني')
                    : ($subscription->subject?->name ?? $subscription->subject_name ?? 'اشتراك أكاديمي'),
                'status' => $subscription->status,
                'start_date' => $subscription->start_date?->toDateString(),
                'end_date' => $subscription->end_date?->toDateString(),
            ],
            'child' => [
                'id' => $subscription->student?->id,
                'name' => $subscription->student?->user?->name ?? $subscription->student?->full_name,
            ],
            'teacher' => $type === 'quran'
                ? ($subscription->quranTeacher?->user ? [
                    'id' => $subscription->quranTeacher->user->id,
                    'name' => $subscription->quranTeacher->user->name,
                ] : null)
                : ($subscription->academicTeacher?->user ? [
                    'id' => $subscription->academicTeacher->user->id,
                    'name' => $subscription->academicTeacher->user->name,
                ] : null),
            'progress' => [
                'sessions_total' => $subscription->sessions_count,
                'sessions_completed' => $completedSessions->count(),
                'sessions_remaining' => $subscription->remaining_sessions ?? ($subscription->sessions_count - $completedSessions->count()),
                'completion_percentage' => $subscription->sessions_count > 0
                    ? round(($completedSessions->count() / $subscription->sessions_count) * 100, 1)
                    : 0,
            ],
            'attendance' => $this->calculateSubscriptionAttendance($subscription, $type, $completedSessions),
            'recent_sessions' => $completedSessions->sortByDesc('scheduled_at')->take(5)->map(fn ($s) => [
                'id' => $s->id,
                'scheduled_at' => $s->scheduled_at?->toISOString(),
                'status' => $s->status->value ?? $s->status,
                'attended' => $s->attended ?? false,
                'rating' => $s->reports?->first()?->rating ?? null,
                'notes' => $s->reports?->first()?->notes ?? null,
            ])->values()->toArray(),
            'upcoming_sessions' => $upcomingSessions->sortBy('scheduled_at')->take(3)->map(fn ($s) => [
                'id' => $s->id,
                'scheduled_at' => $s->scheduled_at?->toISOString(),
                'status' => $s->status->value ?? $s->status,
            ])->values()->toArray(),
        ];

        return $this->success([
            'report' => $report,
        ], __('Subscription report retrieved successfully'));
    }

    /**
     * Get Quran progress for a student.
     */
    protected function getQuranProgress(int $studentUserId): array
    {
        $subscriptions = QuranSubscription::where('student_id', $studentUserId)->get();
        $activeSubscriptions = $subscriptions->where('status', SessionSubscriptionStatus::ACTIVE->value)->count();

        $completedSessions = QuranSession::where('student_id', $studentUserId)
            ->where('status', SessionStatus::COMPLETED->value)
            ->count();

        $totalSessions = QuranSession::where('student_id', $studentUserId)->count();

        // Get memorization stats if available
        $recentSession = QuranSession::where('student_id', $studentUserId)
            ->where('status', SessionStatus::COMPLETED->value)
            ->orderBy('scheduled_at', 'desc')
            ->first();

        return [
            'active_subscriptions' => $activeSubscriptions,
            'total_subscriptions' => $subscriptions->count(),
            'completed_sessions' => $completedSessions,
            'total_sessions' => $totalSessions,
            'memorized_pages' => $recentSession?->total_memorized_pages ?? null,
        ];
    }

    /**
     * Get Academic progress for a student.
     */
    protected function getAcademicProgress(int $studentUserId): array
    {
        $subscriptions = AcademicSubscription::where('student_id', $studentUserId)->get();
        $activeSubscriptions = $subscriptions->where('status', SessionSubscriptionStatus::ACTIVE->value)->count();

        $completedSessions = AcademicSession::where('student_id', $studentUserId)
            ->where('status', SessionStatus::COMPLETED->value)
            ->count();

        $totalSessions = AcademicSession::where('student_id', $studentUserId)->count();

        return [
            'active_subscriptions' => $activeSubscriptions,
            'total_subscriptions' => $subscriptions->count(),
            'completed_sessions' => $completedSessions,
            'total_sessions' => $totalSessions,
            'subjects' => $subscriptions->map(fn ($s) => [
                'name' => $s->subject?->name ?? $s->subject_name,
                'status' => $s->status,
            ])->unique('name')->values()->toArray(),
        ];
    }

    /**
     * Get Course progress for a student.
     */
    protected function getCourseProgress(int $studentId): array
    {
        $enrollments = CourseSubscription::where('student_id', $studentId)
            ->with(['interactiveCourse', 'recordedCourse'])
            ->get();

        $activeEnrollments = $enrollments->where('status', EnrollmentStatus::ENROLLED->value)->count();
        $completedEnrollments = $enrollments->where('status', EnrollmentStatus::COMPLETED->value)->count();

        $completedSessions = $enrollments->sum('completed_sessions');
        $totalSessions = $enrollments->sum(fn ($e) => $e->interactiveCourse?->total_sessions ?? $e->recordedCourse?->total_lessons ?? 0);

        return [
            'active_enrollments' => $activeEnrollments,
            'completed_enrollments' => $completedEnrollments,
            'total_enrollments' => $enrollments->count(),
            'completed_sessions' => $completedSessions,
            'total_sessions' => $totalSessions,
            'average_progress' => $enrollments->count() > 0
                ? round($enrollments->avg('progress_percentage') ?? 0, 1)
                : 0,
        ];
    }

    /**
     * Calculate subscription attendance from session reports.
     */
    protected function calculateSubscriptionAttendance($subscription, string $type, $completedSessions): array
    {
        $studentId = $subscription->student_id;
        $sessionIds = $completedSessions->pluck('id');

        if ($type === 'quran') {
            $reports = StudentSessionReport::whereIn('session_id', $sessionIds)
                ->where('student_id', $studentId)
                ->get();
        } else {
            $reports = AcademicSessionReport::whereIn('session_id', $sessionIds)
                ->where('student_id', $studentId)
                ->get();
        }

        $total = $reports->count();

        $attended = $reports->filter(function ($report) {
            $status = $report->attendance_status;
            if ($status instanceof BackedEnum) {
                $status = $status->value;
            }

            return in_array($status, [AttendanceStatus::ATTENDED->value, AttendanceStatus::LATE->value]);
        })->count();

        $missed = $reports->filter(function ($report) {
            $status = $report->attendance_status;
            if ($status instanceof BackedEnum) {
                $status = $status->value;
            }

            return $status === SessionStatus::ABSENT;
        })->count();

        return [
            'total_scheduled' => $total,
            'attended' => $attended,
            'missed' => $missed,
            'attendance_rate' => $total > 0
                ? round(($attended / $total) * 100, 1)
                : 0,
        ];
    }

    /**
     * Calculate overall attendance rate from actual session reports.
     */
    protected function calculateOverallAttendanceRate(int $studentUserId): float
    {
        // Get Quran session attendance from reports
        $quranReports = StudentSessionReport::where('student_id', $studentUserId)
            ->whereHas('session', function ($q) {
                $q->countable();
            })
            ->get();

        // Get Academic session attendance from reports
        $academicReports = AcademicSessionReport::where('student_id', $studentUserId)
            ->whereHas('session', function ($q) {
                $q->countable();
            })
            ->get();

        $totalReports = $quranReports->count() + $academicReports->count();

        if ($totalReports === 0) {
            return 0;
        }

        // Count attended (includes late) from reports
        $quranAttended = $quranReports->filter(function ($report) {
            $status = $report->attendance_status;
            if ($status instanceof BackedEnum) {
                $status = $status->value;
            }

            return in_array($status, [AttendanceStatus::ATTENDED->value, AttendanceStatus::LATE->value]);
        })->count();

        $academicAttended = $academicReports->filter(function ($report) {
            $status = $report->attendance_status;
            if ($status instanceof BackedEnum) {
                $status = $status->value;
            }

            return in_array($status, [AttendanceStatus::ATTENDED->value, AttendanceStatus::LATE->value]);
        })->count();

        $totalAttended = $quranAttended + $academicAttended;

        return round(($totalAttended / $totalReports) * 100, 1);
    }

    /**
     * Get session attendance stats from actual report data.
     */
    protected function getSessionAttendance(string $type, int $studentUserId, Carbon $startDate, Carbon $endDate): array
    {
        if ($type === 'quran') {
            $sessions = QuranSession::where('student_id', $studentUserId)
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->countable()
                ->with('reports')
                ->get();

            $reports = $sessions->flatMap(function ($session) use ($studentUserId) {
                return $session->reports->where('student_id', $studentUserId);
            });
        } elseif ($type === 'academic') {
            $sessions = AcademicSession::where('student_id', $studentUserId)
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->countable()
                ->with('reports')
                ->get();

            $reports = $sessions->flatMap(function ($session) use ($studentUserId) {
                return $session->reports->where('student_id', $studentUserId);
            });
        } else {
            // Course sessions - check enrollments
            $enrolledCourseIds = CourseSubscription::where('student_id', $studentUserId)
                ->pluck('interactive_course_id')
                ->filter();

            $sessions = InteractiveCourseSession::whereIn('course_id', $enrolledCourseIds)
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->countable()
                ->with('studentReports')
                ->get();

            $reports = $sessions->flatMap(function ($session) use ($studentUserId) {
                return $session->studentReports->where('student_id', $studentUserId);
            });
        }

        $total = $reports->count();

        // Count actual attendance from reports
        $attended = $reports->filter(function ($report) {
            $status = $report->attendance_status;
            if ($status instanceof BackedEnum) {
                $status = $status->value;
            }

            return in_array($status, [AttendanceStatus::ATTENDED->value, AttendanceStatus::LATE->value]);
        })->count();

        $missed = $reports->filter(function ($report) {
            $status = $report->attendance_status;
            if ($status instanceof BackedEnum) {
                $status = $status->value;
            }

            return $status === SessionStatus::ABSENT;
        })->count();

        return [
            'total' => $total,
            'attended' => $attended,
            'missed' => $missed,
            'attendance_rate' => $total > 0 ? round(($attended / $total) * 100, 1) : 0,
        ];
    }
}
