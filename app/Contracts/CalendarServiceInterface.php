<?php

namespace App\Contracts;

use App\Models\User;
use Carbon\Carbon;

/**
 * Interface for calendar services.
 *
 * This interface defines the contract for calendar operations including
 * event retrieval, availability checking, and conflict detection.
 */
interface CalendarServiceInterface
{
    /**
     * Get calendar events for a user within a date range.
     *
     * @param User $user The user
     * @param Carbon $startDate Start of the date range
     * @param Carbon $endDate End of the date range
     * @param array $filters Optional filters (session_type, status, etc.)
     * @return array List of calendar events
     */
    public function getEvents(User $user, Carbon $startDate, Carbon $endDate, array $filters = []): array;

    /**
     * Get available time slots for scheduling.
     *
     * @param User $user The user (typically a teacher)
     * @param Carbon $date The date to check
     * @param int $durationMinutes Required duration in minutes
     * @param array $constraints Additional constraints
     * @return array List of available time slots
     */
    public function getAvailableSlots(User $user, Carbon $date, int $durationMinutes = 60, array $constraints = []): array;

    /**
     * Check for scheduling conflicts.
     *
     * @param User $user The user
     * @param Carbon $startTime Proposed start time
     * @param Carbon $endTime Proposed end time
     * @param int|null $excludeSessionId Session ID to exclude from conflict check
     * @return array List of conflicting events (empty if no conflicts)
     */
    public function checkConflicts(User $user, Carbon $startTime, Carbon $endTime, ?int $excludeSessionId = null): array;

    /**
     * Get weekly availability pattern for a user.
     *
     * @param User $user The user
     * @param Carbon|null $referenceDate Reference date for the week
     * @return array Weekly availability data
     */
    public function getWeeklyAvailability(User $user, ?Carbon $referenceDate = null): array;

    /**
     * Get calendar statistics for a user.
     *
     * @param User $user The user
     * @param Carbon $startDate Start of the period
     * @param Carbon $endDate End of the period
     * @return array Statistics data (completed, cancelled, etc.)
     */
    public function getStatistics(User $user, Carbon $startDate, Carbon $endDate): array;

    /**
     * Export calendar events to a specific format.
     *
     * @param User $user The user
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @param string $format Export format ('ics', 'csv', 'json')
     * @return string The exported data
     */
    public function export(User $user, Carbon $startDate, Carbon $endDate, string $format = 'ics'): string;
}
