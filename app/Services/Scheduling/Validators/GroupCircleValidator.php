<?php

namespace App\Services\Scheduling\Validators;

use App\Models\QuranCircle;
use App\Services\AcademyContextService;
use App\Services\Scheduling\ValidationResult;
use Carbon\Carbon;

/**
 * Validator for Group Quran Circles (Continuous, no fixed end)
 */
class GroupCircleValidator implements ScheduleValidatorInterface
{
    public function __construct(
        private QuranCircle $circle
    ) {}

    /**
     * Validate circle capacity before scheduling
     *
     * Checks if the circle has students enrolled and if it's at capacity.
     * Warning if near capacity, error if empty (no point scheduling without students).
     */
    public function validateCapacity(): ValidationResult
    {
        $maxStudents = $this->circle->max_students ?? 20;
        $currentStudents = $this->circle->students()->count();
        $availableSlots = $maxStudents - $currentStudents;

        // If no students enrolled, warn but allow scheduling
        if ($currentStudents === 0) {
            return ValidationResult::warning(
                __('scheduling.capacity.no_students'),
                ['max_students' => $maxStudents, 'current_students' => 0, 'available_slots' => $availableSlots]
            );
        }

        // If circle is at minimum threshold (less than 25% capacity), warn
        $minThreshold = ceil($maxStudents * 0.25);
        if ($currentStudents < $minThreshold) {
            return ValidationResult::warning(
                __('scheduling.capacity.low_students', ['current' => $currentStudents, 'max' => $maxStudents]),
                ['max_students' => $maxStudents, 'current_students' => $currentStudents, 'available_slots' => $availableSlots]
            );
        }

        // If circle is full, inform (not an error - can still schedule)
        if ($availableSlots <= 0) {
            return ValidationResult::success(
                __('scheduling.capacity.full', ['current' => $currentStudents, 'max' => $maxStudents]),
                ['max_students' => $maxStudents, 'current_students' => $currentStudents, 'is_full' => true]
            );
        }

        return ValidationResult::success(
            __('scheduling.capacity.suitable', ['current' => $currentStudents, 'max' => $maxStudents, 'available' => $availableSlots]),
            ['max_students' => $maxStudents, 'current_students' => $currentStudents, 'available_slots' => $availableSlots]
        );
    }

    public function validateDaySelection(array $days): ValidationResult
    {
        $dayCount = count($days);

        if ($dayCount === 0) {
            return ValidationResult::error(__('scheduling.days.select_at_least_one'));
        }

        if ($dayCount > 7) {
            return ValidationResult::error(__('scheduling.days.max_per_week', ['max' => 7]));
        }

        // Use actual monthly_sessions_count from circle (database has default of 8)
        $monthlyTarget = $this->circle->monthly_sessions_count;
        $recommendedDaysPerWeek = ceil($monthlyTarget / 4); // 4 weeks in a month
        $maxDaysPerWeek = $recommendedDaysPerWeek + 2; // Allow flexibility

        if ($dayCount > $maxDaysPerWeek) {
            return ValidationResult::warning(
                __('scheduling.days.exceeds_recommended', ['selected' => $dayCount, 'recommended' => $recommendedDaysPerWeek, 'context' => __('scheduling.days.context_monthly_target', ['target' => $monthlyTarget]), 'consequence' => __('scheduling.days.consequence_more_than_usual')]),
                [
                    'selected' => $dayCount,
                    'recommended' => $recommendedDaysPerWeek,
                    'max' => $maxDaysPerWeek,
                    'monthly_target' => $monthlyTarget,
                ]
            );
        }

        return ValidationResult::success(
            __('scheduling.days.count_suitable', ['count' => $dayCount]),
            ['selected' => $dayCount, 'recommended' => $recommendedDaysPerWeek]
        );
    }

    public function validateSessionCount(int $count): ValidationResult
    {
        if ($count <= 0) {
            return ValidationResult::error(__('scheduling.count.must_be_positive'));
        }

        if ($count > 100) {
            return ValidationResult::error(__('scheduling.count.max_batch', ['max' => 100]));
        }

        // Use actual monthly_sessions_count from circle (database has default of 8)
        $monthlyTarget = $this->circle->monthly_sessions_count;
        $recommendedCount = $monthlyTarget; // Default to one month

        if ($count < $monthlyTarget / 2) {
            return ValidationResult::warning(
                __('scheduling.count.below_half_monthly', ['count' => $count, 'target' => $monthlyTarget])
            );
        }

        if ($count > $monthlyTarget * 3) {
            return ValidationResult::warning(
                __('scheduling.count.exceeds_three_months', ['count' => $count])
            );
        }

        return ValidationResult::success(
            __('scheduling.count.suitable', ['count' => $count]),
            ['count' => $count, 'monthly_target' => $monthlyTarget]
        );
    }

