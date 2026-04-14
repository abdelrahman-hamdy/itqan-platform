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
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\CourseReview;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseHomework;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\RecordedCourse;
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
        bool $canConfirmStudentEmails = false,
    ): array {
        $cacheKey = $this->buildCacheKey($academyId, $quranTeacherIds, $academicTeacherProfileIds, $canConfirmStudentEmails);

        $counts = Cache::remember($cacheKey, 300, function () use ($academyId, $isAdmin, $quranTeacherIds, $academicTeacherProfileIds, $canConfirmStudentEmails) {
            return $this->queryCounts($academyId, $isAdmin, $quranTeacherIds, $academicTeacherProfileIds, $canConfirmStudentEmails);
        });

        $groups = $this->buildGroups($counts, $isAdmin);
        $totalCount = collect($groups)->sum(fn ($g) => collect($g['items'])->sum('count'));
        $worstSeverity = $this->determineWorstSeverity($groups);

        // Reviews data (not cached — needs to be fresh for inline actions)
        $pendingReviews = $this->getPendingReviewsData($academyId, $isAdmin, $quranTeacherIds, $academicTeacherProfileIds);

        // Unconfirmed students data (not cached — needs to be fresh for inline actions)
        $unconfirmedStudents = $this->getUnconfirmedStudentsData($academyId, $canConfirmStudentEmails, $isAdmin, $counts['unconfirmed_emails']);

        return [
            'groups' => $groups,
            'total_count' => $totalCount,
            'worst_severity' => $worstSeverity,
            'pendingReviews' => $pendingReviews,
            'unconfirmedStudents' => $unconfirmedStudents,
        ];
    }

    private function queryCounts(int $academyId, bool $isAdmin, array $quranTeacherIds, array $academicTeacherProfileIds, bool $canConfirmStudentEmails = false): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $endOfDay = $now->copy()->endOfDay();
        $threeDays = $now->copy()->addDays(3);
        $sevenDays = $now->copy()->addDays(7);

        // Derive additional ID sets for supervisor scoping.
        // QuranTeacherProfile IDs → for QuranTrialRequest.teacher_id, TeacherReview.reviewable_id
        // Academic teacher User IDs → for homework.teacher_id, SessionRequest.teacher_id
        $quranTeacherProfileIds = [];
        $academicTeacherUserIds = [];

        if (! $isAdmin) {
            if (! empty($quranTeacherIds)) {
                $quranTeacherProfileIds = QuranTeacherProfile::whereIn('user_id', $quranTeacherIds)
                    ->pluck('id')->toArray();
            }
            if (! empty($academicTeacherProfileIds)) {
                $academicTeacherUserIds = AcademicTeacherProfile::whereIn('id', $academicTeacherProfileIds)
                    ->pluck('user_id')->toArray();
            }
        }

        // === CRITICAL ===

        // 1. Subscriptions expiring within 3 days
        $expiring3d = QuranSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->where('ends_at', '<=', $threeDays)
            ->where('ends_at', '>', $now)
            ->when(! $isAdmin, fn ($q) => $q->whereIn('quran_teacher_id', $quranTeacherIds ?: [0]))
            ->count()
            + AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->where('ends_at', '<=', $threeDays)
                ->where('ends_at', '>', $now)
                ->when(! $isAdmin, fn ($q) => $q->whereIn('teacher_id', $academicTeacherProfileIds ?: [0]))
                ->count();

        // 2. Extended subscriptions (active grace period)
        $extendedSubs = QuranSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereNotNull('metadata->grace_period_ends_at')
            ->whereRaw("JSON_EXTRACT(metadata, '$.grace_period_ends_at') > ?", [$now->toDateTimeString()])
            ->when(! $isAdmin, fn ($q) => $q->whereIn('quran_teacher_id', $quranTeacherIds ?: [0]))
            ->count()
            + AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->whereNotNull('metadata->grace_period_ends_at')
                ->whereRaw("JSON_EXTRACT(metadata, '$.grace_period_ends_at') > ?", [$now->toDateTimeString()])
                ->when(! $isAdmin, fn ($q) => $q->whereIn('teacher_id', $academicTeacherProfileIds ?: [0]))
                ->count();

        // 3. Expired-pending subscriptions (pending > 48h)
        $expiredPending = QuranSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::PENDING->value)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING->value)
            ->where('created_at', '<', $now->copy()->subHours(
                config('subscriptions.pending.expires_after_hours', 48)
            ))
            ->when(! $isAdmin, fn ($q) => $q->whereIn('quran_teacher_id', $quranTeacherIds ?: [0]))
            ->count()
            + AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::PENDING->value)
                ->where('payment_status', SubscriptionPaymentStatus::PENDING->value)
                ->where('created_at', '<', $now->copy()->subHours(
                    config('subscriptions.pending.expires_after_hours', 48)
                ))
                ->when(! $isAdmin, fn ($q) => $q->whereIn('teacher_id', $academicTeacherProfileIds ?: [0]))
                ->count();

        // 4. Failed payments today (admin-only — payments are polymorphic, impractical to scope by teacher)
        $failedPayments = $isAdmin
            ? Payment::where('academy_id', $academyId)
                ->where('status', PaymentStatus::FAILED->value)
                ->whereBetween('created_at', [$today, $endOfDay])->count()
            : 0;

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
            ->where('ends_at', '<=', $sevenDays)
            ->when(! $isAdmin, fn ($q) => $q->whereIn('quran_teacher_id', $quranTeacherIds ?: [0]))
            ->count()
            + AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->where('ends_at', '>', $threeDays)
                ->where('ends_at', '<=', $sevenDays)
                ->when(! $isAdmin, fn ($q) => $q->whereIn('teacher_id', $academicTeacherProfileIds ?: [0]))
                ->count();

        // 7. Pending subscriptions
        $pendingSubs = QuranSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::PENDING->value)
            ->when(! $isAdmin, fn ($q) => $q->whereIn('quran_teacher_id', $quranTeacherIds ?: [0]))
            ->count()
            + AcademicSubscription::where('academy_id', $academyId)
                ->where('status', SessionSubscriptionStatus::PENDING->value)
                ->when(! $isAdmin, fn ($q) => $q->whereIn('teacher_id', $academicTeacherProfileIds ?: [0]))
                ->count();

        // 8. Pending payments (admin-only)
        $pendingPayments = $isAdmin
            ? Payment::where('academy_id', $academyId)
                ->where('status', PaymentStatus::PENDING->value)->count()
            : 0;

        // 9. Pending trial requests (Quran only — scoped by teacher profile)
        $pendingTrials = QuranTrialRequest::where('academy_id', $academyId)
            ->where('status', TrialRequestStatus::PENDING->value)
            ->when(! $isAdmin, fn ($q) => $q->whereIn('teacher_id', $quranTeacherProfileIds ?: [0]))
            ->count();

        // 10. Homework awaiting grading (scoped by homework teacher)
        $homeworkGrading = AcademicHomeworkSubmission::where('academy_id', $academyId)
            ->whereIn('submission_status', [
                HomeworkSubmissionStatus::SUBMITTED->value,
                HomeworkSubmissionStatus::LATE->value,
                HomeworkSubmissionStatus::RESUBMITTED->value,
            ])
            ->when(! $isAdmin, fn ($q) => $q->whereHas('homework', fn ($hq) => $hq->whereIn('teacher_id', $academicTeacherUserIds ?: [0])))
            ->count()
            + InteractiveCourseHomeworkSubmission::where('academy_id', $academyId)
                ->whereIn('submission_status', [
                    HomeworkSubmissionStatus::SUBMITTED->value,
                    HomeworkSubmissionStatus::LATE->value,
                    HomeworkSubmissionStatus::RESUBMITTED->value,
                ])
                ->when(! $isAdmin, fn ($q) => $q->whereHas('homework', fn ($hq) => $hq->whereIn('teacher_id', $academicTeacherUserIds ?: [0])))
                ->count();

        // 11. Overdue homework (scoped by teacher)
        $overdueHomework = AcademicHomework::where('academy_id', $academyId)
            ->where('due_date', '<', $now)
            ->where('status', HomeworkStatus::PUBLISHED->value)
            ->when(! $isAdmin, fn ($q) => $q->whereIn('teacher_id', $academicTeacherUserIds ?: [0]))
            ->count()
            + InteractiveCourseHomework::where('academy_id', $academyId)
                ->where('due_date', '<', $now)
                ->where('status', HomeworkStatus::PUBLISHED->value)
                ->when(! $isAdmin, fn ($q) => $q->whereIn('teacher_id', $academicTeacherUserIds ?: [0]))
                ->count();

        // 12. Reviews awaiting approval (conditional on academy settings, scoped by teacher)
        $pendingReviewsCount = 0;
        $academy = Cache::remember("academy:{$academyId}", 600, fn () => Academy::find($academyId));
        $manualReviews = ! (($academy->academic_settings ?? [])['auto_approve_reviews'] ?? true);
        if ($manualReviews) {
            $pendingReviewsCount = $this->countPendingReviews(
                $academyId, $isAdmin, $quranTeacherProfileIds, $academicTeacherProfileIds, $academicTeacherUserIds
            );
        }

        // === INFO ===

        // 13-16. Inactive users — batch into 1 query
        $inactiveQuery = User::where('academy_id', $academyId)
            ->where('active_status', false)
            ->whereIn('user_type', [
                UserType::STUDENT->value,
                UserType::QURAN_TEACHER->value,
                UserType::ACADEMIC_TEACHER->value,
                UserType::PARENT->value,
            ]);

        if (! $isAdmin) {
            // Scope teachers to assigned only; students/parents return 0 for supervisors
            $inactiveQuery->where(function ($q) use ($quranTeacherIds, $academicTeacherUserIds) {
                $q->where(function ($q) use ($quranTeacherIds) {
                    $q->where('user_type', UserType::QURAN_TEACHER->value)
                        ->whereIn('id', $quranTeacherIds ?: [0]);
                })->orWhere(function ($q) use ($academicTeacherUserIds) {
                    $q->where('user_type', UserType::ACADEMIC_TEACHER->value)
                        ->whereIn('id', $academicTeacherUserIds ?: [0]);
                });
            });
        }

        $inactiveCounts = $inactiveQuery
            ->selectRaw('user_type, COUNT(*) as total')
            ->groupBy('user_type')
            ->pluck('total', 'user_type');

        $inactiveStudents = $isAdmin ? ($inactiveCounts[UserType::STUDENT->value] ?? 0) : 0;
        $inactiveQuranTeachers = $inactiveCounts[UserType::QURAN_TEACHER->value] ?? 0;
        $inactiveAcademicTeachers = $inactiveCounts[UserType::ACADEMIC_TEACHER->value] ?? 0;
        $inactiveParents = $isAdmin ? ($inactiveCounts[UserType::PARENT->value] ?? 0) : 0;

        // 17. Pending session requests (scoped by teacher)
        $pendingSessionRequests = 0;
        if (Schema::hasTable('session_requests')) {
            $allTeacherUserIds = $isAdmin ? [] : array_unique(array_merge($quranTeacherIds, $academicTeacherUserIds));

            $pendingSessionRequests = SessionRequest::where('academy_id', $academyId)
                ->whereIn('status', [
                    SessionRequestStatus::PENDING->value,
                    SessionRequestStatus::AGREED->value,
                ])
                ->when(! $isAdmin, fn ($q) => $q->whereIn('teacher_id', $allTeacherUserIds ?: [0]))
                ->count();
        }

        // 18. Students with unconfirmed email (academy-wide, permission-gated)
        $unconfirmedEmails = 0;
        if ($canConfirmStudentEmails || $isAdmin) {
            $unconfirmedEmails = User::where('academy_id', $academyId)
                ->where('user_type', UserType::STUDENT->value)
                ->whereNull('email_verified_at')
                ->where('active_status', true)
                ->count();
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
            'unconfirmed_emails' => $unconfirmedEmails,
        ];
    }

    /**
     * Count pending reviews scoped by teacher for supervisors.
     */
    private function countPendingReviews(int $academyId, bool $isAdmin, array $quranTeacherProfileIds, array $academicTeacherProfileIds, array $academicTeacherUserIds): int
    {
        // Teacher reviews: polymorphic reviewable → QuranTeacherProfile or AcademicTeacherProfile
        $teacherReviews = TeacherReview::where('academy_id', $academyId)
            ->where('is_approved', false)
            ->when(! $isAdmin, function ($q) use ($quranTeacherProfileIds, $academicTeacherProfileIds) {
                $q->where(function ($q) use ($quranTeacherProfileIds, $academicTeacherProfileIds) {
                    $q->where(function ($q) use ($quranTeacherProfileIds) {
                        $q->where('reviewable_type', QuranTeacherProfile::class)
                            ->whereIn('reviewable_id', $quranTeacherProfileIds ?: [0]);
                    })->orWhere(function ($q) use ($academicTeacherProfileIds) {
                        $q->where('reviewable_type', AcademicTeacherProfile::class)
                            ->whereIn('reviewable_id', $academicTeacherProfileIds ?: [0]);
                    });
                });
            })->count();

        // Course reviews: polymorphic reviewable → InteractiveCourse or RecordedCourse
        $courseReviews = CourseReview::where('academy_id', $academyId)
            ->where('is_approved', false)
            ->when(! $isAdmin, function ($q) use ($academicTeacherProfileIds) {
                $q->where(function ($q) use ($academicTeacherProfileIds) {
                    $q->whereHasMorph('reviewable', [InteractiveCourse::class], function ($q) use ($academicTeacherProfileIds) {
                        $q->whereIn('assigned_teacher_id', $academicTeacherProfileIds ?: [0]);
                    })->orWhereHasMorph('reviewable', [RecordedCourse::class]);
                });
            })->count();

        return $teacherReviews + $courseReviews;
    }

    private function buildGroups(array $counts, bool $isAdmin): array
    {
        $groups = [];

        // Critical group
        $criticalItems = array_filter([
            $this->makeItem('expiring_subscriptions_3d', $counts['expiring_3d'], 'critical', 'ri-time-line', 'manage.subscriptions.index', ['status' => 'expiring_3d']),
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
            $this->makeItem('expiring_subscriptions_7d', $counts['expiring_7d'], 'warning', 'ri-timer-line', 'manage.subscriptions.index', ['status' => 'expiring_7d']),
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
            $this->makeItem('unconfirmed_student_emails', $counts['unconfirmed_emails'], 'info', 'ri-mail-unread-line', null, [], 'confirm'),
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
     * Get pending reviews data for inline management panel (scoped by teacher for supervisors).
     *
     * @param  int[]  $quranTeacherIds  User IDs of assigned Quran teachers
     * @param  int[]  $academicTeacherProfileIds  Profile IDs of assigned Academic teachers
     */
    private function getPendingReviewsData(int $academyId, bool $isAdmin, array $quranTeacherIds, array $academicTeacherProfileIds): array
    {
        $academy = Cache::remember("academy:{$academyId}", 600, fn () => Academy::find($academyId));
        $manualReviews = ! (($academy->academic_settings ?? [])['auto_approve_reviews'] ?? true);

        if (! $manualReviews) {
            return ['enabled' => false, 'items' => [], 'total' => 0];
        }

        // Derive IDs for scoping (same as queryCounts)
        $quranTeacherProfileIds = [];
        $academicTeacherUserIds = [];
        if (! $isAdmin) {
            if (! empty($quranTeacherIds)) {
                $quranTeacherProfileIds = QuranTeacherProfile::whereIn('user_id', $quranTeacherIds)
                    ->pluck('id')->toArray();
            }
            if (! empty($academicTeacherProfileIds)) {
                $academicTeacherUserIds = AcademicTeacherProfile::whereIn('id', $academicTeacherProfileIds)
                    ->pluck('user_id')->toArray();
            }
        }

        $courseReviews = CourseReview::where('academy_id', $academyId)
            ->where('is_approved', false)
            ->when(! $isAdmin, function ($q) use ($academicTeacherProfileIds) {
                $q->where(function ($q) use ($academicTeacherProfileIds) {
                    $q->whereHasMorph('reviewable', [InteractiveCourse::class], function ($q) use ($academicTeacherProfileIds) {
                        $q->whereIn('assigned_teacher_id', $academicTeacherProfileIds ?: [0]);
                    })->orWhereHasMorph('reviewable', [RecordedCourse::class]);
                });
            })
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
            ->when(! $isAdmin, function ($q) use ($quranTeacherProfileIds, $academicTeacherProfileIds) {
                $q->where(function ($q) use ($quranTeacherProfileIds, $academicTeacherProfileIds) {
                    $q->where(function ($q) use ($quranTeacherProfileIds) {
                        $q->where('reviewable_type', QuranTeacherProfile::class)
                            ->whereIn('reviewable_id', $quranTeacherProfileIds ?: [0]);
                    })->orWhere(function ($q) use ($academicTeacherProfileIds) {
                        $q->where('reviewable_type', AcademicTeacherProfile::class)
                            ->whereIn('reviewable_id', $academicTeacherProfileIds ?: [0]);
                    });
                });
            })
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

        $allReviews = collect($courseReviews)->merge($teacherReviews)
            ->sortByDesc('created_at')
            ->take(10)
            ->values()
            ->toArray();

        $totalCount = $this->countPendingReviews(
            $academyId, $isAdmin, $quranTeacherProfileIds, $academicTeacherProfileIds, $academicTeacherUserIds
        );

        return [
            'enabled' => true,
            'items' => $allReviews,
            'total' => $totalCount,
        ];
    }

    /**
     * Get unconfirmed students data for inline management panel.
     */
    private function getUnconfirmedStudentsData(int $academyId, bool $canConfirmStudentEmails, bool $isAdmin, int $cachedTotal = 0): array
    {
        if (! $canConfirmStudentEmails && ! $isAdmin) {
            return ['enabled' => false, 'items' => [], 'total' => 0];
        }

        $students = User::where('academy_id', $academyId)
            ->where('user_type', UserType::STUDENT->value)
            ->whereNull('email_verified_at')
            ->where('active_status', true)
            ->select(['id', 'first_name', 'last_name', 'email', 'created_at'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'registered_at' => $u->created_at,
            ]);

        return [
            'enabled' => true,
            'items' => $students->toArray(),
            'total' => $cachedTotal,
        ];
    }

    /**
     * Forget the attention cache for specific parameters.
     */
    public function forgetCacheFor(int $academyId, array $quranTeacherIds, array $academicTeacherProfileIds, bool $canConfirmStudentEmails = false): void
    {
        $cacheKey = $this->buildCacheKey($academyId, $quranTeacherIds, $academicTeacherProfileIds, $canConfirmStudentEmails);
        Cache::forget($cacheKey);
    }

    private function buildCacheKey(int $academyId, array $quranTeacherIds, array $academicTeacherProfileIds, bool $canConfirmStudentEmails): string
    {
        return 'dashboard_attention_'.$academyId.'_'.md5(serialize([$quranTeacherIds, $academicTeacherProfileIds, $canConfirmStudentEmails]));
    }
}
