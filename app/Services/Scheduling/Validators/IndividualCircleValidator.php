<?php

namespace App\Services\Scheduling\Validators;

use App\Models\QuranIndividualCircle;
use App\Services\AcademyContextService;
use App\Services\Scheduling\ValidationResult;
use App\Services\SessionManagementService;
use Carbon\Carbon;

/**
 * Validator for Individual Quran Circles (Subscription-based)
 */
class IndividualCircleValidator implements ScheduleValidatorInterface
{
    private SessionManagementService $sessionService;

    public function __construct(
        private QuranIndividualCircle $circle
    ) {
        $this->sessionService = app(SessionManagementService::class);
    }

    public function validateDaySelection(array $days): ValidationResult
    {
        $dayCount = count($days);

        if ($dayCount === 0) {
            return ValidationResult::error('يجب اختيار يوم واحد على الأقل');
        }

        if ($dayCount > 7) {
            return ValidationResult::error('لا يمكن اختيار أكثر من 7 أيام في الأسبوع');
        }

        $limits = $this->getSubscriptionLimits();

        if ($limits['remaining_sessions'] <= 0) {
            return ValidationResult::error('لا توجد جلسات متبقية في الاشتراك');
        }

        $recommendedPerWeek = $limits['recommended_per_week'];
        $maxPerWeek = $limits['max_per_week'];

        if ($dayCount > $maxPerWeek) {
            return ValidationResult::warning(
                "اخترت {$dayCount} أيام أسبوعياً، وهو أكثر من الموصى به ({$recommendedPerWeek} أيام). قد يؤدي هذا لإنهاء الجلسات بسرعة كبيرة.",
                ['selected' => $dayCount, 'recommended' => $recommendedPerWeek, 'max' => $maxPerWeek]
            );
        }

        return ValidationResult::success(
            "✓ عدد الأيام مناسب ({$dayCount} أيام أسبوعياً)",
            ['selected' => $dayCount, 'recommended' => $recommendedPerWeek]
        );
    }

    public function validateSessionCount(int $count): ValidationResult
    {
        $limits = $this->getSubscriptionLimits();
        $remaining = $limits['remaining_sessions'];

        if ($count <= 0) {
            return ValidationResult::error('يجب أن يكون عدد الجلسات أكبر من صفر');
        }

        if ($count > $remaining) {
            return ValidationResult::error(
                "لا يمكن جدولة {$count} جلسة. الجلسات المتبقية: {$remaining} فقط",
                ['requested' => $count, 'remaining' => $remaining]
            );
        }

        if ($count > 100) {
            return ValidationResult::error('لا يمكن جدولة أكثر من 100 جلسة دفعة واحدة');
        }

        return ValidationResult::success(
            "✓ عدد الجلسات مناسب ({$count} من أصل {$remaining} متبقية)"
        );
    }

    public function validateDateRange(?Carbon $startDate, int $weeksAhead): ValidationResult
    {
        $limits = $this->getSubscriptionLimits();
        $subscription = $this->circle->subscription;

        if (!$subscription) {
            return ValidationResult::error('لا يوجد اشتراك نشط لهذه الحلقة');
        }

        if ($subscription->status !== 'active') {
            return ValidationResult::error('الاشتراك غير نشط. يجب تفعيل الاشتراك أولاً');
        }

        $validStart = $limits['valid_start_date'];
        $validEnd = $limits['valid_end_date'];

        $timezone = AcademyContextService::getTimezone();
        $requestedStart = $startDate ?? Carbon::now($timezone);
        $requestedEnd = $requestedStart->copy()->addWeeks($weeksAhead);

        if ($requestedStart->isBefore($validStart)) {
            return ValidationResult::error(
                "لا يمكن جدولة جلسات قبل تاريخ بدء الاشتراك ({$validStart->format('Y/m/d')})"
            );
        }

        // Only check expiry if subscription has an end date
        if ($validEnd !== null && $requestedEnd->isAfter($validEnd)) {
            return ValidationResult::warning(
                "⚠️ بعض الجلسات ستتجاوز تاريخ انتهاء الاشتراك ({$validEnd->format('Y/m/d')}). سيتم جدولة الجلسات حتى تاريخ الانتهاء فقط.",
                ['subscription_end' => $validEnd->format('Y/m/d')]
            );
        }

        $endDateText = $validEnd ? $validEnd->format('Y/m/d') : 'غير محدد';
        return ValidationResult::success(
            "✓ نطاق التاريخ صحيح (من {$requestedStart->format('Y/m/d')} إلى {$requestedEnd->format('Y/m/d')})"
        );
    }

