<?php

namespace App\Services\Scheduling\Validators;

use App\Enums\TrialRequestStatus;
use App\Models\QuranTrialRequest;
use App\Services\Scheduling\ValidationResult;
use Carbon\Carbon;
use App\Enums\SessionStatus;

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
            return ValidationResult::error('يجب اختيار يوم واحد للجلسة التجريبية');
        }

        if ($dayCount > 1) {
            return ValidationResult::warning(
                '⚠️ الجلسة التجريبية تحتاج يوم واحد فقط. سيتم استخدام اليوم الأول المختار.'
            );
        }

        return ValidationResult::success('✓ تم اختيار يوم واحد للجلسة التجريبية');
    }

    public function validateSessionCount(int $count): ValidationResult
    {
        if ($count === 0) {
            return ValidationResult::error('يجب جدولة جلسة واحدة على الأقل');
        }

        if ($count > 1) {
            return ValidationResult::warning(
                '⚠️ الجلسة التجريبية تتطلب جلسة واحدة فقط. سيتم إنشاء جلسة واحدة.'
            );
        }

        return ValidationResult::success('✓ سيتم جدولة جلسة تجريبية واحدة');
    }

    public function validateDateRange(?Carbon $startDate, int $weeksAhead): ValidationResult
    {
        $requestedStart = $startDate ?? now();

        // Trial sessions must be at least 1 hour in the future
        $minimumStart = now()->addHour();

        if ($requestedStart->isBefore($minimumStart)) {
            return ValidationResult::error(
                'يجب جدولة الجلسة التجريبية قبل موعدها بساعة واحدة على الأقل'
            );
        }

        // Check if trial request is still valid (use TrialRequestStatus, not SessionStatus)
        if ($this->trialRequest->status === TrialRequestStatus::CANCELLED) {
            return ValidationResult::error('لا يمكن جدولة جلسة لطلب تجريبي ملغي');
        }

        if ($this->trialRequest->status === TrialRequestStatus::COMPLETED) {
            return ValidationResult::error('تم إكمال هذا الطلب التجريبي بالفعل');
        }

        // Check if status allows scheduling (using enum values for comparison)
        $schedulableStatuses = [TrialRequestStatus::PENDING, TrialRequestStatus::APPROVED];
        if (!in_array($this->trialRequest->status, $schedulableStatuses)) {
            $statusLabel = $this->trialRequest->status instanceof TrialRequestStatus
                ? $this->trialRequest->status->label()
                : $this->trialRequest->status;
            return ValidationResult::error(
                'حالة الطلب التجريبي لا تسمح بالجدولة: ' . $statusLabel
            );
        }

        return ValidationResult::success(
            "✓ موعد الجلسة التجريبية: {$requestedStart->format('Y/m/d H:i')}"
        );
    }

    public function validateWeeklyPacing(array $days, int $weeksAhead): ValidationResult
    {
        // Trial sessions are always just 1 session, so pacing doesn't apply
        if ($weeksAhead > 1) {
            return ValidationResult::warning(
                '⚠️ الجلسة التجريبية لا تحتاج لأكثر من أسبوع واحد للجدولة'
            );
        }

        return ValidationResult::success('✓ الجدولة مناسبة للجلسة التجريبية');
    }

    public function getRecommendations(): array
    {
        $preferredDate = now()->addDay(); // Recommend next day
        $preferredTime = $this->trialRequest->preferred_time ?? '16:00';

        return [
            'recommended_days' => 1,
            'recommended_count' => 1,
            'recommended_date' => $preferredDate->format('Y-m-d'),
            'recommended_time' => $preferredTime,
            'reason' => 'الجلسة التجريبية تحتاج جلسة واحدة فقط مدتها ' .
                       ($this->trialRequest->duration_minutes ?? 30) . ' دقيقة',
        ];
    }

    public function getSchedulingStatus(): array
    {
        // Check if trial request already has a scheduled session
        $hasScheduledSession = $this->trialRequest->trialSessions()
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value, SessionStatus::COMPLETED->value])
            ->exists();

        if ($hasScheduledSession) {
            $session = $this->trialRequest->trialSessions()
                ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value, SessionStatus::COMPLETED->value])
                ->first();

            if ($session->status === SessionStatus::COMPLETED) {
                return [
                    'status' => SessionStatus::COMPLETED,
                    'message' => 'تم إكمال الجلسة التجريبية',
                    'color' => 'gray',
                    'can_schedule' => false,
                    'urgent' => false,
                ];
            }

            return [
                'status' => SessionStatus::SCHEDULED,
                'message' => "مجدولة: {$session->scheduled_at->format('Y/m/d H:i')}",
                'color' => 'green',
                'can_schedule' => false,
                'urgent' => false,
            ];
        }

        // Check if trial request is in valid state for scheduling (using enum comparison)
        $schedulableStatuses = [TrialRequestStatus::PENDING, TrialRequestStatus::APPROVED];
        if (!in_array($this->trialRequest->status, $schedulableStatuses)) {
            return [
                'status' => 'cannot_schedule',
                'message' => 'حالة الطلب لا تسمح بالجدولة',
                'color' => 'red',
                'can_schedule' => false,
                'urgent' => false,
            ];
        }

        // Ready to schedule
        return [
            'status' => 'not_scheduled',
            'message' => 'جاهز للجدولة',
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
