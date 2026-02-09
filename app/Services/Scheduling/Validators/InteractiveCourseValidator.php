<?php

namespace App\Services\Scheduling\Validators;

use App\Enums\SessionStatus;
use App\Models\InteractiveCourse;
use App\Services\AcademyContextService;
use App\Services\Scheduling\ValidationResult;
use Carbon\Carbon;

/**
 * Validator for Interactive Courses (Fixed session count, curriculum-based)
 */
class InteractiveCourseValidator implements ScheduleValidatorInterface
{
    public function __construct(
        private InteractiveCourse $course
    ) {}

    public function validateDaySelection(array $days): ValidationResult
    {
        $dayCount = count($days);

        if ($dayCount === 0) {
            return ValidationResult::error(__('scheduling.days.select_at_least_one'));
        }

        if ($dayCount > 5) {
            return ValidationResult::error(__('scheduling.days.max_per_week_course', ['max' => 5]));
        }

        // Calculate recommended days per week based on course duration
        // Use actual course configuration with fallbacks to prevent division by zero
        $totalSessions = $this->course->total_sessions ?? 16;
        $durationWeeks = max(1, $this->course->duration_weeks ?? 8);
        $recommendedDaysPerWeek = ceil($totalSessions / $durationWeeks);

        if ($dayCount > $recommendedDaysPerWeek + 1) {
            return ValidationResult::warning(
                __('scheduling.days.exceeds_recommended', [
                    'selected' => $dayCount,
                    'recommended' => $recommendedDaysPerWeek,
                    'context' => __('scheduling.days.context_course', ['total' => $totalSessions, 'weeks' => $durationWeeks]),
                    'consequence' => __('scheduling.days.consequence_course_ends_early'),
                ]),
                [
                    'selected' => $dayCount,
                    'recommended' => $recommendedDaysPerWeek,
                    'total_sessions' => $totalSessions,
                    'duration_weeks' => $durationWeeks,
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

        // Use actual course configuration with fallback
        $totalSessions = $this->course->total_sessions ?? 16;
        $scheduledSessions = $this->course->sessions()
            ->notCancelled()
            ->count();

        $remainingSessions = max(0, $totalSessions - $scheduledSessions);

        if ($remainingSessions <= 0) {
            return ValidationResult::error(
                __('scheduling.count.all_scheduled', ['total' => $totalSessions])
            );
        }

        if ($count > $remainingSessions) {
            return ValidationResult::error(
                __('scheduling.count.exceeds_remaining_course', ['count' => $count, 'remaining' => $remainingSessions, 'total' => $totalSessions])
            );
        }

        if ($count < $remainingSessions * 0.3) {
            return ValidationResult::warning(
                __('scheduling.count.few_scheduled_warning_short', ['count' => $count, 'remaining' => $remainingSessions])
            );
        }

        return ValidationResult::success(
            __('scheduling.count.success', ['count' => $count, 'remaining' => $remainingSessions]),
            [
                'count' => $count,
                'remaining' => $remainingSessions,
                'total' => $totalSessions,
            ]
        );
    }

    public function validateDateRange(?Carbon $startDate, int $weeksAhead): ValidationResult
    {
        $timezone = AcademyContextService::getTimezone();
        $requestedStart = $startDate ?? Carbon::now($timezone);
        $requestedEnd = $requestedStart->copy()->addWeeks($weeksAhead);

        // Check if course has start and end dates
        $courseStartDate = $this->course->start_date;
        $courseEndDate = $this->course->end_date;

        if ($courseStartDate && $requestedStart->isBefore($courseStartDate)) {
            return ValidationResult::error(
                __('scheduling.date.before_course_start', ['date' => $courseStartDate->format('Y/m/d')])
            );
        }

        if ($courseEndDate && $requestedEnd->isAfter($courseEndDate)) {
            return ValidationResult::warning(
                __('scheduling.date.exceeds_course_end', ['date' => $courseEndDate->format('Y/m/d')])
            );
        }

        // Allow scheduling from today onwards (actual time validation happens during scheduling)
        $now = Carbon::now($timezone)->startOfDay();
        if ($requestedStart->startOfDay()->lessThan($now)) {
            return ValidationResult::error(__('scheduling.date.cannot_schedule_past'));
        }

        // Check if requested period is reasonable for course duration
        // Use actual course configuration with fallback
        $durationWeeks = max(1, $this->course->duration_weeks ?? 8);
        if ($weeksAhead > $durationWeeks * 1.5) {
            return ValidationResult::warning(
                __('scheduling.date.exceeds_duration', ['weeks' => $weeksAhead, 'duration' => $durationWeeks])
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

        // Use actual course configuration with fallbacks
        $totalSessions = $this->course->total_sessions ?? 16;
        $scheduledSessions = $this->course->sessions()
            ->notCancelled()
            ->count();
        $remainingSessions = max(0, $totalSessions - $scheduledSessions);

        if ($totalSessionsToSchedule > $remainingSessions) {
            return ValidationResult::error(
                __('scheduling.pacing.exceeds_remaining_short', ['total' => $totalSessionsToSchedule, 'remaining' => $remainingSessions])
            );
        }

        // Calculate optimal pacing with fallback to prevent division by zero
        $durationWeeks = max(1, $this->course->duration_weeks ?? 8);
        $recommendedPerWeek = ceil($totalSessions / $durationWeeks);

        if ($daysPerWeek > $recommendedPerWeek * 1.5) {
            return ValidationResult::warning(
                __('scheduling.pacing.too_fast_course', ['count' => $daysPerWeek, 'recommended' => $recommendedPerWeek])
            );
        }

        if ($daysPerWeek < $recommendedPerWeek * 0.5) {
            return ValidationResult::warning(
                __('scheduling.pacing.too_slow_course', ['count' => $daysPerWeek])
            );
        }

        return ValidationResult::success(
            __('scheduling.pacing.suitable', ['total' => $totalSessionsToSchedule, 'weeks' => $weeksAhead])
        );
    }

    public function getRecommendations(): array
    {
        // Use actual course configuration with fallbacks to prevent division by zero
        $totalSessions = $this->course->total_sessions ?? 16;
        $durationWeeks = max(1, $this->course->duration_weeks ?? 8);
        $scheduledSessions = $this->course->sessions()
            ->notCancelled()
            ->count();

        $remainingSessions = max(0, $totalSessions - $scheduledSessions);
        $recommendedDaysPerWeek = max(1, ceil($totalSessions / $durationWeeks));

        // Calculate weeks needed to complete remaining sessions
        $weeksNeeded = $remainingSessions > 0 ? ceil($remainingSessions / $recommendedDaysPerWeek) : 0;

        return [
            'recommended_days' => $recommendedDaysPerWeek,
            'max_days' => min($recommendedDaysPerWeek + 1, 5),
            'total_sessions' => $totalSessions,
            'remaining_sessions' => $remainingSessions,
            'duration_weeks' => $durationWeeks,
            'weeks_needed' => $weeksNeeded,
            'reason' => __('scheduling.recommendations.course_reason', ['recommended' => $recommendedDaysPerWeek, 'remaining' => $remainingSessions, 'weeks' => $weeksNeeded, 'total' => $totalSessions]),
        ];
    }

    public function getSchedulingStatus(): array
    {
        $totalSessions = max(1, $this->course->total_sessions ?? 16);
        $scheduledSessions = $this->course->sessions()
            ->notCancelled()
            ->count();

        $remainingSessions = max(0, $totalSessions - $scheduledSessions);
        $completionPercentage = ($scheduledSessions / $totalSessions) * 100;

        if ($remainingSessions === 0) {
            return [
                'status' => 'fully_scheduled',
                'message' => __('scheduling.status.fully_scheduled_count', ['scheduled' => $totalSessions, 'total' => $totalSessions]),
                'color' => 'green',
                'can_schedule' => false,
                'urgent' => false,
                'progress' => 100,
            ];
        }

        // Check future scheduled sessions
        $futureScheduled = $this->course->sessions()
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '>', now())
            ->count();

        if ($futureScheduled === 0) {
            return [
                'status' => 'not_scheduled',
                'message' => __('scheduling.status.not_scheduled_course', ['done' => $scheduledSessions, 'total' => $totalSessions]),
                'color' => 'red',
                'can_schedule' => true,
                'urgent' => true,
                'progress' => round($completionPercentage, 1),
            ];
        }

        if ($futureScheduled < $remainingSessions * 0.3) {
            return [
                'status' => 'needs_more_scheduling',
                'message' => __('scheduling.status.needs_more', ['future' => $futureScheduled, 'remaining' => $remainingSessions]),
                'color' => 'yellow',
                'can_schedule' => true,
                'urgent' => true,
                'progress' => round($completionPercentage, 1),
            ];
        }

        return [
            'status' => 'partially_scheduled',
            'message' => __('scheduling.status.partially_scheduled', ['scheduled' => $futureScheduled, 'remaining' => $remainingSessions]),
            'color' => 'blue',
            'can_schedule' => true,
            'urgent' => false,
            'progress' => round($completionPercentage, 1),
        ];
    }

    /**
     * Get the maximum date that can be scheduled
     * Returns the course end date
     */
    public function getMaxScheduleDate(): ?Carbon
    {
        return $this->course->end_date;
    }
}