    public function validateDateRange(?Carbon $startDate, int $weeksAhead): ValidationResult
    {
        // Use academy timezone for accurate time comparison
        $timezone = AcademyContextService::getTimezone();
        $requestedStart = $startDate ?? Carbon::now($timezone);

        // Group circles are continuous, no end date restriction
        // Allow scheduling from today onwards (actual time validation happens during scheduling)
        $now = Carbon::now($timezone)->startOfDay();
        if ($requestedStart->startOfDay()->lessThan($now)) {
            return ValidationResult::error(__('scheduling.date.cannot_schedule_past'));
        }

        if ($weeksAhead > 52) {
            return ValidationResult::warning(
                __('scheduling.date.exceeds_year', ['weeks' => $weeksAhead])
            );
        }

        return ValidationResult::success(
            __('scheduling.date.range_valid_from', ['start' => $requestedStart->format('Y/m/d')])
        );
    }

    public function validateWeeklyPacing(array $days, int $weeksAhead): ValidationResult
    {
        $daysPerWeek = count($days);
        $totalSessions = $daysPerWeek * $weeksAhead;

        // Use actual monthly_sessions_count from circle (database has default of 8)
        $monthlyTarget = $this->circle->monthly_sessions_count;
        $expectedMonths = ceil($weeksAhead / 4);
        $expectedTotal = $monthlyTarget * $expectedMonths;

        if ($totalSessions < $expectedTotal * 0.7) {
            return ValidationResult::warning(
                __('scheduling.pacing.below_expected', ['total' => $totalSessions, 'expected' => $expectedTotal, 'months' => $expectedMonths])
            );
        }

        if ($totalSessions > $expectedTotal * 1.3) {
            return ValidationResult::warning(
                __('scheduling.pacing.above_expected', ['total' => $totalSessions, 'expected' => $expectedTotal, 'months' => $expectedMonths])
            );
        }

        return ValidationResult::success(__('scheduling.pacing.suitable_count', ['total' => $totalSessions]));
    }

    public function getRecommendations(): array
    {
        // Use actual monthly_sessions_count from circle (database has default of 8)
        $monthlyTarget = $this->circle->monthly_sessions_count;
        $recommendedDaysPerWeek = ceil($monthlyTarget / 4);

        // Include capacity information
        $maxStudents = $this->circle->max_students ?? 20;
        $currentStudents = $this->circle->students()->count();
        $availableSlots = max(0, $maxStudents - $currentStudents);

        return [
            'recommended_days' => $recommendedDaysPerWeek,
            'max_days' => $recommendedDaysPerWeek + 2,
            'monthly_target' => $monthlyTarget,
            'max_students' => $maxStudents,
            'current_students' => $currentStudents,
            'available_slots' => $availableSlots,
            'is_full' => $availableSlots <= 0,
            'reason' => __('scheduling.recommendations.group_circle_reason', ['recommended' => $recommendedDaysPerWeek, 'target' => $monthlyTarget]),
        ];
    }

    public function getSchedulingStatus(): array
    {
        $timezone = AcademyContextService::getTimezone();
        $now = Carbon::now($timezone);
        $oneMonthAhead = $now->copy()->addMonth();

        $futureSessionsCount = $this->circle->sessions()
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $oneMonthAhead)
            ->count();

        // Use actual monthly_sessions_count from circle (database has default of 8)
        $monthlyTarget = $this->circle->monthly_sessions_count;

        if ($futureSessionsCount === 0) {
            return [
                'status' => 'not_scheduled',
                'message' => __('scheduling.status.not_scheduled_month'),
                'color' => 'red',
                'can_schedule' => true,
                'urgent' => true,
            ];
        } elseif ($futureSessionsCount < $monthlyTarget * 0.5) {
            return [
                'status' => 'needs_scheduling',
                'message' => __('scheduling.status.needs_scheduling', ['count' => $futureSessionsCount]),
                'color' => 'yellow',
                'can_schedule' => true,
                'urgent' => true,
            ];
        } else {
            return [
                'status' => 'actively_scheduled',
                'message' => __('scheduling.status.actively_scheduled', ['count' => $futureSessionsCount]),
                'color' => 'green',
                'can_schedule' => true,
                'urgent' => false,
            ];
        }
    }

    /**
     * Get the maximum date that can be scheduled
     * Group circles are continuous, so return null (no limit)
     */
    public function getMaxScheduleDate(): ?Carbon
    {
        return null; // No end date for group circles
    }
}
