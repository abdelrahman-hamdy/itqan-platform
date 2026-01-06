<?php

namespace App\Services\Subscription;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use Illuminate\Database\Eloquent\Collection;

/**
 * RenewalStatisticsService
 *
 * Provides reporting and statistics for subscription renewals.
 *
 * RESPONSIBILITIES:
 * - Calculating renewal statistics for reporting
 * - Tracking successful and failed renewals
 * - Generating revenue reports from renewals
 * - Forecasting upcoming renewals
 *
 * STATISTICS PROVIDED:
 * - Total renewals (successful + failed)
 * - Success/failure rates
 * - Revenue generated from renewals
 * - Upcoming renewals in next 7 days
 * - Breakdown by subscription type (Quran/Academic)
 */
class RenewalStatisticsService
{
    /**
     * Get all subscriptions due for renewal
     */
    public function getDueForRenewal(): Collection
    {
        $quranSubscriptions = QuranSubscription::where('auto_renew', true)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('next_billing_date', '<=', now()->addDays(1))
            ->get();

        $academicSubscriptions = AcademicSubscription::where('auto_renew', true)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('next_billing_date', '<=', now()->addDays(1))
            ->get();

        return $quranSubscriptions->concat($academicSubscriptions);
    }

    /**
     * Get subscriptions that failed renewal
     */
    public function getFailedRenewals(int $academyId, int $days = 30): Collection
    {
        $since = now()->subDays($days);

        $quranSubscriptions = QuranSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::CANCELLED)
            ->where('payment_status', SubscriptionPaymentStatus::FAILED)
            ->where('updated_at', '>=', $since)
            ->get();

        $academicSubscriptions = AcademicSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::CANCELLED)
            ->where('payment_status', SubscriptionPaymentStatus::FAILED)
            ->where('updated_at', '>=', $since)
            ->get();

        $combined = $quranSubscriptions->concat($academicSubscriptions);
        $sorted = $combined->sortByDesc('updated_at')->values();

        return new Collection($sorted->all());
    }

    /**
     * Get renewal statistics for reporting
     */
    public function getRenewalStatistics(int $academyId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $stats = [
            'period_days' => $days,
            'total_renewals' => 0,
            'successful_renewals' => 0,
            'failed_renewals' => 0,
            'total_revenue' => 0,
            'upcoming_renewals' => 0,
            'by_type' => [],
        ];

        foreach (['quran' => QuranSubscription::class, 'academic' => AcademicSubscription::class] as $type => $modelClass) {
            $successful = $modelClass::where('academy_id', $academyId)
                ->where('last_payment_date', '>=', $since)
                ->where('payment_status', SubscriptionPaymentStatus::PAID)
                ->count();

            $failed = $modelClass::where('academy_id', $academyId)
                ->where('updated_at', '>=', $since)
                ->where('status', SessionSubscriptionStatus::CANCELLED)
                ->where('payment_status', SubscriptionPaymentStatus::FAILED)
                ->count();

            $revenue = $modelClass::where('academy_id', $academyId)
                ->where('last_payment_date', '>=', $since)
                ->where('payment_status', SubscriptionPaymentStatus::PAID)
                ->sum('final_price') ?? 0;

            $upcoming = $modelClass::where('academy_id', $academyId)
                ->where('auto_renew', true)
                ->where('status', SessionSubscriptionStatus::ACTIVE)
                ->whereBetween('next_billing_date', [now(), now()->addDays(7)])
                ->count();

            $stats['by_type'][$type] = [
                'successful' => $successful,
                'failed' => $failed,
                'revenue' => $revenue,
                'upcoming' => $upcoming,
            ];

            $stats['successful_renewals'] += $successful;
            $stats['failed_renewals'] += $failed;
            $stats['total_revenue'] += $revenue;
            $stats['upcoming_renewals'] += $upcoming;
        }

        $stats['total_renewals'] = $stats['successful_renewals'] + $stats['failed_renewals'];

        return $stats;
    }

    /**
     * Get upcoming renewals for a specific academy
     */
    public function getUpcomingRenewals(int $academyId, int $daysAhead = 7): Collection
    {
        $endDate = now()->addDays($daysAhead);

        $quranSubscriptions = QuranSubscription::where('academy_id', $academyId)
            ->where('auto_renew', true)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->whereBetween('next_billing_date', [now(), $endDate])
            ->with(['student', 'student.user'])
            ->get();

        $academicSubscriptions = AcademicSubscription::where('academy_id', $academyId)
            ->where('auto_renew', true)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->whereBetween('next_billing_date', [now(), $endDate])
            ->with(['student', 'student.user'])
            ->get();

        return $quranSubscriptions->concat($academicSubscriptions)
            ->sortBy('next_billing_date')
            ->values();
    }

    /**
     * Get renewal success rate for an academy
     */
    public function getRenewalSuccessRate(int $academyId, int $days = 30): float
    {
        $stats = $this->getRenewalStatistics($academyId, $days);

        if ($stats['total_renewals'] === 0) {
            return 0.0;
        }

        return round(($stats['successful_renewals'] / $stats['total_renewals']) * 100, 2);
    }
}
