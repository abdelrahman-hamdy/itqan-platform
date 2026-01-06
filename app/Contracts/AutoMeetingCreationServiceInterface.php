<?php

namespace App\Contracts;

use App\Models\Academy;
use App\Models\QuranSession;

/**
 * Auto Meeting Creation Service Interface
 *
 * Defines the contract for automatic meeting creation service.
 * This service handles the scheduled creation of LiveKit meeting rooms
 * for sessions based on academy-specific video settings.
 *
 * Key responsibilities:
 * - Creating meetings for eligible sessions across all academies
 * - Respecting academy-specific timing and configuration settings
 * - Cleaning up expired meetings
 * - Providing statistics and testing capabilities
 */
interface AutoMeetingCreationServiceInterface
{
    /**
     * Create meetings for all eligible sessions across all active academies.
     *
     * This is the main entry point for the scheduled job that runs periodically.
     * Processes all active academies and creates meetings for eligible sessions.
     *
     * @return array Results summary with counts and errors
     *               - total_academies_processed: Number of academies checked
     *               - total_sessions_processed: Number of sessions evaluated
     *               - meetings_created: Number of meetings successfully created
     *               - meetings_failed: Number of failed meeting creations
     *               - errors: Array of error details by academy
     */
    public function createMeetingsForAllAcademies(): array;

    /**
     * Create meetings for eligible sessions in a specific academy.
     *
     * Processes a single academy, checking its video settings and creating
     * meetings for all eligible sessions within the configured time window.
     *
     * @param  Academy  $academy  The academy to process
     * @return array Results summary for this academy
     *               - academy_id: The academy ID
     *               - academy_name: The academy name
     *               - sessions_processed: Number of sessions checked
     *               - meetings_created: Number of meetings created
     *               - meetings_failed: Number of failed creations
     *               - errors: Array of error details
     */
    public function createMeetingsForAcademy(Academy $academy): array;

    /**
     * Clean up expired meetings that should be ended.
     *
     * Finds sessions with active meetings that have exceeded their scheduled
     * duration plus buffer time, and ends those meetings.
     *
     * @return array Results summary
     *               - sessions_checked: Number of sessions evaluated
     *               - meetings_ended: Number of meetings successfully ended
     *               - meetings_failed_to_end: Number of failed meeting closures
     *               - errors: Array of error details
     */
    public function cleanupExpiredMeetings(): array;

    /**
     * Get statistics about auto meeting creation.
     *
     * Provides insights into the auto-meeting system including:
     * - Total auto-generated meetings
     * - Currently active meetings
     * - Meetings created today/this week
     * - Academies with auto-creation enabled
     *
     * @return array Statistical data
     *               - total_auto_generated_meetings: Lifetime count
     *               - active_meetings: Currently active meetings
     *               - meetings_created_today: Today's count
     *               - meetings_created_this_week: This week's count
     *               - academies_with_auto_creation_enabled: Count of enabled academies
     */
    public function getStatistics(): array;

    /**
     * Test meeting creation for a specific session.
     *
     * Useful for debugging and testing the meeting creation flow
     * without waiting for the scheduled job.
     *
     * @param  QuranSession  $session  The session to create a test meeting for
     * @return array Test result
     *               - success: Boolean indicating success/failure
     *               - message: Human-readable result message
     *               - session_id: The session ID
     *               - meeting_url: Created meeting URL (if successful)
     *               - room_name: Created room name (if successful)
     *               - error: Error message (if failed)
     */
    public function testMeetingCreation(QuranSession $session): array;
}
