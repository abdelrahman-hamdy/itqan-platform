<?php

namespace App\Contracts;

/**
 * Attendance Event Service Interface
 *
 * Defines the contract for recording attendance events from LiveKit webhooks.
 * This service handles simple data storage of join/leave events without complex
 * business logic. Actual attendance calculation happens post-meeting.
 */
interface AttendanceEventServiceInterface
{
    /**
     * Record user joining the meeting (from LiveKit webhook)
     *
     * Creates or updates MeetingAttendance record when a user joins the meeting.
     * Records join timestamp, participant SID, and updates join cycles.
     *
     * @param mixed $session The session instance (QuranSession, AcademicSession, etc.)
     * @param mixed $user The user who joined
     * @param array $eventData Event data from LiveKit webhook including:
     *                         - timestamp: When the user joined
     *                         - event_id: Unique event identifier
     *                         - participant_sid: LiveKit participant session ID
     * @return bool True if event was recorded successfully, false otherwise
     */
    public function recordJoin($session, $user, array $eventData): bool;

    /**
     * Record user leaving the meeting (from LiveKit webhook)
     *
     * Updates MeetingAttendance record when a user leaves the meeting.
     * Records leave timestamp, matches with corresponding join event,
     * and calculates duration for that cycle.
     *
     * @param mixed $session The session instance (QuranSession, AcademicSession, etc.)
     * @param mixed $user The user who left
     * @param array $eventData Event data from LiveKit webhook including:
     *                         - timestamp: When the user left
     *                         - event_id: Unique event identifier
     *                         - participant_sid: LiveKit participant session ID
     *                         - duration_minutes: Optional pre-calculated duration
     * @return bool True if event was recorded successfully, false otherwise
     */
    public function recordLeave($session, $user, array $eventData): bool;
}
