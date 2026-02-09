<?php

namespace App\Services\Scheduling\Validators;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
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
            return ValidationResult::error(__('scheduling.days.select_at_least_one'));
        }

        if ($dayCount > 7) {
            return ValidationResult::error(__('scheduling.days.max_per_week', ['max' => 7]));
        }

        $limits = $this->getSubscriptionLimits();

        if ($dayCount > $limits['max_per_week']) {
            return ValidationResult::warning(
                __('scheduling.days.exceeds_recommended', [
                    'selected' => $dayCount,
                    'recommended' => $limits['recommended_per_week'],
                    'context' => __('scheduling.days.context_subscription', [
                        'remaining' => $limits['remaining_sessions'],
                        'weeks' => $limits['weeks_remaining'],
                    ]),
                    'consequence' => __('scheduling.days.consequence_fast_consumption'),
                ]),
                [
                    'selected' => $dayCount,
                    'recommended' => $limits['recommended_per_week'],
                    'max' => $limits['max_per_week'],
                    'remaining_sessions' => $limits['remaining_sessions'],
                ]
            );
        }

        return ValidationResult::success(
            __('scheduling.days.count_suitable', ['count' => $dayCount]),
            ['selected' => $dayCount, 'recommended' => $limits['recommended_per_week']]
        );
    }

    public function validateSessionCount(int $count): ValidationResult
    {
        if ($count <= 0) {
            return ValidationResult::error(__('scheduling.count.must_be_positive'));
        }

        $limits = $this->getSubscriptionLimits();
        $remainingSessions = $limits['remaining_sessions'];

        if ($remainingSessions <= 0) {
            return ValidationResult::error(
                __('scheduling.count.no_remaining')
            );
        }

        if ($count > $remainingSessions) {
            return ValidationResult::error(
                __('scheduling.count.exceeds_remaining', ['count' => $count, 'remaining' => $remainingSessions])
            );
        }

        if ($count > 50) {
            return ValidationResult::error(__('scheduling.count.max_batch', ['max' => 50]));
        }

        if ($count < $remainingSessions * 0.3) {
            return ValidationResult::warning(
                __('scheduling.count.few_scheduled_warning', ['count' => $count, 'remaining' => $remainingSessions])
            );
        }

        return ValidationResult::success(
            __('scheduling.count.success', ['count' => $count, 'remaining' => $remainingSessions]),
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
                __('scheduling.date.before_subscription_start', ['date' => $earliestAllowed->format('Y/m/d')])
            );
        }

        // Validate: can't schedule in the past
        if ($requestedStart->isBefore($now)) {
            return ValidationResult::error(__('scheduling.date.cannot_schedule_past'));
        }

        // Calculate end date of scheduling period
        $requestedEnd = $requestedStart->copy()->addWeeks($weeksAhead);

        // Validate: requested end doesn't exceed subscription end
        if ($subscriptionEnd && $requestedEnd->isAfter($subscriptionEnd)) {
            return ValidationResult::warning(
                __('scheduling.date.exceeds_subscription_end', ['date' => $subscriptionEnd->format('Y/m/d')])
            );
        }

        return ValidationResult::success(
            __('scheduling.date.range_valid', ['start' => $requestedStart->format('Y/m/d'), 'end' => $requestedEnd->format('Y/m/d')])
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
                __('scheduling.pacing.exceeds_remaining', ['total' => $totalSessionsToSchedule, 'remaining' => $remainingSessions])
            );
        }

        // Check if pacing is too fast (burnout risk)
        if ($daysPerWeek > $recommendedPerWeek * 2) {
            return ValidationResult::warning(
                __('scheduling.pacing.too_fast', ['count' => $daysPerWeek, 'recommended' => $recommendedPerWeek])
            );
        }

        // Check if pacing is too slow (subscription may expire before using all sessions)
        if ($daysPerWeek < $recommendedPerWeek * 0.5) {
            $weeksToFinish = ceil($remainingSessions / $daysPerWeek);
            $weeksRemaining = $limits['weeks_remaining'];

            if ($weeksToFinish > $weeksRemaining) {
                return ValidationResult::warning(
                    __('scheduling.pacing.too_slow', ['count' => $daysPerWeek, 'remaining' => $remainingSessions, 'weeks' => $weeksRemaining])
                );
            }
        }

        return ValidationResult::success(
            __('scheduling.pacing.suitable', ['total' => $totalSessionsToSchedule, 'weeks' => $weeksAhead])
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
            'reason' => __('scheduling.recommendations.subscription_reason', [
                'recommended' => $limits['recommended_per_week'],
                'remaining' => $limits['remaining_sessions'],
                'weeks' => $limits['weeks_remaining'],
                'date' => $limits['valid_end_date']->format('Y/m/d'),
            ]),
        ];
    }

    public function getSchedulingStatus(): array
    {
        // Check subscription status first
        if (! $this->subscription || $this->subscription->status !== SessionSubscriptionStatus::ACTIVE) {
            return [
                'status' => 'inactive',
                'message' => __('scheduling.status.inactive_subscription'),
                'color' => 'red',
                'can_schedule' => false,
                'urgent' => false,
            ];
        }

        if ($this->subscription->ends_at && $this->subscription->ends_at->isPast()) {
            return [
                'status' => 'expired',
                'message' => __('scheduling.status.expired_subscription', ['date' => $this->subscription->ends_at->format('Y/m/d')]),
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
                'message' => __('scheduling.status.fully_scheduled'),
                'color' => 'gray',
                'can_schedule' => false,
                'urgent' => false,
            ];
        }

        // Get future scheduled sessions
        $futureScheduled = $this->subscription->sessions()
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '>', now())
            ->count();

        if ($futureScheduled === 0) {
            return [
                'status' => 'not_scheduled',
                'message' => __('scheduling.status.not_scheduled', ['remaining' => $remainingSessions]),
                'color' => 'yellow',
                'can_schedule' => true,
                'urgent' => true,
            ];
        }

        if ($futureScheduled < $remainingSessions * 0.5) {
            return [
                'status' => 'partially_scheduled',
                'message' => __('scheduling.status.partially_scheduled', ['scheduled' => $futureScheduled, 'remaining' => $remainingSessions]),
                'color' => 'blue',
                'can_schedule' => true,
                'urgent' => true,
            ];
        }

        return [
            'status' => 'well_scheduled',
            'message' => __('scheduling.status.partially_scheduled', ['scheduled' => $futureScheduled, 'remaining' => $remainingSessions]),
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
            ->notCancelled()
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