    public function validateWeeklyPacing(array $days, int $weeksAhead): ValidationResult
    {
        $limits = $this->getSubscriptionLimits();
        $daysPerWeek = count($days);
        $totalSessionsToSchedule = $daysPerWeek * $weeksAhead;
        $remaining = $limits['remaining_sessions'];

        if ($totalSessionsToSchedule > $remaining) {
            $maxWeeks = floor($remaining / $daysPerWeek);
            return ValidationResult::warning(
                "⚠️ اخترت {$daysPerWeek} أيام لمدة {$weeksAhead} أسابيع ({$totalSessionsToSchedule} جلسة)، لكن لديك {$remaining} جلسة متبقية فقط. سيتم جدولة {$remaining} جلسة وتوزيعها على الأيام المختارة.",
                [
                    'total_requested' => $totalSessionsToSchedule,
                    'remaining' => $remaining,
                    'max_weeks' => $maxWeeks,
                    'will_schedule' => $remaining
                ]
            );
        }

        $recommendedPerWeek = $limits['recommended_per_week'];

        if ($daysPerWeek > $recommendedPerWeek * 2) {
            return ValidationResult::warning(
                "⚠️ اخترت {$daysPerWeek} أيام أسبوعياً، وهو ضعف الموصى به ({$recommendedPerWeek}). قد يكون هذا كثيراً على الطالب."
            );
        }

        return ValidationResult::success("✓ الجدول الزمني مناسب");
    }

    public function getRecommendations(): array
    {
        $limits = $this->getSubscriptionLimits();

        return [
            'recommended_days' => (int) round($limits['recommended_per_week']),
            'max_days' => $limits['max_per_week'],
            'remaining_sessions' => $limits['remaining_sessions'],
            'weeks_remaining' => $limits['weeks_remaining'],
            'reason' => "موصى به {$limits['recommended_per_week']} أيام أسبوعياً لتوزيع {$limits['remaining_sessions']} جلسة على {$limits['weeks_remaining']} أسبوع",
        ];
    }

    public function getSchedulingStatus(): array
    {
        $subscription = $this->circle->subscription;

        if (!$subscription || $subscription->status !== 'active') {
            return [
                'status' => 'inactive',
                'message' => 'الاشتراك غير نشط',
                'color' => 'gray',
                'can_schedule' => false,
            ];
        }

        if ($subscription->expires_at && $subscription->expires_at->isPast()) {
            return [
                'status' => 'expired',
                'message' => 'الاشتراك منتهي',
                'color' => 'red',
                'can_schedule' => false,
            ];
        }

        $totalSessions = $subscription->total_sessions;
        $scheduledSessions = $this->circle->sessions()
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->count();
        $remaining = $totalSessions - $scheduledSessions;

        if ($remaining <= 0) {
            return [
                'status' => 'fully_scheduled',
                'message' => 'جميع الجلسات مجدولة',
                'color' => 'green',
                'can_schedule' => false,
            ];
        }

        $timezone = AcademyContextService::getTimezone();
        $futureScheduled = $this->circle->sessions()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', Carbon::now($timezone))
            ->count();

        if ($futureScheduled === 0) {
            return [
                'status' => 'not_scheduled',
                'message' => "لم يتم جدولة أي جلسات ({$remaining} متبقية)",
                'color' => 'yellow',
                'can_schedule' => true,
            ];
        } else {
            return [
                'status' => 'partially_scheduled',
                'message' => "{$futureScheduled} جلسة مجدولة، {$remaining} متبقية",
                'color' => 'blue',
                'can_schedule' => true,
            ];
        }
    }

