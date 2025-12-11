<?php

namespace App\Contracts;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface for calendar operations.
 *
 * Defines the contract for fetching, formatting, and checking
 * calendar events across different session types.
 */
interface CalendarServiceInterface
{
    /**
     * Get unified calendar for a user.
     *
     * @param User $user The user to get calendar for
     * @param Carbon $startDate Start of date range
     * @param Carbon $endDate End of date range
     * @param array $filters Optional filters (types, status, search)
     * @return Collection Collection of calendar events
     */
    public function getUserCalendar(
        User $user,
        Carbon $startDate,
        Carbon $endDate,
        array $filters = []
    ): Collection;

    /**
     * Check for scheduling conflicts.
     *
     * @param User $user The user to check conflicts for
     * @param Carbon $startTime Proposed start time
     * @param Carbon $endTime Proposed end time
     * @param string|null $excludeType Type to exclude from check
     * @param int|null $excludeId ID to exclude from check
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
     * Get available time slots for a user.
     *
     * @param User $user The user to get slots for
     * @param Carbon $date The date to check
     * @param int $durationMinutes Required slot duration
     * @return Collection Collection of available time slots
     */
    public function getAvailableSlots(
        User $user,
        Carbon $date,
        int $durationMinutes = 60
    ): Collection;

    /**
     * Get calendar statistics for a user.
     *
     * @param User $user The user to get stats for
     * @param Carbon $month The month to get stats for
     * @return array Statistics array
     */
    public function getCalendarStats(User $user, Carbon $month): array;
}
