<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Interface for meeting attendance tracking service.
 *
 * Handles real-time attendance tracking for LiveKit meeting participants
 * including join/leave events, attendance calculation, and statistics.
 */
interface MeetingAttendanceServiceInterface
{
    /**
     * Handle user joining a meeting.
     *
     * @return bool True if join recorded successfully
     */
    public function handleUserJoin(MeetingCapable $session, User $user): bool;

    /**
     * Handle user leaving a meeting.
     *
     * @return bool True if leave recorded successfully
     */
    public function handleUserLeave(MeetingCapable $session, User $user): bool;

    /**
     * Handle user joining a meeting (polymorphic version for any session type).
     *
     * @param  mixed  $session
     * @return bool True if join recorded successfully
     */
    public function handleUserJoinPolymorphic($session, User $user, string $sessionType): bool;

    /**
     * Handle user leaving a meeting (polymorphic version for any session type).
     *
     * @param  mixed  $session
     * @return bool True if leave recorded successfully
     */
    public function handleUserLeavePolymorphic($session, User $user, string $sessionType): bool;

    /**
     * Calculate final attendance for all participants of a session.
     *
     * @return array Results with calculated count, errors, and attendance data
     */
    public function calculateFinalAttendance(MeetingCapable $session): array;

    /**
     * Process attendance for multiple completed sessions.
     *
     * @return array Results with processed counts and errors
     */
    public function processCompletedSessions(Collection $sessions): array;

    /**
     * Handle reconnection detection.
     *
     * @return bool True if this is a reconnection
     */
    public function handleReconnection(MeetingCapable $session, User $user): bool;

    /**
     * Get attendance statistics for a session.
     *
     * @return array Statistics including present/absent counts and percentages
     */
    public function getAttendanceStatistics(MeetingCapable $session): array;

    /**
     * Cleanup old uncalculated attendance records.
     *
     * @return int Number of records cleaned up
     */
    public function cleanupOldAttendanceRecords(int $daysOld = 7): int;

    /**
     * Force recalculation of attendance for a session.
     *
     * @return array Results with calculated count, errors, and attendance data
     */
    public function recalculateAttendance(MeetingCapable $session): array;

    /**
     * Export attendance data for reporting.
     *
     * @return array Formatted attendance data
     */
    public function exportAttendanceData(MeetingCapable $session): array;
}
