<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\Certificate;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\QuranSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Enums\PaymentStatus;

/**
 * Parent Dashboard Service
 *
 * Generate dashboard statistics and widgets for parent portal.
 * Aggregates data from all children for family-wide overview.
 */
class ParentDashboardService
{
    /**
     * Get dashboard data for all children
     *
     * @param ParentProfile $parent
     * @return array
     */
    public function getDashboardData(ParentProfile $parent): array
    {
        $cacheKey = "parent:dashboard:{$parent->id}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($parent) {
            $children = $parent->students()
                ->forAcademy($parent->academy_id)
                ->with(['user'])
                ->get();

            return [
                'children' => $children,
                'stats' => $this->getFamilyStatistics($parent),
                'upcoming_sessions' => $this->getUpcomingSessionsForAllChildren($parent, 7),
                'recent_activity' => $this->getRecentActivity($parent),
            ];
        });
    }

    /**
     * Calculate family statistics (total sessions, payments, etc.)
     *
     * @param ParentProfile $parent
     * @return array
     */
    public function getFamilyStatistics(ParentProfile $parent): array
    {
        $cacheKey = "parent:family_stats:{$parent->id}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($parent) {
            $children = $parent->students()
                ->forAcademy($parent->academy_id)
                ->get();

            $childUserIds = $children->pluck('user_id')->toArray();

            // Total active subscriptions across all children
            $activeSubscriptions = \App\Models\QuranSubscription::whereIn('student_id', $childUserIds)
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->count();

            $activeSubscriptions += \App\Models\AcademicSubscription::whereIn('student_id', $childUserIds)
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->count();

            $activeSubscriptions += \App\Models\CourseSubscription::whereIn('student_id', $childUserIds)
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->count();

            // Upcoming sessions (next 7 days)
            $upcomingSessions = QuranSession::whereIn('student_id', $childUserIds)
                ->where('academy_id', $parent->academy_id)
                ->where('status', SessionStatus::SCHEDULED->value)
                ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
                ->count();

            $upcomingSessions += AcademicSession::whereIn('student_id', $childUserIds)
                ->where('academy_id', $parent->academy_id)
                ->where('status', SessionStatus::SCHEDULED->value)
                ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
                ->count();

            // Total certificates earned
            $totalCertificates = Certificate::whereIn('student_id', $childUserIds)
                ->where('academy_id', $parent->academy_id)
                ->count();

            // Outstanding payments
            $outstandingPayments = Payment::whereIn('user_id', $childUserIds)
                ->where('academy_id', $parent->academy_id)
                ->whereIn('status', [PaymentStatus::PENDING->value, PaymentStatus::PROCESSING->value])
                ->sum('amount');

            return [
                'total_children' => $children->count(),
                'active_subscriptions' => $activeSubscriptions,
                'upcoming_sessions' => $upcomingSessions,
                'total_certificates' => $totalCertificates,
                'outstanding_payments' => $outstandingPayments,
            ];
        });
    }

    /**
     * Get upcoming sessions for all children (next X days)
     *
     * @param ParentProfile $parent
     * @param int $days
     * @return Collection
     */
    public function getUpcomingSessionsForAllChildren(ParentProfile $parent, int $days = 7): Collection
    {
        $cacheKey = "parent:upcoming_sessions:{$parent->id}:{$days}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($parent, $days) {
            $children = $parent->students()
                ->forAcademy($parent->academy_id)
                ->get();

            $childUserIds = $children->pluck('user_id')->toArray();

            // Get Quran sessions
            $quranSessions = QuranSession::whereIn('student_id', $childUserIds)
                ->where('academy_id', $parent->academy_id)
                ->where('status', SessionStatus::SCHEDULED->value)
                ->whereBetween('scheduled_at', [now(), now()->addDays($days)])
                ->with(['quranTeacher', 'student.user', 'individualCircle', 'circle'])
                ->orderBy('scheduled_at', 'asc')
                ->get();

            // Get Academic sessions
            $academicSessions = AcademicSession::whereIn('student_id', $childUserIds)
                ->where('academy_id', $parent->academy_id)
                ->where('status', SessionStatus::SCHEDULED->value)
                ->whereBetween('scheduled_at', [now(), now()->addDays($days)])
                ->with(['academicTeacher', 'student.user', 'academicIndividualLesson'])
                ->orderBy('scheduled_at', 'asc')
                ->get();

            // Merge and sort by scheduled_at
            return $quranSessions->merge($academicSessions)
                ->sortBy('scheduled_at')
                ->values();
        });
    }

    /**
     * Get recent activity across all children
     *
     * @param ParentProfile $parent
     * @param int $limit
     * @return array
     */
    public function getRecentActivity(ParentProfile $parent, int $limit = 10): array
    {
        $children = $parent->students()
            ->forAcademy($parent->academy_id)
            ->get();

        $childUserIds = $children->pluck('user_id')->toArray();

        $activities = collect();

        // Recent completed sessions
        $recentSessions = QuranSession::whereIn('student_id', $childUserIds)
            ->where('academy_id', $parent->academy_id)
            ->where('status', SessionStatus::COMPLETED->value)
            ->with(['student.user'])
            ->orderBy('scheduled_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($session) {
                return [
                    'type' => 'session_completed',
                    'message' => 'أكمل ' . ($session->student?->user?->name ?? $session->student?->name ?? 'الطالب') . ' جلسة قرآن',
                    'timestamp' => $session->scheduled_at,
                    'icon' => 'ri-book-read-line',
                    'color' => 'green',
                ];
            });

        $activities = $activities->merge($recentSessions);

        // Recent academic sessions
        $recentAcademicSessions = AcademicSession::whereIn('student_id', $childUserIds)
            ->where('academy_id', $parent->academy_id)
            ->where('status', SessionStatus::COMPLETED->value)
            ->with(['student.user'])
            ->orderBy('scheduled_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($session) {
                return [
                    'type' => 'session_completed',
                    'message' => 'أكمل ' . ($session->student?->user?->name ?? $session->student?->name ?? 'الطالب') . ' حصة دراسية',
                    'timestamp' => $session->scheduled_at,
                    'icon' => 'ri-book-2-line',
                    'color' => 'blue',
                ];
            });

        $activities = $activities->merge($recentAcademicSessions);

        // Recent certificates - eager load student relationship to avoid N+1
        $recentCertificates = Certificate::whereIn('student_id', $childUserIds)
            ->where('academy_id', $parent->academy_id)
            ->with(['student'])
            ->orderBy('issued_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($certificate) {
                return [
                    'type' => 'certificate_issued',
                    'message' => 'حصل ' . ($certificate->student?->name ?? 'الطالب') . ' على شهادة',
                    'timestamp' => $certificate->issued_at,
                    'icon' => 'ri-award-line',
                    'color' => 'yellow',
                ];
            });

        $activities = $activities->merge($recentCertificates);

        // Recent payments - eager load user relationship to avoid N+1
        $recentPayments = Payment::whereIn('user_id', $childUserIds)
            ->where('academy_id', $parent->academy_id)
            ->where('status', SessionStatus::COMPLETED->value)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'type' => 'payment_completed',
                    'message' => 'تم الدفع لـ ' . ($payment->user?->name ?? 'الطالب') . ' - ' . $payment->amount . ' ' . $payment->currency,
                    'timestamp' => $payment->created_at,
                    'icon' => 'ri-money-dollar-circle-line',
                    'color' => 'green',
                ];
            });

        $activities = $activities->merge($recentPayments);

        // Sort by timestamp descending and limit
        return $activities
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Clear all dashboard caches for a parent.
     */
    public function clearParentCache(int $parentId): void
    {
        Cache::forget("parent:dashboard:{$parentId}");
        Cache::forget("parent:family_stats:{$parentId}");
        Cache::forget("parent:upcoming_sessions:{$parentId}:7");
        Cache::forget("parent:upcoming_sessions:{$parentId}:30");
    }
}
