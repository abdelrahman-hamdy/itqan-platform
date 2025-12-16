<?php

namespace App\Services\Scheduling\Validators;

use App\Models\AcademicSubscription;
use App\Services\AcademyContextService;
use App\Services\Scheduling\ValidationResult;
use Carbon\Carbon;

/**
 * Validator for Academic Individual Lessons (Subscription-Based)
 * Similar to Individual Quran Circles, validates against subscription limits and expiry
 */
class AcademicLessonValidator implements ScheduleValidatorInterface
{
    public function __construct(
        private AcademicSubscription $subscription
    ) {}

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

        if ($dayCount > $limits['max_per_week']) {
            return ValidationResult::warning(
                "⚠️ اخترت {$dayCount} أيام أسبوعياً، وهو أكثر من الموصى به ({$limits['recommended_per_week']} أيام) " .
                "بناءً على الاشتراك ({$limits['remaining_sessions']} جلسة متبقية خلال {$limits['weeks_remaining']} أسبوع). " .
                "قد تستهلك الجلسات بسرعة كبيرة.",
                [
                    'selected' => $dayCount,
                    'recommended' => $limits['recommended_per_week'],
                    'max' => $limits['max_per_week'],
                    'remaining_sessions' => $limits['remaining_sessions']
                ]
            );
        }

