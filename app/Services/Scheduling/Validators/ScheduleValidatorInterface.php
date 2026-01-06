<?php

namespace App\Services\Scheduling\Validators;

use App\Services\Scheduling\ValidationResult;
use Carbon\Carbon;

/**
 * Interface for schedule validators
 *
 * Each entity type (Trial, Course, Circle, etc.) implements this
 * to provide custom validation logic
 */
interface ScheduleValidatorInterface
{
    /**
     * Validate selected days of the week
     *
     * @param  array  $days  Array of day names ['sunday', 'monday', ...]
     */
    public function validateDaySelection(array $days): ValidationResult;

    /**
     * Validate total session count to be scheduled
     *
     * @param  int  $count  Number of sessions to create
     */
    public function validateSessionCount(int $count): ValidationResult;

    /**
     * Validate the date range for scheduling
     *
     * @param  Carbon|null  $startDate  Starting date (null = now)
     * @param  int  $weeksAhead  How many weeks to schedule
     */
    public function validateDateRange(?Carbon $startDate, int $weeksAhead): ValidationResult;

    /**
     * Validate the weekly pacing (sessions per week)
     *
     * @param  array  $days  Selected days
     * @param  int  $weeksAhead  Number of weeks
     */
    public function validateWeeklyPacing(array $days, int $weeksAhead): ValidationResult;

    /**
     * Get scheduling recommendations for this entity
     *
     * @return array ['recommended_days' => int, 'max_days' => int, 'reason' => string]
     */
    public function getRecommendations(): array;

    /**
     * Get current scheduling status
     *
     * @return array ['status' => string, 'message' => string, 'data' => array]
     */
    public function getSchedulingStatus(): array;
}
