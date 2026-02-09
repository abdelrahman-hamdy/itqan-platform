<?php

namespace App\Http\Controllers\Api\V1\ParentApi\Reports;

use App\Enums\EnrollmentStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentSessionReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unified report controller for parents.
 *
 * Provides aggregated progress and attendance reports across
 * all learning types (Quran, Academic, Interactive Courses).
 */
class ParentUnifiedReportController extends BaseParentReportController
{
    /**
     * Get overall progress report for all children or a specific child.
     */
    public function progress(Request $request, ?int $childId = null): JsonResponse
    {
        $result = $this->validateParentAccess($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        [$user, $parentProfile] = $result;

        $children = $this->getChildren($parentProfile->id, $childId);

        if ($children->isEmpty()) {
            return $this->notFound(__('No children found.'));
        }

        $reports = [];

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $this->getStudentUserId($student);

            // Quran progress
            $quranProgress = $this->getQuranProgress($studentUserId);

            // Academic progress
            $academicProgress = $this->getAcademicProgress($studentUserId);

            // Course progress
            $courseProgress = $this->getCourseProgress($studentUserId);

            $reports[] = [
                'child' => $this->formatChildData($student),
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
            'reports' => $childId ? ($reports[0] ?? null) : $reports,
        ], __('Overall progress report retrieved successfully'));
    }

    /**
     * Get overall attendance report for all children or a specific child.
     */
    public function attendance(Request $request, ?int $childId = null): JsonResponse
    {
        $result = $this->validateParentAccess($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        [$user, $parentProfile] = $result;

        $children = $this->getChildren($parentProfile->id, $childId);

        if ($children->isEmpty()) {
            return $this->notFound(__('No children found.'));
        }

        [$startDate, $endDate] = $this->getDateRange($request);

        $reports = [];

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $this->getStudentUserId($student);

            // Get session attendance
            $quranAttendance = $this->getQuranAttendance($studentUserId, $startDate, $endDate);
            $academicAttendance = $this->getAcademicAttendance($studentUserId, $startDate, $endDate);

            $totalSessions = $quranAttendance['total'] + $academicAttendance['total'];
            $totalAttended = $quranAttendance['attended'] + $academicAttendance['attended'];
            $totalMissed = $quranAttendance['missed'] + $academicAttendance['missed'];

            $reports[] = [
                'child' => $this->formatChildData($student),
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
                ],
            ];
        }

        return $this->success([
            'reports' => $childId ? ($reports[0] ?? null) : $reports,
        ], __('Overall attendance report retrieved successfully'));
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
     * Get Quran attendance stats.
     */
    protected function getQuranAttendance(int $studentUserId, $startDate, $endDate): array
    {
        $sessions = QuranSession::where('student_id', $studentUserId)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->countable()
            ->with('reports')
            ->get();

        $reports = $sessions->flatMap(function ($session) use ($studentUserId) {
            return $session->reports->where('student_id', $studentUserId);
        });

        $total = $reports->count();
        $attended = $this->countAttended($reports);
        $missed = $this->countMissed($reports);

        return [
            'total' => $total,
            'attended' => $attended,
            'missed' => $missed,
            'attendance_rate' => $total > 0 ? $this->calculateAttendanceRate($reports) : 0,
        ];
    }

    /**
     * Get Academic attendance stats.
     */
    protected function getAcademicAttendance(int $studentUserId, $startDate, $endDate): array
    {
        $sessions = AcademicSession::where('student_id', $studentUserId)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->countable()
            ->with('reports')
            ->get();

        $reports = $sessions->flatMap(function ($session) use ($studentUserId) {
            return $session->reports->where('student_id', $studentUserId);
        });

        $total = $reports->count();
        $attended = $this->countAttended($reports);
        $missed = $this->countMissed($reports);

        return [
            'total' => $total,
            'attended' => $attended,
            'missed' => $missed,
            'attendance_rate' => $total > 0 ? $this->calculateAttendanceRate($reports) : 0,
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

        $quranAttended = $this->countAttended($quranReports);
        $academicAttended = $this->countAttended($academicReports);
        $totalAttended = $quranAttended + $academicAttended;

        return round(($totalAttended / $totalReports) * 100, 1);
    }
}
