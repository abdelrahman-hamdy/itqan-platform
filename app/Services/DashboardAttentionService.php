<?php

namespace App\Services;

use App\Enums\HomeworkStatus;
use App\Enums\HomeworkSubmissionStatus;
use App\Enums\PaymentStatus;
use App\Enums\SessionRequestStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\TrialRequestStatus;
use App\Enums\UserType;
use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\Academy;
use App\Models\CourseReview;
use App\Models\InteractiveCourseHomework;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTrialRequest;
use App\Models\SessionRequest;
use App\Models\TeacherReview;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DashboardAttentionService
{
    /**
     * Get all attention items grouped by severity.
     *
     * @param  int[]  $quranTeacherIds
     * @param  int[]  $academicTeacherProfileIds
     * @return array{groups: array, total_count: int, worst_severity: string, pendingReviews: array}
     */
    public function getAttentionItems(
        int $academyId,
        bool $isAdmin,
        array $quranTeacherIds,
        array $academicTeacherProfileIds,
    ): array {
        $cacheKey = 'dashboard_attention_'.$academyId.'_'.md5(serialize([$quranTeacherIds, $academicTeacherProfileIds]));

        $counts = Cache::remember($cacheKey, 30, function () use ($academyId, $isAdmin, $quranTeacherIds, $academicTeacherProfileIds) {
            return $this->queryCounts($academyId, $isAdmin, $quranTeacherIds, $academicTeacherProfileIds);
        });

        $groups = $this->buildGroups($counts, $isAdmin);
        $totalCount = collect($groups)->sum(fn ($g) => collect($g['items'])->sum('count'));
        $worstSeverity = $this->determineWorstSeverity($groups);

        // Reviews data (not cached — needs to be fresh for inline actions)
        $pendingReviews = $this->getPendingReviewsData($academyId);

        return [
            'groups' => $groups,
            'total_count' => $totalCount,
            'worst_severity' => $worstSeverity,
            'pendingReviews' => $pendingReviews,
        ];
    }

    private function queryCounts(int $academyId, bool $isAdmin, array $quranTeacherIds, array $academicTeacherProfileIds): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $endOfDay = $now->copy()->endOfDay();
        $threeDays = $now->copy()->addDays(3);
        $sevenDays = $now->copy()->addDays(7);

        // === CRITICAL ===

        // 1. Subscriptions expiring within 3 days
        $expiring3d = QuranSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->where('ends_at', '<=', $threeDays)
            ->where('ends_at', '>', $now)->count()
            + AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->where('ends_at', '<=', $threeDays)
                ->where('ends_at', '>', $now)->count();

        // 2. Extended subscriptions (active grace period)
        $extendedSubs = QuranSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereNotNull('metadata->grace_period_ends_at')
            ->whereRaw("JSON_EXTRACT(metadata, '$.grace_period_ends_at') > ?", [$now->toDateTimeString()])
            ->count()
            + AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->whereNotNull('metadata->grace_period_ends_at')
                ->whereRaw("JSON_EXTRACT(metadata, '$.grace_period_ends_at') > ?", [$now->toDateTimeString()])
                ->count();

        // 3. Expired-pending subscriptions (pending > 48h)
        $expiredPending = QuranSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::PENDING->value)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING->value)
            ->where('created_at', '<', $now->copy()->subHours(
                config('subscriptions.pending.expires_after_hours', 48)
            ))->count()
            + AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::PENDING->value)
                ->where('payment_status', SubscriptionPaymentStatus::PENDING->value)
                ->where('created_at', '<', $now->copy()->subHours(
                    config('subscriptions.pending.expires_after_hours', 48)
                ))->count();

        // 4. Failed payments today
        $failedPayments = Payment::where('academy_id', $academyId)
            ->where('status', PaymentStatus::FAILED->value)
            ->whereBetween('created_at', [$today, $endOfDay])->count();

        // 5. Cancelled sessions today (scoped by teacher for supervisors)
        $cancelledSessions = 0;
        if (! empty($quranTeacherIds)) {
            $cancelledSessions += QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('status', SessionStatus::CANCELLED->value)
                ->whereBetween('scheduled_at', [$today, $endOfDay])->count();
        }
        if (! empty($academicTeacherProfileIds)) {
            $cancelledSessions += AcademicSession::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->where('status', SessionStatus::CANCELLED->value)
                ->whereBetween('scheduled_at', [$today, $endOfDay])->count();
        }

        // === WARNING ===

        // 6. Subscriptions expiring in 4-7 days
        $expiring7d = QuranSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->where('ends_at', '>', $threeDays)
            ->where('ends_at', '<=', $sevenDays)->count()
            + AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->where('ends_at', '>', $threeDays)
                ->where('ends_at', '<=', $sevenDays)->count();

        // 7. Pending subscriptions
        $pendingSubs = QuranSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::PENDING->value)->count()
            + AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::PENDING->value)->count();

        // 8. Pending payments
        $pendingPayments = Payment::where('academy_id', $academyId)
            ->where('status', PaymentStatus::PENDING->value)->count();

        // 9. Pending trial requests
        $pendingTrials = QuranTrialRequest::where('academy_id', $academyId)
            ->where('status', TrialRequestStatus::PENDING->value)->count();

        // 10. Homework awaiting grading
        $homeworkGrading = AcademicHomeworkSubmission::where('academy_id', $academyId)
            ->whereIn('submission_status', [
                HomeworkSubmissionStatus::SUBMITTED->value,
                HomeworkSubmissionStatus::LATE->value,
                HomeworkSubmissionStatus::RESUBMITTED->value,
            ])->count()
            + InteractiveCourseHomeworkSubmission::where('academy_id', $academyId)
                ->whereIn('submission_status', [
                    HomeworkSubmissionStatus::SUBMITTED->value,
                    HomeworkSubmissionStatus::LATE->value,
                    HomeworkSubmissionStatus::RESUBMITTED->value,
                ])->count();

        // 11. Overdue homework
        $overdueHomework = AcademicHomework::where('academy_id', $academyId)
            ->where('due_date', '<', $now)
            ->where('status', HomeworkStatus::PUBLISHED->value)->count()
            + InteractiveCourseHomework::where('academy_id', $academyId)
                ->where('due_date', '<', $now)
                ->where('status', HomeworkStatus::PUBLISHED->value)->count();

        // 12. Reviews awaiting approval (conditional on academy settings)
        $pendingReviewsCount = 0;
        $academy = Academy::find($academyId);
        $manualReviews = ! (($academy->academic_settings ?? [])['auto_approve_reviews'] ?? true);
        if ($manualReviews) {
            $pendingReviewsCount = CourseReview::where('academy_id', $academyId)
                ->where('is_approved', false)->count()
                + TeacherReview::where('academy_id', $academyId)
                    ->where('is_approved', false)->count();
        }

        // === INFO ===

        // 13-16. Inactive users
        $inactiveStudents = User::where('academy_id', $academyId)
            ->where('active_status', false)
            ->where('user_type', UserType::STUDENT->value)->count();

        $inactiveQuranTeachers = User::where('academy_id', $academyId)
            ->where('active_status', false)
            ->where('user_type', UserType::QURAN_TEACHER->value)->count();

        $inactiveAcademicTeachers = User::where('academy_id', $academyId)
            ->where('active_status', false)
            ->where('user_type', UserType::ACADEMIC_TEACHER->value)->count();

        $inactiveParents = User::where('academy_id', $academyId)
            ->where('active_status', false)
            ->where('user_type', UserType::PARENT->value)->count();

        // 17. Pending session requests
        $pendingSessionRequests = 0;
        if (Schema::hasTable('session_requests')) {
            $pendingSessionRequests = SessionRequest::where('academy_id', $academyId)
                ->whereIn('status', [
                    SessionRequestStatus::PENDING->value,
                    SessionRequestStatus::AGREED->value,
                ])->count();
        }

        return [
            'expiring_3d' => $expiring3d,
            'extended_subs' => $extendedSubs,
            'expired_pending' => $expiredPending,
            'failed_payments' => $failedPayments,
            'cancelled_sessions' => $cancelledSessions,
            'expiring_7d' => $expiring7d,
            'pending_subs' => $pendingSubs,
            'pending_payments' => $pendingPayments,
            'pending_trials' => $pendingTrials,
            'homework_grading' => $homeworkGrading,
            'overdue_homework' => $overdueHomework,
            'pending_reviews' => $pendingReviewsCount,
            'manual_reviews' => $manualReviews,
            'inactive_students' => $inactiveStudents,
            'inactive_quran_teachers' => $inactiveQuranTeachers,
            'inactive_academic_teachers' => $inactiveAcademicTeachers,
            'inactive_parents' => $inactiveParents,
            'pending_session_requests' => $pendingSessionRequests,
        ];
    }

    private function buildGroups(array $counts, bool $isAdmin): array
    {
        $groups = [];

        // Critical group
        $criticalItems = array_filter([
            $this->makeItem('expiring_subscriptions_3d', $counts['expiring_3d'], 'critical', 'ri-time-line', 'manage.subscriptions.index', ['status' => 'active', 'sort' => 'expiring_soon']),
            $this->makeItem('extended_subscriptions', $counts['extended_subs'], 'critical', 'ri-loop-right-line', 'manage.subscriptions.index', ['status' => 'extended']),
            $this->makeItem('expired_pending_subscriptions', $counts['expired_pending'], 'critical', 'ri-error-warning-line', 'manage.subscriptions.index', ['status' => 'pending', 'sort' => 'oldest']),
            $this->makeItem('failed_payments_today', $counts['failed_payments'], 'critical', 'ri-bank-card-line', 'manage.payments.index', ['status' => 'failed']),
            $this->makeItem('cancelled_sessions_today', $counts['cancelled_sessions'], 'critical', 'ri-close-circle-line', 'manage.sessions.index', ['status' => 'cancelled', 'date' => 'today']),
        ], fn ($item) => $item['count'] > 0);

        if (! empty($criticalItems)) {
            $groups[] = [
                'key' => 'critical',
                'label_key' => 'supervisor.attention.severity_critical',
                'color' => 'red',
                'items' => array_values($criticalItems),
            ];
        }

        // Warning group
        $trialItem = $this->makeItem('pending_trial_requests', $counts['pending_trials'], 'warning', 'ri-flask-line', 'manage.trial-sessions.index', ['status' => 'pending']);
        // Supervisors get a secondary "schedule" button linking to calendar
        if (! $isAdmin) {
            $trialItem['secondary'] = [
                'route' => 'manage.calendar.index',
                'query_params' => [],
                'action_key' => 'supervisor.attention.action_schedule',
            ];
        }

        $warningItems = array_filter([
            $this->makeItem('expiring_subscriptions_7d', $counts['expiring_7d'], 'warning', 'ri-timer-line', 'manage.subscriptions.index', ['status' => 'active', 'sort' => 'expiring_soon']),
            $this->makeItem('pending_subscriptions', $counts['pending_subs'], 'warning', 'ri-bank-card-line', 'manage.subscriptions.index', ['status' => 'pending']),
            $this->makeItem('pending_payments', $counts['pending_payments'], 'warning', 'ri-money-dollar-circle-line', 'manage.payments.index', ['status' => 'pending']),
            $trialItem,
            $this->makeItem('homework_awaiting_grading', $counts['homework_grading'], 'warning', 'ri-draft-line', 'manage.homework.index'),
            $this->makeItem('overdue_homework', $counts['overdue_homework'], 'warning', 'ri-task-line', 'manage.homework.index'),
            $counts['manual_reviews'] ? $this->makeItem('reviews_awaiting_approval', $counts['pending_reviews'], 'warning', 'ri-star-line', null, [], 'review') : null,
        ], fn ($item) => $item !== null && $item['count'] > 0);

        if (! empty($warningItems)) {
            $groups[] = [
                'key' => 'warning',
                'label_key' => 'supervisor.attention.severity_warning',
                'color' => 'amber',
                'items' => array_values($warningItems),
            ];
        }

        // Info group
        $infoItems = array_filter([
            $this->makeItem('inactive_students', $counts['inactive_students'], 'info', 'ri-user-unfollow-line', 'manage.students.index', ['status' => 'inactive']),
            $this->makeItem('inactive_quran_teachers', $counts['inactive_quran_teachers'], 'info', 'ri-book-read-line', 'manage.teachers.index', ['status' => 'inactive', 'type' => 'quran']),
            $this->makeItem('inactive_academic_teachers', $counts['inactive_academic_teachers'], 'info', 'ri-graduation-cap-line', 'manage.teachers.index', ['status' => 'inactive', 'type' => 'academic']),
            $this->makeItem('inactive_parents', $counts['inactive_parents'], 'info', 'ri-parent-line', 'manage.parents.index', ['status' => 'inactive']),
            $this->makeItem('pending_session_requests', $counts['pending_session_requests'], 'info', 'ri-calendar-todo-line', 'manage.sessions.index', ['status' => 'scheduled,ready']),
        ], fn ($item) => $item['count'] > 0);

        if (! empty($infoItems)) {
            $groups[] = [
                'key' => 'info',
                'label_key' => 'supervisor.attention.severity_info',
                'color' => 'blue',
                'items' => array_values($infoItems),
            ];
        }

        return $groups;
    }

    private function makeItem(string $key, int $count, string $severity, string $icon, ?string $route, array $queryParams = [], string $actionKey = 'view'): array
    {
        return [
            'key' => $key,
            'count' => $count,
            'label_key' => 'supervisor.attention.'.$key,
            'severity' => $severity,
            'icon' => $icon,
            'route' => $route,
            'query_params' => $queryParams,
            'action_key' => 'supervisor.attention.action_'.$actionKey,
            'secondary' => null,
        ];
    }

    private function determineWorstSeverity(array $groups): string
    {
        foreach ($groups as $group) {
            if ($group['key'] === 'critical' && collect($group['items'])->sum('count') > 0) {
                return 'critical';
            }
        }
        foreach ($groups as $group) {
            if ($group['key'] === 'warning' && collect($group['items'])->sum('count') > 0) {
                return 'warning';
            }
        }
        foreach ($groups as $group) {
            if ($group['key'] === 'info' && collect($group['items'])->sum('count') > 0) {
                return 'info';
            }
        }

        return 'clear';
    }

    /**
     * Get pending reviews data for inline management panel.
     */
    private function getPendingReviewsData(int $academyId): array
    {
        $academy = Academy::find($academyId);
        $manualReviews = ! (($academy->academic_settings ?? [])['auto_approve_reviews'] ?? true);

        if (! $manualReviews) {
            return ['enabled' => false, 'items' => [], 'total' => 0];
        }

        $courseReviews = CourseReview::where('academy_id', $academyId)
            ->where('is_approved', false)
            ->with(['user', 'reviewable'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'type' => 'course',
                'reviewer_name' => $r->user?->name ?? __('supervisor.attention.unknown'),
                'rating' => $r->rating,
                'comment' => $r->review,
                'target_name' => $r->reviewable?->title ?? '',
                'created_at' => $r->created_at,
            ]);

        $teacherReviews = TeacherReview::where('academy_id', $academyId)
            ->where('is_approved', false)
            ->with(['student', 'reviewable'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'type' => 'teacher',
                'reviewer_name' => $r->student?->name ?? __('supervisor.attention.unknown'),
                'rating' => $r->rating,
                'comment' => $r->comment,
                'target_name' => $r->teacher_name,
                'created_at' => $r->created_at,
            ]);

        $allReviews = $courseReviews->merge($teacherReviews)
            ->sortByDesc('created_at')
            ->take(10)
            ->values()
            ->toArray();

        $totalCount = CourseReview::where('academy_id', $academyId)->where('is_approved', false)->count()
            + TeacherReview::where('academy_id', $academyId)->where('is_approved', false)->count();

        return [
            'enabled' => true,
            'items' => $allReviews,
            'total' => $totalCount,
        ];
    }

    /**
     * Clear attention cache for an academy.
     */
    public function clearCache(int $academyId): void
    {
        // Since the cache key includes teacher IDs, we use a pattern-based approach
        // by clearing all keys that start with the prefix
        $pattern = 'dashboard_attention_'.$academyId.'_*';
        // Redis doesn't support wildcard delete in Cache facade, so we clear by known key
        // The 30-second TTL handles natural expiry; this is for immediate refresh after actions
        Cache::forget('dashboard_attention_'.$academyId.'_'.md5(serialize([[], []])));
    }
}
