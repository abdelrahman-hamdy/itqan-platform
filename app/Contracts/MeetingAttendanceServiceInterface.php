<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Interface for meeting attendance tracking with coordination.
 *
 * Handles attendance operations that require side effects like
 * status transitions, broadcasting, and notifications.
 *
 * For pure calculation operations (recalculate, statistics, export, cleanup),
 * use AttendanceCalculationService directly.
 */
interface MeetingAttendanceServiceInterface
{
    /**
     * Handle user joining a meeting.
     *
     * Side effects: session status transition (READY -> ONGOING), broadcasting.
     *
     * @return bool True if join recorded successfully
     */
    public function handleUserJoin(MeetingCapable $session, User $user): bool;

    /**
     * Handle user leaving a meeting.
     *
     * Side effects: broadcasting attendance update.
     *
     * @return bool True if leave recorded successfully
     */
    public function handleUserLeave(MeetingCapable $session, User $user): bool;

    /**
     * Calculate final attendance for all participants of a session.
     *
     * Side effects: sends notifications to students and parents.
     *
     * @return array Results with calculated count, errors, and attendance data
     */
    public function calculateFinalAttendance(MeetingCapable $session): array;
}
