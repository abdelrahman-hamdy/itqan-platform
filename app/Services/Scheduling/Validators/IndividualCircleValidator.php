<?php

namespace App\Services\Scheduling\Validators;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
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
    public function __construct(
        private QuranIndividualCircle $circle,
        private SessionManagementService $sessionService
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

        if ($limits['remaining_sessions'] <= 0) {
            return ValidationResult::error(__('scheduling.count.no_remaining_circle'));
        }

        $recommendedPerWeek = $limits['recommended_per_week'];
        $maxPerWeek = $limits['max_per_week'];

        if ($dayCount > $maxPerWeek) {
            return ValidationResult::warning(
                __('scheduling.days.exceeds_recommended', ['selected' => $dayCount, 'recommended' => $recommendedPerWeek, 'context' => '', 'consequence' => __('scheduling.days.consequence_fast_finish')]),
                ['selected' => $dayCount, 'recommended' => $recommendedPerWeek, 'max' => $maxPerWeek]
            );
        }

        return ValidationResult::success(
            __('scheduling.days.count_suitable', ['count' => $dayCount]),
            ['selected' => $dayCount, 'recommended' => $recommendedPerWeek]
        );
    }

    public function validateSessionCount(int $count): ValidationResult
    {
        $limits = $this->getSubscriptionLimits();
        $remaining = $limits['remaining_sessions'];

        if ($count <= 0) {
            return ValidationResult::error(__('scheduling.count.must_be_positive'));
        }

        if ($count > $remaining) {
            return ValidationResult::error(
                __('scheduling.count.exceeds_remaining_short', ['count' => $count, 'remaining' => $remaining]),
                ['requested' => $count, 'remaining' => $remaining]
            );
        }

        if ($count > 100) {
            return ValidationResult::error(__('scheduling.count.max_batch_simple', ['max' => 100]));
        }

        return ValidationResult::success(
            __('scheduling.count.suitable_of_remaining', ['count' => $count, 'remaining' => $remaining])
        );
    }

    public function validateDateRange(?Carbon $startDate, int $weeksAhead): ValidationResult
    {
        $limits = $this->getSubscriptionLimits();
        $subscription = $this->circle->subscription;

        if (! $subscription) {
            return ValidationResult::error(__('scheduling.date.no_active_subscription'));
        }

        if ($subscription->status !== SessionSubscriptionStatus::ACTIVE) {
            return ValidationResult::error(__('scheduling.date.subscription_inactive'));
        }

        $validStart = $limits['valid_start_date'];
        $validEnd = $limits['valid_end_date'];

        $timezone = AcademyContextService::getTimezone();
        $requestedStart = $startDate ?? Carbon::now($timezone);
        $requestedEnd = $requestedStart->copy()->addWeeks($weeksAhead);

        if ($requestedStart->isBefore($validStart)) {
            return ValidationResult::error(
                __('scheduling.date.before_subscription_start', ['date' => $validStart->format('Y/m/d')])
            );
        }

        // Only check expiry if subscription has an end date
        if ($validEnd !== null && $requestedEnd->isAfter($validEnd)) {
            return ValidationResult::warning(
                __('scheduling.date.exceeds_subscription_end_auto', ['date' => $validEnd->format('Y/m/d')]),
                ['subscription_end' => $validEnd->format('Y/m/d')]
            );
        }

        return ValidationResult::success(
            __('scheduling.date.range_valid', ['start' => $requestedStart->format('Y/m/d'), 'end' => $requestedEnd->format('Y/m/d')])
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
                __('scheduling.pacing.overflow_warning', ['days' => $daysPerWeek, 'weeks' => $weeksAhead, 'total' => $totalSessionsToSchedule, 'remaining' => $remaining]),
                [
                    'total_requested' => $totalSessionsToSchedule,
                    'remaining' => $remaining,
                    'max_weeks' => $maxWeeks,
                    'will_schedule' => $remaining,
                ]
            );
        }

        $recommendedPerWeek = $limits['recommended_per_week'];

        if ($daysPerWeek > $recommendedPerWeek * 2) {
            return ValidationResult::warning(
                __('scheduling.pacing.double_recommended', ['count' => $daysPerWeek, 'recommended' => $recommendedPerWeek])
            );
        }

        return ValidationResult::success(__('scheduling.pacing.suitable_simple'));
    }

    public function getRecommendations(): array
    {
        $limits = $this->getSubscriptionLimits();

        return [
            'recommended_days' => (int) round($limits['recommended_per_week']),
            'max_days' => $limits['max_per_week'],
            'remaining_sessions' => $limits['remaining_sessions'],
            'weeks_remaining' => $limits['weeks_remaining'],
            'reason' => __('scheduling.recommendations.circle_reason', ['recommended' => $limits['recommended_per_week'], 'remaining' => $limits['remaining_sessions'], 'weeks' => $limits['weeks_remaining']]),
        ];
    }

    public function getSchedulingStatus(): array
    {
        $subscription = $this->circle->subscription;

        if (! $subscription || $subscription->status !== SessionSubscriptionStatus::ACTIVE) {
            return [
                'status' => 'inactive',
                'message' => __('scheduling.status.inactive_subscription'),
                'color' => 'gray',
                'can_schedule' => false,
            ];
        }

        // Check if subscription has expired (use ends_at field)
        if ($subscription->ends_at && $subscription->ends_at->isPast()) {
            return [
                'status' => 'expired',
                'message' => __('scheduling.status.expired_subscription_short'),
                'color' => 'red',
                'can_schedule' => false,
            ];
        }

        $totalSessions = $subscription->total_sessions;
        // Use lockForUpdate to prevent race conditions during session counting
        $scheduledSessions = $this->circle->sessions()
            ->lockForUpdate()
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value, SessionStatus::COMPLETED->value])
            ->count();
        $remaining = $totalSessions - $scheduledSessions;

        if ($remaining <= 0) {
            return [
                'status' => 'fully_scheduled',
                'message' => __('scheduling.status.fully_scheduled'),
                'color' => 'green',
                'can_schedule' => false,
            ];
        }

        $timezone = AcademyContextService::getTimezone();
        $futureScheduled = $this->circle->sessions()
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '>', Carbon::now($timezone))
            ->count();

        if ($futureScheduled === 0) {
            return [
                'status' => 'not_scheduled',
                'message' => __('scheduling.status.not_scheduled_circle', ['remaining' => $remaining]),
                'color' => 'yellow',
                'can_schedule' => true,
            ];
        } else {
            return [
                'status' => 'partially_scheduled',
                'message' => __('scheduling.status.partially_scheduled_circle', ['scheduled' => $futureScheduled, 'remaining' => $remaining]),
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
        if (! $subscription) {
            // Calculate remaining based on circle's total_sessions field
            // Use lockForUpdate to prevent race conditions during session counting
            $totalSessions = $this->circle->total_sessions ?? 0;
            $usedSessions = $this->circle->sessions()
                ->lockForUpdate()
                ->whereIn('status', [SessionStatus::COMPLETED->value, SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value])
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

        // CRITICAL FIX: Prioritize ends_at field when available
        // Only fall back to calculating from billing_cycle if ends_at is not set
        $endDate = null;
        if ($subscription->ends_at) {
            // Use the actual ends_at field from the subscription
            $endDate = $subscription->ends_at;
        } elseif ($subscription->starts_at && $subscription->billing_cycle) {
            // Fall back to calculating from billing cycle only if ends_at is not set
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
     * Prioritizes ends_at field, falls back to billing cycle calculation
     */
    public function getMaxScheduleDate(): ?Carbon
    {
        $subscription = $this->circle->individualSubscription ?? $this->circle->subscription;
        if (! $subscription) {
            return null;
        }

        // Prioritize ends_at field when available
        if ($subscription->ends_at) {
            return $subscription->ends_at;
        }

        // Fall back to billing cycle calculation
        if (! $subscription->starts_at || ! $subscription->billing_cycle) {
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