        return ValidationResult::success(
            "✓ عدد الأيام مناسب ({$dayCount} أيام أسبوعياً)",
            ['selected' => $dayCount, 'recommended' => $limits['recommended_per_week']]
        );
    }

    public function validateSessionCount(int $count): ValidationResult
    {
        if ($count <= 0) {
            return ValidationResult::error('يجب أن يكون عدد الجلسات أكبر من صفر');
        }

        $limits = $this->getSubscriptionLimits();
        $remainingSessions = $limits['remaining_sessions'];

        if ($remainingSessions <= 0) {
            return ValidationResult::error(
                'لا توجد جلسات متبقية في الاشتراك الحالي. يرجى تجديد الاشتراك.'
            );
        }

        if ($count > $remainingSessions) {
            return ValidationResult::error(
                "لا يمكن جدولة {$count} جلسة. الجلسات المتبقية في الاشتراك: {$remainingSessions}"
            );
        }

        if ($count > 50) {
            return ValidationResult::error('لا يمكن جدولة أكثر من 50 جلسة دفعة واحدة لتجنب الأخطاء');
        }

        if ($count < $remainingSessions * 0.3) {
            return ValidationResult::warning(
                "⚠️ تجدول {$count} جلسة فقط من أصل {$remainingSessions} متبقية. " .
                "قد تحتاج لجدولة المزيد قريباً قبل انتهاء الاشتراك."
            );
        }

        return ValidationResult::success(
            "✓ سيتم جدولة {$count} من {$remainingSessions} جلسة متبقية",
            ['count' => $count, 'remaining' => $remainingSessions]
        );
    }

    public function validateDateRange(?Carbon $startDate, int $weeksAhead): ValidationResult
    {
        // Use academy timezone for accurate time comparison
        $timezone = AcademyContextService::getTimezone();

        // Create single consistent 'now' reference to avoid microsecond differences
        $now = Carbon::now($timezone)->startOfDay();

        // Parse requested start date or default to today
        $requestedStart = $startDate ? Carbon::parse($startDate)->startOfDay() : $now;

        // Get subscription dates with explicit parsing
        // Use standardized field names (starts_at/ends_at) - same as Quran subscriptions
        $subscriptionStart = $this->subscription->starts_at
            ? Carbon::parse($this->subscription->starts_at)->startOfDay()
            : $now;
        $subscriptionEnd = $this->subscription->ends_at
            ? Carbon::parse($this->subscription->ends_at)->startOfDay()
            : null;

        // Calculate earliest allowed date: max(subscription start, today)
        $earliestAllowed = $subscriptionStart->isAfter($now) ? $subscriptionStart : $now;

        // Validate: requested start >= earliest allowed
        if ($requestedStart->isBefore($earliestAllowed)) {
            return ValidationResult::error(
                "لا يمكن جدولة جلسات قبل تاريخ بدء الاشتراك ({$earliestAllowed->format('Y/m/d')})"
            );
        }

        // Validate: can't schedule in the past
        if ($requestedStart->isBefore($now)) {
            return ValidationResult::error('لا يمكن جدولة جلسات في الماضي');
        }

        // Calculate end date of scheduling period
        $requestedEnd = $requestedStart->copy()->addWeeks($weeksAhead);

        // Validate: requested end doesn't exceed subscription end
        if ($subscriptionEnd && $requestedEnd->isAfter($subscriptionEnd)) {
            return ValidationResult::warning(
                "⚠️ بعض الجلسات قد تتجاوز تاريخ انتهاء الاشتراك ({$subscriptionEnd->format('Y/m/d')}). " .
                "تأكد من توزيع الجلسات بشكل مناسب."
            );
        }

        return ValidationResult::success(
            "✓ نطاق التاريخ صحيح (من {$requestedStart->format('Y/m/d')} إلى {$requestedEnd->format('Y/m/d')})"
        );
    }

    public function validateWeeklyPacing(array $days, int $weeksAhead): ValidationResult
    {
        $daysPerWeek = count($days);
        $totalSessionsToSchedule = $daysPerWeek * $weeksAhead;

        $limits = $this->getSubscriptionLimits();
        $remainingSessions = $limits['remaining_sessions'];
        $recommendedPerWeek = $limits['recommended_per_week'];

        if ($totalSessionsToSchedule > $remainingSessions) {
            return ValidationResult::error(
                "الجدول المختار سينشئ {$totalSessionsToSchedule} جلسة، " .
                "لكن المتبقي في الاشتراك فقط {$remainingSessions} جلسة"
            );
        }

        // Check if pacing is too fast (burnout risk)
        if ($daysPerWeek > $recommendedPerWeek * 2) {
            return ValidationResult::warning(
                "⚠️ معدل {$daysPerWeek} جلسات أسبوعياً أسرع بكثير من الموصى به ({$recommendedPerWeek} جلسات). " .
                "قد يؤدي هذا لاستنفاد الجلسات بسرعة أو إرهاق الطالب."
            );
        }

        // Check if pacing is too slow (subscription may expire before using all sessions)
        if ($daysPerWeek < $recommendedPerWeek * 0.5) {
            $weeksToFinish = ceil($remainingSessions / $daysPerWeek);
            $weeksRemaining = $limits['weeks_remaining'];

            if ($weeksToFinish > $weeksRemaining) {
                return ValidationResult::warning(
                    "⚠️ معدل {$daysPerWeek} جلسات أسبوعياً بطيء جداً. " .
                    "قد لا تستطيع إنهاء {$remainingSessions} جلسة قبل انتهاء الاشتراك خلال {$weeksRemaining} أسبوع."
                );
            }
        }

        return ValidationResult::success(
            "✓ الجدول الزمني مناسب ({$totalSessionsToSchedule} جلسة خلال {$weeksAhead} أسبوع)"
        );
    }

    public function getRecommendations(): array
    {
        $limits = $this->getSubscriptionLimits();

        return [
            'recommended_days' => ceil($limits['recommended_per_week']),
            'max_days' => $limits['max_per_week'],
            'remaining_sessions' => $limits['remaining_sessions'],
            'recommended_per_week' => $limits['recommended_per_week'],
            'weeks_remaining' => $limits['weeks_remaining'],
            'subscription_expires_at' => $limits['valid_end_date']->format('Y-m-d'),
            'reason' => "موصى به {$limits['recommended_per_week']} جلسات أسبوعياً لإكمال " .
                       "{$limits['remaining_sessions']} جلسة متبقية خلال {$limits['weeks_remaining']} أسبوع " .
                       "(قبل انتهاء الاشتراك في {$limits['valid_end_date']->format('Y/m/d')})",
        ];
    }

    public function getSchedulingStatus(): array
    {
        // Check subscription status first
        if (!$this->subscription || $this->subscription->status !== 'active') {
            return [
                'status' => 'inactive',
                'message' => 'الاشتراك غير نشط',
                'color' => 'red',
                'can_schedule' => false,
                'urgent' => false,
            ];
        }

        if ($this->subscription->ends_at && $this->subscription->ends_at->isPast()) {
            return [
                'status' => 'expired',
                'message' => 'انتهى الاشتراك في ' . $this->subscription->ends_at->format('Y/m/d'),
                'color' => 'red',
                'can_schedule' => false,
                'urgent' => false,
            ];
        }

        // Calculate sessions
        $limits = $this->getSubscriptionLimits();
        $remainingSessions = $limits['remaining_sessions'];

        if ($remainingSessions <= 0) {
            return [
                'status' => 'fully_scheduled',
                'message' => 'تم جدولة جميع الجلسات',
                'color' => 'gray',
                'can_schedule' => false,
                'urgent' => false,
            ];
        }

        // Get future scheduled sessions
        $futureScheduled = $this->subscription->sessions()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->count();

        if ($futureScheduled === 0) {
            return [
                'status' => 'not_scheduled',
                'message' => "لا توجد جلسات مجدولة ({$remainingSessions} جلسة متبقية)",
                'color' => 'yellow',
                'can_schedule' => true,
                'urgent' => true,
            ];
        }

        if ($futureScheduled < $remainingSessions * 0.5) {
            return [
                'status' => 'partially_scheduled',
                'message' => "{$futureScheduled} جلسة مجدولة من {$remainingSessions} متبقية",
                'color' => 'blue',
                'can_schedule' => true,
                'urgent' => true,
            ];
        }

        return [
            'status' => 'well_scheduled',
            'message' => "{$futureScheduled} جلسة مجدولة من {$remainingSessions} متبقية",
            'color' => 'green',
            'can_schedule' => true,
            'urgent' => false,
        ];
    }

    /**
     * Calculate subscription scheduling limits
     */
    private function getSubscriptionLimits(): array
    {
        // Get total sessions from subscription with fallback
        $totalSessions = $this->subscription->total_sessions ?? 8;

        // Calculate used sessions
        $usedSessions = $this->subscription->sessions()
            ->whereIn('status', ['completed', 'scheduled', 'in_progress'])
            ->count();

        $remainingSessions = max(0, $totalSessions - $usedSessions);

        // Calculate subscription period
        $startDate = $this->subscription->starts_at ?? now();
        $endDate = $this->subscription->ends_at ?? now()->addMonths(1);

        // Calculate remaining time
        $now = now();
        $validStartDate = $startDate->isAfter($now) ? $startDate : $now;
        $validEndDate = $endDate;

        $daysRemaining = max(1, $validStartDate->diffInDays($validEndDate));
        $weeksRemaining = max(1, ceil($daysRemaining / 7));

        // Calculate recommended pacing
        $recommendedPerWeek = $remainingSessions / $weeksRemaining;
        $maxPerWeek = ceil($recommendedPerWeek * 1.5); // Allow 50% more for flexibility

        return [
            'remaining_sessions' => $remainingSessions,
            'total_sessions' => $totalSessions,
            'used_sessions' => $usedSessions,
            'recommended_per_week' => round($recommendedPerWeek, 1),
            'max_per_week' => $maxPerWeek,
            'valid_start_date' => $validStartDate,
            'valid_end_date' => $validEndDate,
            'days_remaining' => $daysRemaining,
            'weeks_remaining' => $weeksRemaining,
        ];
    }

    /**
     * Get the maximum date that can be scheduled
     * Returns the subscription end date
     */
    public function getMaxScheduleDate(): ?Carbon
    {
        return $this->subscription->ends_at;
    }
}
