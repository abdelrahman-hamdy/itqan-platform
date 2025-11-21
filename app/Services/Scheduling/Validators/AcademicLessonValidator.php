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
        $timezone = AcademyContextService::getTimezone();
        $requestedStart = $startDate ?? Carbon::now($timezone);
        $limits = $this->getSubscriptionLimits();

        // Validate start date
        $validStart = $limits['valid_start_date'];
        $validEnd = $limits['valid_end_date'];

        if ($requestedStart->isBefore($validStart)) {
            return ValidationResult::error(
                "لا يمكن جدولة جلسات قبل تاريخ بدء الاشتراك ({$validStart->format('Y/m/d')})"
            );
        }

        // Allow scheduling from today onwards (actual time validation happens during scheduling)
        $now = Carbon::now($timezone)->startOfDay();
        if ($requestedStart->startOfDay()->lessThan($now)) {
            return ValidationResult::error('لا يمكن جدولة جلسات في الماضي');
        }

        // Calculate end date of scheduling period
        $requestedEnd = $requestedStart->copy()->addWeeks($weeksAhead);

        if ($requestedEnd->isAfter($validEnd)) {
            $daysOver = $requestedEnd->diffInDays($validEnd);
            return ValidationResult::warning(
                "⚠️ بعض الجلسات ستتجاوز تاريخ انتهاء الاشتراك ({$validEnd->format('Y/m/d')}) بـ {$daysOver} يوم. " .
                "قد تحتاج لتقليل عدد الأسابيع أو تجديد الاشتراك.",
                ['overage_days' => $daysOver]
            );
        }

        // Check if subscription is about to expire
        $daysUntilExpiry = now()->diffInDays($validEnd, false);
        if ($daysUntilExpiry < 7 && $daysUntilExpiry > 0) {
            return ValidationResult::warning(
                "⚠️ الاشتراك سينتهي خلال {$daysUntilExpiry} أيام ({$validEnd->format('Y/m/d')}). " .
                "قد ترغب في تجديد الاشتراك قريباً."
            );
        }

        if ($daysUntilExpiry <= 0) {
            return ValidationResult::error(
                "الاشتراك منتهي منذ {$validEnd->format('Y/m/d')}. يرجى تجديد الاشتراك."
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
        if (!$this->subscription || $this->subscription->subscription_status !== 'active') {
            return [
                'status' => 'inactive',
                'message' => 'الاشتراك غير نشط',
                'color' => 'red',
                'can_schedule' => false,
                'urgent' => false,
            ];
        }

        if ($this->subscription->end_date && $this->subscription->end_date->isPast()) {
            return [
                'status' => 'expired',
                'message' => 'انتهى الاشتراك في ' . $this->subscription->end_date->format('Y/m/d'),
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
        // Get total sessions from subscription package
        $totalSessions = $this->subscription->total_sessions ?? 12;

        // Calculate used sessions
        $usedSessions = $this->subscription->sessions()
            ->whereIn('status', ['completed', 'scheduled', 'in_progress'])
            ->count();

        $remainingSessions = max(0, $totalSessions - $usedSessions);

        // Calculate subscription period
        $startDate = $this->subscription->start_date ?? now();
        $endDate = $this->subscription->end_date ?? now()->addMonths(1);

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
}
