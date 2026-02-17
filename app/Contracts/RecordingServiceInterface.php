<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Exception;
use App\Models\SessionRecording;

/**
 * Recording Service Interface
 *
 * Defines the contract for session recording management.
 * This service handles the lifecycle of session recordings including:
 * - Starting and stopping recordings via LiveKit Egress
 * - Processing webhook events from LiveKit
 * - Managing recording files and metadata
 * - Providing recording statistics
 *
 * Works with any session implementing the RecordingCapable interface.
 */
interface RecordingServiceInterface
{
    /**
     * Start recording a session.
     *
     * Initiates a new recording via LiveKit Egress and creates a SessionRecording
     * database record to track the recording lifecycle.
     *
     * @param  RecordingCapable  $session  The session to record
     * @return SessionRecording The created recording record
     *
     * @throws Exception If recording cannot be started (session not recordable, already recording, etc.)
     */
    public function startRecording(RecordingCapable $session): SessionRecording;

    /**
     * Stop an active recording.
     *
     * Sends stop command to LiveKit Egress and transitions recording to processing state.
     * The recording will be marked as completed when the webhook arrives.
     *
     * @param  SessionRecording  $recording  The recording to stop
     * @return bool True if recording was stopped successfully
     */
    public function stopRecording(SessionRecording $recording): bool;

    /**
     * Process webhook event from LiveKit Egress.
     *
     * Handles webhook payloads from LiveKit when recordings complete or fail.
     * Updates recording status and extracts file information.
     *
     * @param  array  $webhookData  Webhook payload from LiveKit
     * @return bool True if webhook was processed successfully
     */
    public function processEgressWebhook(array $webhookData): bool;

    /**
     * Get all recordings for a session.
     *
     * Returns all recording attempts for a session, including completed,
     * failed, and in-progress recordings.
     *
     * @param  RecordingCapable  $session  The session
     * @return Collection Collection of SessionRecording models
     */
    public function getSessionRecordings(RecordingCapable $session): Collection;

    /**
     * Delete a recording.
     *
     * Marks recording as deleted in database. Storage file cleanup is handled
     * automatically by SessionRecordingObserver when status changes to 'deleted'.
     * File deletion behavior is controlled by config('livekit.recordings.delete_files_on_delete').
     *
     * @param  SessionRecording  $recording  The recording to delete
     * @param  bool  $removeFile  Legacy parameter (kept for backward compatibility).
     *                            File deletion is now handled by the observer.
     * @return bool True if recording was deleted successfully
     */
    public function deleteRecording(SessionRecording $recording, bool $removeFile = false): bool;

    /**
     * Get recording statistics.
     *
     * Provides aggregate statistics about recordings with optional filtering.
     *
     * @param  array  $filters  Optional filters:
     *                          - session_type: Filter by session type (QuranSession, AcademicSession, etc.)
     *                          - status: Filter by recording status
     *                          - date_from: Start date filter
     *                          - date_to: End date filter
     * @return array Statistics including:
     *               - total_count: Total recordings
     *               - completed_count: Successfully completed recordings
     *               - recording_count: Currently recording
     *               - processing_count: Being processed
     *               - failed_count: Failed recordings
     *               - total_size_bytes: Total storage used in bytes
     *               - total_size_formatted: Human-readable storage size
     *               - total_duration_seconds: Total recording duration
     *               - total_duration_formatted: Human-readable total duration
     *               - average_file_size_bytes: Average recording size
     *               - average_duration_seconds: Average recording duration
     */
    public function getRecordingStatistics(array $filters = []): array;
}
