<?php

namespace App\Services\Scheduling\Validators;

use App\Enums\SessionStatus;
use App\Enums\TrialRequestStatus;
use App\Models\QuranTrialRequest;
use App\Services\AcademyContextService;
use App\Services\Scheduling\ValidationResult;
use Carbon\Carbon;

/**
 * Validator for Trial Sessions (Simplest - exactly 1 session)
 */
class TrialSessionValidator implements ScheduleValidatorInterface
{
    public function __construct(
        private QuranTrialRequest $trialRequest
    ) {}

    public function validateDaySelection(array $days): ValidationResult
    {
        $dayCount = count($days);

        if ($dayCount === 0) {
            return ValidationResult::error(__('scheduling.trial.select_one_day'));
        }

        if ($dayCount > 1) {
            return ValidationResult::warning(
                __('scheduling.trial.one_day_only')
            );
        }

        return ValidationResult::success(__('scheduling.trial.day_selected'));
    }

    public function validateSessionCount(int $count): ValidationResult
    {
        if ($count === 0) {
            return ValidationResult::error(__('scheduling.trial.must_schedule_one'));
        }

        if ($count > 1) {
            return ValidationResult::warning(
                __('scheduling.trial.one_session_only')
            );
        }

        return ValidationResult::success(__('scheduling.trial.will_schedule_one'));
    }

    public function validateDateRange(?Carbon $startDate, int $weeksAhead): ValidationResult
    {
        $timezone = AcademyContextService::getTimezone();
        $now = Carbon::now($timezone);
        $requestedStart = $startDate ?? $now->copy();

        // For trial sessions, just validate the date is today or in the future
        // The full datetime validation (with time) is done in SessionManagementService::createTrialSession()
        // which properly combines date + time and validates against isPast()
        $today = $now->copy()->startOfDay();

        if ($requestedStart->copy()->startOfDay()->isBefore($today)) {
            return ValidationResult::error(
                __('scheduling.date.cannot_schedule_trial_past')
            );
        }

        // Check if trial request is still valid (use TrialRequestStatus, not SessionStatus)
        if ($this->trialRequest->status === TrialRequestStatus::CANCELLED) {
            return ValidationResult::error(__('scheduling.trial.cancelled_request'));
        }

        if ($this->trialRequest->status === TrialRequestStatus::COMPLETED) {
            return ValidationResult::error(__('scheduling.trial.completed_request'));
        }

        // Check if status allows scheduling - only PENDING requests can be scheduled
        if ($this->trialRequest->status !== TrialRequestStatus::PENDING) {
            $statusLabel = $this->trialRequest->status instanceof TrialRequestStatus
                ? $this->trialRequest->status->label()
                : $this->trialRequest->status;

            return ValidationResult::error(
                __('scheduling.trial.status_not_allowed', ['status' => $statusLabel])
            );
        }

        // Format time in academy timezone for display
        $timezone = AcademyContextService::getTimezone();
        $formattedTime = $requestedStart->copy()->setTimezone($timezone)->format('Y/m/d h:i A');

        return ValidationResult::success(
            __('scheduling.trial.scheduled_at', ['time' => $formattedTime])
        );
    }

    public function validateWeeklyPacing(array $days, int $weeksAhead): ValidationResult
    {
        // Trial sessions are always just 1 session, so pacing doesn't apply
        if ($weeksAhead > 1) {
            return ValidationResult::warning(
                __('scheduling.trial.one_week_max')
            );
        }

        return ValidationResult::success(__('scheduling.trial.pacing_suitable'));
    }

    public function getRecommendations(): array
    {
        $timezone = AcademyContextService::getTimezone();
        $preferredDate = Carbon::now($timezone)->addDay(); // Recommend next day
        $preferredTime = $this->trialRequest->preferred_time ?? '16:00';

        return [
            'recommended_days' => 1,
            'recommended_count' => 1,
            'recommended_date' => $preferredDate->format('Y-m-d'),
            'recommended_time' => $preferredTime,
            'reason' => __('scheduling.recommendations.trial_reason'),
        ];
    }

    public function getSchedulingStatus(): array
    {
        // Check if trial request already has a scheduled session
        $hasScheduledSession = $this->trialRequest->trialSessions()
            ->notCancelled()
            ->exists();

        if ($hasScheduledSession) {
            $session = $this->trialRequest->trialSessions()
                ->notCancelled()
                ->first();

            if ($session->status === SessionStatus::COMPLETED) {
                return [
                    'status' => SessionStatus::COMPLETED,
                    'message' => __('scheduling.trial.completed'),
                    'color' => 'gray',
                    'can_schedule' => false,
                    'urgent' => false,
                ];
            }

            // Format time in academy timezone for display
            $timezone = AcademyContextService::getTimezone();
            $formattedTime = $session->scheduled_at->copy()->setTimezone($timezone)->format('Y/m/d h:i A');

            return [
                'status' => SessionStatus::SCHEDULED,
                'message' => __('scheduling.trial.scheduled', ['time' => $formattedTime]),
                'color' => 'green',
                'can_schedule' => false,
                'urgent' => false,
            ];
        }

        // Check if trial request is in valid state for scheduling - only PENDING
        if ($this->trialRequest->status !== TrialRequestStatus::PENDING) {
            return [
                'status' => 'cannot_schedule',
                'message' => __('scheduling.trial.cannot_schedule_status'),
                'color' => 'red',
                'can_schedule' => false,
                'urgent' => false,
            ];
        }

        // Ready to schedule
        return [
            'status' => 'not_scheduled',
            'message' => __('scheduling.trial.ready_to_schedule'),
            'color' => 'yellow',
            'can_schedule' => true,
            'urgent' => true,
        ];
    }

    /**
     * Get the maximum date that can be scheduled
     * Trial sessions have no specific end date limit
     */
    public function getMaxScheduleDate(): ?Carbon
    {
        return null; // No end date for trial sessions
    }
}
