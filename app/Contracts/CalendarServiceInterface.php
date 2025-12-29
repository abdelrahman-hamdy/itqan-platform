<?php

namespace App\Contracts;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Calendar Service Interface
 *
 * Defines the contract for calendar service operations including event retrieval,
 * conflict checking, availability management, and calendar statistics.
 */
interface CalendarServiceInterface
{
    /**
     * Get unified calendar for user with optional filters
     *
     * Retrieves all calendar events for a user within a date range, including:
     * - Quran sessions (individual and circle)
     * - Academic sessions
     * - Interactive course sessions
     * - Break times and unavailable periods
     *
     * @param User $user The user to fetch calendar for
     * @param Carbon $startDate Start of date range
     * @param Carbon $endDate End of date range
     * @param array $filters Optional filters (types, status, search)
     * @return Collection Collection of formatted calendar events
     */
    public function getUserCalendar(
        User $user,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): Collection;

    /**
     * Check for scheduling conflicts when creating/updating events
     *
     * Checks if the proposed time slot conflicts with existing sessions,
     * courses, or circles for the specified user.
     *
     * @param User $user The user to check conflicts for
     * @param Carbon $startTime Proposed event start time
     * @param Carbon $endTime Proposed event end time
     * @param string|null $excludeType Type of event to exclude from check (e.g., 'quran_session')
     * @param int|null $excludeId ID of specific event to exclude from check
     * @return Collection Collection of conflicting events
     */
    public function checkConflicts(
        User $user,
        Carbon $startTime,
        Carbon $endTime,
        ?string $excludeType = null,
        ?int $excludeId = null
    ): Collection;

    /**
     * Get available time slots for user on a specific date
     *
     * Generates available time slots within working hours that don't conflict
     * with existing events.
     *
     * @param User $user The user to find availability for
     * @param Carbon $date The date to check availability
     * @param int $durationMinutes Duration of desired time slot in minutes
     * @param array $workingHours Array of start and end time strings (e.g., ['09:00', '17:00'])
     * @return Collection Collection of available time slots
     */
    public function getAvailableSlots(
        User $user,
        Carbon $date,
        int $durationMinutes = 60,
        array $workingHours = ['09:00', '17:00']
    ): Collection;

    /**
     * Get teacher availability for the entire week
     *
     * Returns a comprehensive view of teacher availability across a week,
     * including available slots, booked sessions, and statistics.
     *
     * @param User $teacher The teacher to get availability for
     * @param Carbon $weekStart Start date of the week
     * @return array Array of availability data indexed by day name
     */
    public function getTeacherWeeklyAvailability(User $teacher, Carbon $weekStart): array;

    /**
     * Get calendar statistics for a specific month
     *
     * Provides statistical analysis of calendar events including:
     * - Total events count
     * - Events by type and status
     * - Weekly breakdown
     * - Busiest day
     * - Total hours
     *
     * @param User $user The user to get statistics for
     * @param Carbon $month The month to analyze
     * @return array Array of statistical data
     */
    public function getCalendarStats(User $user, Carbon $month): array;
}