    /**
     * Calculate subscription limits and recommendations
     */
    private function getSubscriptionLimits(): array
    {
        $subscription = $this->circle->subscription;

        $timezone = AcademyContextService::getTimezone();

        // CRITICAL FIX: If subscription is null (deleted/not found), use circle's total_sessions directly
        // This happens when subscription is soft-deleted but circle still has valid total_sessions
        if (!$subscription) {
            // Calculate remaining based on circle's total_sessions field
            $totalSessions = $this->circle->total_sessions ?? 0;
            $usedSessions = $this->circle->sessions()
                ->whereIn('status', ['completed', 'scheduled', 'in_progress'])
                ->count();
            $remainingSessions = max(0, $totalSessions - $usedSessions);

            // Default scheduling window of 12 weeks (~3 months)
            $weeksRemaining = $remainingSessions > 0 ? 12 : 0;
            $recommendedPerWeek = $weeksRemaining > 0 ? $remainingSessions / $weeksRemaining : 0;

            return [
                'remaining_sessions' => $remainingSessions,
                'recommended_per_week' => round($recommendedPerWeek, 1),
                'max_per_week' => $remainingSessions > 0 ? ceil($recommendedPerWeek * 1.5) : 0,
                'valid_start_date' => Carbon::now($timezone),
                'valid_end_date' => null, // No end date when subscription missing
                'weeks_remaining' => $weeksRemaining,
            ];
        }

        // Use SessionManagementService for accurate count
        $remainingSessions = $this->sessionService->getRemainingIndividualSessions($this->circle);

        // Calculate subscription period
        $now = Carbon::now($timezone);
        $startDate = max($subscription->starts_at, $now);

        // CRITICAL: Calculate end date based on billing cycle
        // NOTE: expires_at field was removed from subscriptions table
        // Subscriptions are now managed by billing_cycle + starts_at
        $endDate = null;
        if ($subscription->starts_at && $subscription->billing_cycle) {
            $endDate = match ($subscription->billing_cycle) {
                'weekly' => $subscription->starts_at->copy()->addWeek(),
                'monthly' => $subscription->starts_at->copy()->addMonth(),
                'quarterly' => $subscription->starts_at->copy()->addMonths(3),
                'yearly' => $subscription->starts_at->copy()->addYear(),
                default => null,
            };
        }

        // Calculate weeks remaining until billing period end
        if ($endDate === null) {
            // For subscriptions without billing cycle, assume a reasonable scheduling window
            $weeksRemaining = 52; // 1 year
        } else {
            $daysRemaining = max(1, $startDate->diffInDays($endDate, false));
            $weeksRemaining = max(1, ceil($daysRemaining / 7));
        }

        // Calculate recommended pacing
        $recommendedPerWeek = $remainingSessions / $weeksRemaining;
        $maxPerWeek = ceil($recommendedPerWeek * 1.5); // Allow 50% flexibility

        return [
            'remaining_sessions' => $remainingSessions,
            'recommended_per_week' => round($recommendedPerWeek, 1),
            'max_per_week' => $maxPerWeek,
            'valid_start_date' => $startDate,
            'valid_end_date' => $endDate, // Based on billing cycle
            'weeks_remaining' => $weeksRemaining,
        ];
    }

    /**
     * Get the maximum date that can be scheduled
     * Returns the subscription end date based on billing cycle
     */
    public function getMaxScheduleDate(): ?Carbon
    {
        $subscription = $this->circle->individualSubscription;
        if (!$subscription || !$subscription->starts_at || !$subscription->billing_cycle) {
            return null;
        }

        return match ($subscription->billing_cycle) {
            'weekly' => $subscription->starts_at->copy()->addWeek(),
            'monthly' => $subscription->starts_at->copy()->addMonth(),
            'quarterly' => $subscription->starts_at->copy()->addMonths(3),
            'yearly' => $subscription->starts_at->copy()->addYear(),
            default => null,
        };
    }
}
