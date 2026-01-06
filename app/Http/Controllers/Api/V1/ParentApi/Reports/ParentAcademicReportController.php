<?php

namespace App\Http\Controllers\Api\V1\ParentApi\Reports;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\AcademicSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles Academic progress reports for parents.
 *
 * Provides progress tracking, attendance reports, and subscription
 * details for children's academic learning.
 */
class ParentAcademicReportController extends BaseParentReportController
{
    /**
     * Get Academic progress report for children.
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

            $progress = $this->getAcademicProgress($studentUserId);

            $reports[] = [
                'child' => $this->formatChildData($student),
                'academic' => $progress,
            ];
        }

        return $this->success([
            'reports' => $childId ? ($reports[0] ?? null) : $reports,
        ], __('Academic progress report retrieved successfully'));
    }

    /**
     * Get Academic attendance report for children.
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

            $attendance = $this->getAcademicAttendance($studentUserId, $startDate, $endDate);

            $reports[] = [
                'child' => $this->formatChildData($student),
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'academic' => $attendance,
            ];
        }

        return $this->success([
            'reports' => $childId ? ($reports[0] ?? null) : $reports,
        ], __('Academic attendance report retrieved successfully'));
    }

    /**
     * Get Academic subscription report.
     */
    public function subscription(Request $request, int $id): JsonResponse
    {
        $result = $this->validateParentAccess($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        [$user, $parentProfile] = $result;

        // Get all linked children's user IDs
        $childUserIds = $this->getChildren($parentProfile->id)
            ->map(fn ($r) => $this->getStudentUserId($r->student))
            ->filter()
            ->toArray();

        $subscription = AcademicSubscription::where('id', $id)
            ->whereIn('student_id', $childUserIds)
            ->with(['academicTeacher.user', 'student.user', 'sessions.reports'])
            ->first();

        if (! $subscription) {
            return $this->notFound(__('Subscription not found.'));
        }

        $report = $this->buildSubscriptionReport($subscription);

        return $this->success([
            'report' => $report,
        ], __('Academic subscription report retrieved successfully'));
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
     * Get Academic attendance stats.
     *
     * @param  \Illuminate\Support\Carbon  $startDate
     * @param  \Illuminate\Support\Carbon  $endDate
     */
    protected function getAcademicAttendance(int $studentUserId, $startDate, $endDate): array
    {
        $sessions = AcademicSession::where('student_id', $studentUserId)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->whereIn('status', [SessionStatus::COMPLETED->value, SessionStatus::ABSENT->value])
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
     * Build detailed subscription report.
     */
    protected function buildSubscriptionReport(AcademicSubscription $subscription): array
    {
        $sessions = $subscription->sessions ?? collect();
        $completedSessions = $sessions->where('status', SessionStatus::COMPLETED->value);
        $upcomingSessions = $sessions->whereIn('status', [
            SessionStatus::SCHEDULED->value,
            SessionStatus::READY->value,
            SessionStatus::ONGOING->value,
        ]);

        return [
            'subscription' => [
                'id' => $subscription->id,
                'type' => 'academic',
                'name' => $subscription->subject?->name ?? $subscription->subject_name ?? 'اشتراك أكاديمي',
                'status' => $subscription->status,
                'start_date' => $subscription->start_date?->toDateString(),
                'end_date' => $subscription->end_date?->toDateString(),
            ],
            'child' => [
                'id' => $subscription->student?->id,
                'name' => $subscription->student?->user?->name ?? $subscription->student?->full_name,
            ],
            'teacher' => $subscription->academicTeacher?->user ? [
                'id' => $subscription->academicTeacher->user->id,
                'name' => $subscription->academicTeacher->user->name,
            ] : null,
            'progress' => [
                'sessions_total' => $subscription->sessions_count,
                'sessions_completed' => $completedSessions->count(),
                'sessions_remaining' => $subscription->remaining_sessions ?? ($subscription->sessions_count - $completedSessions->count()),
                'completion_percentage' => $subscription->sessions_count > 0
                    ? round(($completedSessions->count() / $subscription->sessions_count) * 100, 1)
                    : 0,
            ],
            'attendance' => $this->calculateSubscriptionAttendance($subscription, $completedSessions),
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
    }

    /**
     * Calculate subscription attendance from session reports.
     *
     * @param  AcademicSubscription  $subscription
     * @param  \Illuminate\Support\Collection  $completedSessions
     */
    protected function calculateSubscriptionAttendance($subscription, $completedSessions): array
    {
        $studentId = $subscription->student_id;
        $sessionIds = $completedSessions->pluck('id');

        $reports = AcademicSessionReport::whereIn('session_id', $sessionIds)
            ->where('student_id', $studentId)
            ->get();

        $total = $reports->count();
        $attended = $this->countAttended($reports);
        $missed = $this->countMissed($reports);

        return [
            'total_scheduled' => $total,
            'attended' => $attended,
            'missed' => $missed,
            'attendance_rate' => $total > 0
                ? round(($attended / $total) * 100, 1)
                : 0,
        ];
    }
}
