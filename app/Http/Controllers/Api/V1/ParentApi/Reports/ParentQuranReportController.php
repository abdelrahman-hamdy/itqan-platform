<?php

namespace App\Http\Controllers\Api\V1\ParentApi\Reports;

use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentSessionReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles Quran progress reports for parents.
 *
 * Provides progress tracking, attendance reports, and subscription
 * details for children's Quran learning.
 */
class ParentQuranReportController extends BaseParentReportController
{
    /**
     * Get Quran progress report for children.
     *
     * @param Request $request
     * @param int|null $childId
     * @return JsonResponse
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

            $progress = $this->getQuranProgress($studentUserId);

            $reports[] = [
                'child' => $this->formatChildData($student),
                'quran' => $progress,
            ];
        }

        return $this->success([
            'reports' => $childId ? ($reports[0] ?? null) : $reports,
        ], __('Quran progress report retrieved successfully'));
    }

    /**
     * Get Quran attendance report for children.
     *
     * @param Request $request
     * @param int|null $childId
     * @return JsonResponse
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

            $attendance = $this->getQuranAttendance($studentUserId, $startDate, $endDate);

            $reports[] = [
                'child' => $this->formatChildData($student),
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'quran' => $attendance,
            ];
        }

        return $this->success([
            'reports' => $childId ? ($reports[0] ?? null) : $reports,
        ], __('Quran attendance report retrieved successfully'));
    }

    /**
     * Get Quran subscription report.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
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
            ->map(fn($r) => $this->getStudentUserId($r->student))
            ->filter()
            ->toArray();

        $subscription = QuranSubscription::where('id', $id)
            ->whereIn('student_id', $childUserIds)
            ->with(['quranTeacher.user', 'student.user', 'sessions.reports'])
            ->first();

        if (!$subscription) {
            return $this->notFound(__('Subscription not found.'));
        }

        $report = $this->buildSubscriptionReport($subscription);

        return $this->success([
            'report' => $report,
        ], __('Quran subscription report retrieved successfully'));
    }

    /**
     * Get Quran progress for a student.
     *
     * @param int $studentUserId
     * @return array
     */
    protected function getQuranProgress(int $studentUserId): array
    {
        $subscriptions = QuranSubscription::where('student_id', $studentUserId)->get();
        $activeSubscriptions = $subscriptions->where('status', SubscriptionStatus::ACTIVE->value)->count();

        $completedSessions = QuranSession::where('student_id', $studentUserId)
            ->where('status', SessionStatus::COMPLETED->value)
            ->count();

        $totalSessions = QuranSession::where('student_id', $studentUserId)->count();

        // Get memorization stats from most recent session
        $recentSession = QuranSession::where('student_id', $studentUserId)
            ->where('status', SessionStatus::COMPLETED->value)
            ->orderBy('scheduled_at', 'desc')
            ->first();

        return [
            'active_subscriptions' => $activeSubscriptions,
            'total_subscriptions' => $subscriptions->count(),
            'completed_sessions' => $completedSessions,
            'total_sessions' => $totalSessions,
            'current_surah' => $recentSession?->current_surah ?? null,
            'current_page' => $recentSession?->current_page ?? null,
            'memorized_pages' => $recentSession?->total_memorized_pages ?? null,
        ];
    }

    /**
     * Get Quran attendance stats.
     *
     * @param int $studentUserId
     * @param \Illuminate\Support\Carbon $startDate
     * @param \Illuminate\Support\Carbon $endDate
     * @return array
     */
    protected function getQuranAttendance(int $studentUserId, $startDate, $endDate): array
    {
        $sessions = QuranSession::where('student_id', $studentUserId)
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
     *
     * @param QuranSubscription $subscription
     * @return array
     */
    protected function buildSubscriptionReport(QuranSubscription $subscription): array
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
                'type' => 'quran',
                'name' => $subscription->individualCircle?->name ?? $subscription->circle?->name ?? 'اشتراك قرآني',
                'status' => $subscription->status,
                'start_date' => $subscription->start_date?->toDateString(),
                'end_date' => $subscription->end_date?->toDateString(),
            ],
            'child' => [
                'id' => $subscription->student?->id,
                'name' => $subscription->student?->user?->name ?? $subscription->student?->full_name,
            ],
            'teacher' => $subscription->quranTeacher?->user ? [
                'id' => $subscription->quranTeacher->user->id,
                'name' => $subscription->quranTeacher->user->name,
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
            'recent_sessions' => $completedSessions->sortByDesc('scheduled_at')->take(5)->map(fn($s) => [
                'id' => $s->id,
                'scheduled_at' => $s->scheduled_at?->toISOString(),
                'status' => $s->status->value ?? $s->status,
                'attended' => $s->attended ?? false,
                'rating' => $s->reports?->first()?->new_memorization_degree ?? null,
                'notes' => $s->reports?->first()?->notes ?? null,
            ])->values()->toArray(),
            'upcoming_sessions' => $upcomingSessions->sortBy('scheduled_at')->take(3)->map(fn($s) => [
                'id' => $s->id,
                'scheduled_at' => $s->scheduled_at?->toISOString(),
                'status' => $s->status->value ?? $s->status,
            ])->values()->toArray(),
        ];
    }

    /**
     * Calculate subscription attendance from session reports.
     *
     * @param QuranSubscription $subscription
     * @param \Illuminate\Support\Collection $completedSessions
     * @return array
     */
    protected function calculateSubscriptionAttendance($subscription, $completedSessions): array
    {
        $studentId = $subscription->student_id;
        $sessionIds = $completedSessions->pluck('id');

        $reports = StudentSessionReport::whereIn('session_id', $sessionIds)
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
