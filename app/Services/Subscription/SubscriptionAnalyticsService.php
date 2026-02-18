<?php

namespace App\Services\Subscription;

use App\Enums\EnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;

/**
 * Provides subscription statistics and analytics.
 *
 * Extracted from SubscriptionService to isolate analytics/reporting logic.
 */
class SubscriptionAnalyticsService
{
    /**
     * Subscription type constants (mirrors SubscriptionService)
     */
    public const TYPE_QURAN = 'quran';

    public const TYPE_ACADEMIC = 'academic';

    public const TYPE_COURSE = 'course';

    /**
     * Get subscription statistics for an academy
     */
    public function getAcademyStatistics(int $academyId): array
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'pending' => 0,
            'paused' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'revenue' => 0,
            'by_type' => [],
        ];

        // Session-based subscriptions (Quran & Academic)
        foreach ([self::TYPE_QURAN, self::TYPE_ACADEMIC] as $type) {
            $modelClass = $this->getModelClass($type);

            $typeStats = [
                'total' => $modelClass::where('academy_id', $academyId)->count(),
                'active' => $modelClass::where('academy_id', $academyId)
                    ->where('status', SessionSubscriptionStatus::ACTIVE)->count(),
                'pending' => $modelClass::where('academy_id', $academyId)
                    ->where('status', SessionSubscriptionStatus::PENDING)->count(),
                'paused' => $modelClass::where('academy_id', $academyId)
                    ->where('status', SessionSubscriptionStatus::PAUSED)->count(),
                'cancelled' => $modelClass::where('academy_id', $academyId)
                    ->where('status', SessionSubscriptionStatus::CANCELLED)->count(),
                'completed' => 0, // Session-based subscriptions don't have completed status
                'revenue' => $modelClass::where('academy_id', $academyId)
                    ->where('payment_status', SubscriptionPaymentStatus::PAID)
                    ->sum('final_price') ?? 0,
            ];

            $stats['by_type'][$type] = $typeStats;
            $stats['total'] += $typeStats['total'];
            $stats['active'] += $typeStats['active'];
            $stats['pending'] += $typeStats['pending'];
            $stats['paused'] += $typeStats['paused'];
            $stats['cancelled'] += $typeStats['cancelled'];
            $stats['revenue'] += $typeStats['revenue'];
        }

        // Course subscriptions (use EnrollmentStatus)
        $courseStats = [
            'total' => CourseSubscription::where('academy_id', $academyId)->count(),
            'active' => CourseSubscription::where('academy_id', $academyId)
                ->where('status', EnrollmentStatus::ENROLLED)->count(),
            'pending' => CourseSubscription::where('academy_id', $academyId)
                ->where('status', EnrollmentStatus::PENDING)->count(),
            'paused' => 0, // Courses don't have paused status
            'cancelled' => CourseSubscription::where('academy_id', $academyId)
                ->where('status', EnrollmentStatus::CANCELLED)->count(),
            'completed' => CourseSubscription::where('academy_id', $academyId)
                ->where('status', EnrollmentStatus::COMPLETED)->count(),
            'revenue' => CourseSubscription::where('academy_id', $academyId)
                ->where('payment_status', SubscriptionPaymentStatus::PAID)
                ->sum('final_price') ?? 0,
        ];

        $stats['by_type'][self::TYPE_COURSE] = $courseStats;
        $stats['total'] += $courseStats['total'];
        $stats['active'] += $courseStats['active'];
        $stats['pending'] += $courseStats['pending'];
        $stats['cancelled'] += $courseStats['cancelled'];
        $stats['completed'] += $courseStats['completed'];
        $stats['revenue'] += $courseStats['revenue'];

        return $stats;
    }

    /**
     * Get student subscription statistics
     */
    public function getStudentStatistics(int $studentId): array
    {
        // Fetch subscriptions directly to avoid a circular dependency on SubscriptionQueryService
        $subscriptions = collect();
        $subscriptions = $subscriptions->merge(QuranSubscription::where('student_id', $studentId)->get());
        $subscriptions = $subscriptions->merge(AcademicSubscription::where('student_id', $studentId)->get());
        $subscriptions = $subscriptions->merge(CourseSubscription::where('student_id', $studentId)->get());

        return [
            'total' => $subscriptions->count(),
            'active' => $subscriptions->filter(fn ($s) => $s->isActive())->count(),
            'completed' => $subscriptions->filter(fn ($s) => method_exists($s, 'isCompleted') && $s->isCompleted())->count(),
            'total_spent' => $subscriptions
                ->where('payment_status', SubscriptionPaymentStatus::PAID)
                ->sum('final_price'),
            'by_type' => [
                self::TYPE_QURAN => $subscriptions->filter(
                    fn ($s) => $s instanceof QuranSubscription
                )->count(),
                self::TYPE_ACADEMIC => $subscriptions->filter(
                    fn ($s) => $s instanceof AcademicSubscription
                )->count(),
                self::TYPE_COURSE => $subscriptions->filter(
                    fn ($s) => $s instanceof CourseSubscription
                )->count(),
            ],
        ];
    }

    /**
     * Get pending subscriptions count by type.
     *
     * Useful for Filament dashboard widgets.
     *
     * @param  int|null  $academyId  Optional academy filter
     */
    public function getPendingSubscriptionsStats(?int $academyId = null): array
    {
        $stats = [];

        // Quran
        $quranQuery = QuranSubscription::where('status', SessionSubscriptionStatus::PENDING)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING);
        if ($academyId) {
            $quranQuery->where('academy_id', $academyId);
        }
        $stats[self::TYPE_QURAN] = [
            'total' => $quranQuery->count(),
            'expired' => (clone $quranQuery)->where('created_at', '<', now()->subHours(
                config('subscriptions.pending.expires_after_hours', 48)
            ))->count(),
        ];

        // Academic
        $academicQuery = AcademicSubscription::where('status', SessionSubscriptionStatus::PENDING)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING);
        if ($academyId) {
            $academicQuery->where('academy_id', $academyId);
        }
        $stats[self::TYPE_ACADEMIC] = [
            'total' => $academicQuery->count(),
            'expired' => (clone $academicQuery)->where('created_at', '<', now()->subHours(
                config('subscriptions.pending.expires_after_hours', 48)
            ))->count(),
        ];

        // Course
        $courseQuery = CourseSubscription::where('status', EnrollmentStatus::PENDING)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING);
        if ($academyId) {
            $courseQuery->where('academy_id', $academyId);
        }
        $stats[self::TYPE_COURSE] = [
            'total' => $courseQuery->count(),
            'expired' => (clone $courseQuery)->where('created_at', '<', now()->subHours(
                config('subscriptions.pending.expires_after_hours', 48)
            ))->count(),
        ];

        return $stats;
    }

    /**
     * Get the model class for a subscription type
     */
    private function getModelClass(string $type): string
    {
        return match ($type) {
            self::TYPE_QURAN => QuranSubscription::class,
            self::TYPE_ACADEMIC => AcademicSubscription::class,
            self::TYPE_COURSE => CourseSubscription::class,
            default => throw new \InvalidArgumentException("Unknown subscription type: {$type}"),
        };
    }
}
