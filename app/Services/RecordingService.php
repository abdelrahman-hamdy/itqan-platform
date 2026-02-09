<?php

namespace App\Services;

use App\Contracts\RecordingCapable;
use App\Contracts\RecordingServiceInterface;
use App\Enums\RecordingStatus;
use App\Models\SessionRecording;
use Illuminate\Support\Facades\Log;

/**
 * RecordingService
 *
 * Handles business logic for session recording:
 * - Starting and stopping recordings
 * - Processing webhook events from LiveKit Egress
 * - Managing recording lifecycle
 *
 * This service works with any session implementing RecordingCapable interface
 */
class RecordingService implements RecordingServiceInterface
{
    protected LiveKitService $liveKitService;

    public function __construct(LiveKitService $liveKitService)
    {
        $this->liveKitService = $liveKitService;
    }

    /**
     * Start recording a session
     *
     * @param  RecordingCapable  $session  The session to record
     * @return SessionRecording The created recording record
     *
     * @throws \Exception If recording cannot be started
     */
    public function startRecording(RecordingCapable $session): SessionRecording
    {
        // Validate session can be recorded
        if (! $session->canBeRecorded()) {
            throw new \Exception('Session cannot be recorded at this time');
        }

        // Check if already recording
        if ($session->isRecording()) {
            throw new \Exception('Session is already being recorded');
        }

        try {
            // Get recording configuration from session
            $config = $session->getRecordingConfiguration();

            // Start recording via LiveKit Egress API
            $egressResponse = $this->liveKitService->startRecording(
                $config['room_name'],
                $config
            );

            // Create recording record
            $recording = SessionRecording::create([
                'recordable_type' => $session->getMorphClass(),
                'recordable_id' => $session->id,
                'recording_id' => $egressResponse['egress_id'],
                'meeting_room' => $config['room_name'],
                'status' => RecordingStatus::RECORDING->value,
                'started_at' => now(),
                'metadata' => $config['metadata'] ?? [],
                'file_format' => $config['preset'] === 'AUDIO_ONLY' ? 'm4a' : 'mp4',
            ]);

            Log::info('Recording started successfully', [
                'session_type' => get_class($session),
                'session_id' => $session->id,
                'recording_id' => $recording->id,
                'egress_id' => $egressResponse['egress_id'],
                'room_name' => $config['room_name'],
            ]);

            return $recording;

        } catch (\Exception $e) {
            Log::error('Failed to start recording', [
                'session_type' => get_class($session),
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Failed to start recording: '.$e->getMessage());
        }
    }

    /**
     * Stop an active recording
     *
     * @param  SessionRecording  $recording  The recording to stop
     * @return bool Whether recording was stopped successfully
     */
    public function stopRecording(SessionRecording $recording): bool
    {
        // Validate recording can be stopped
        if (! $recording->isRecording()) {
            Log::warning('Attempted to stop non-active recording', [
                'recording_id' => $recording->id,
                'status' => $recording->status,
            ]);

            return false;
        }

        try {
            // Stop recording via LiveKit Egress API
            $stopped = $this->liveKitService->stopRecording($recording->recording_id);

            if ($stopped) {
                // Update recording status to processing
                // Will be marked as completed when webhook arrives
                $recording->markAsProcessing();

                Log::info('Recording stopped successfully', [
                    'recording_id' => $recording->id,
                    'egress_id' => $recording->recording_id,
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to stop recording', [
                'recording_id' => $recording->id,
                'egress_id' => $recording->recording_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process webhook event from LiveKit Egress
     *
     * @param  array  $webhookData  Webhook payload from LiveKit
     * @return bool Whether webhook was processed successfully
     */
    public function processEgressWebhook(array $webhookData): bool
    {
        $event = $webhookData['event'] ?? null;

        if ($event !== 'egress_ended') {
            // Only process egress_ended events for now
            return false;
        }

        try {
            $egressInfo = $webhookData['egressInfo'] ?? [];
            $egressId = $egressInfo['egressId'] ?? null;

            if (! $egressId) {
                Log::warning('Egress webhook missing egressId', ['data' => $webhookData]);

                return false;
            }

            // Find recording by egress ID
            $recording = SessionRecording::where('recording_id', $egressId)->first();

            if (! $recording) {
                Log::warning('Recording not found for egress webhook', [
                    'egress_id' => $egressId,
                ]);

                return false;
            }

            // Check egress status
            $status = $egressInfo['status'] ?? null;
            $error = $egressInfo['error'] ?? null;

            if ($status === 'EGRESS_COMPLETE') {
                // Extract file information
                $fileInfo = $this->extractFileInfoFromWebhook($egressInfo);

                $recording->markAsCompleted($fileInfo);

                Log::info('Recording completed via webhook', [
                    'recording_id' => $recording->id,
                    'egress_id' => $egressId,
                    'file_path' => $fileInfo['file_path'] ?? null,
                ]);

                return true;

            } elseif ($status === 'EGRESS_FAILED' || $error) {
                $errorMessage = $error ?? 'Unknown error';

                $recording->markAsFailed($errorMessage);

                Log::error('Recording failed via webhook', [
                    'recording_id' => $recording->id,
                    'egress_id' => $egressId,
                    'error' => $errorMessage,
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Error processing egress webhook', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData,
            ]);

            return false;
        }
    }

    /**
     * Extract file information from webhook payload
     *
     * @param  array  $egressInfo  Egress info from webhook
     * @return array File information
     */
    protected function extractFileInfoFromWebhook(array $egressInfo): array
    {
        // Extract file info from different possible locations in webhook
        $fileList = $egressInfo['fileResults'] ?? $egressInfo['file'] ?? [];

        if (empty($fileList)) {
            return [
                'file_path' => null,
                'file_name' => null,
                'file_size' => null,
                'duration' => null,
            ];
        }

        // Get first file (for room composite, there's usually only one)
        $file = is_array($fileList) && isset($fileList[0]) ? $fileList[0] : $fileList;

        return [
            'file_path' => $file['filename'] ?? $file['location'] ?? null,
            'file_name' => basename($file['filename'] ?? ''),
            'file_size' => $file['size'] ?? null,
            'duration' => $file['duration'] ?? $egressInfo['duration'] ?? null,
        ];
    }

    /**
     * Get all recordings for a session
     */
    public function getSessionRecordings(RecordingCapable $session): \Illuminate\Database\Eloquent\Collection
    {
        return $session->getRecordings();
    }

    /**
     * Delete a recording (mark as deleted and remove storage file)
     *
     * Storage file cleanup is handled automatically by SessionRecordingObserver
     * when the status is changed to 'deleted' via markAsDeleted().
     *
     * @param  bool  $removeFile  Whether to remove the physical file (default: false for backward compatibility)
     *                            Note: File deletion is now controlled by config('livekit.recordings.delete_files_on_delete')
     *                            and handled by SessionRecordingObserver regardless of this parameter.
     */
    public function deleteRecording(SessionRecording $recording, bool $removeFile = false): bool
    {
        try {
            // markAsDeleted() triggers the SessionRecordingObserver::updating() event
            // which handles storage file cleanup based on config settings
            $recording->markAsDeleted();

            Log::info('Recording deleted', [
                'recording_id' => $recording->id,
                'file_path' => $recording->file_path,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete recording', [
                'recording_id' => $recording->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get recording statistics
     *
     * @param  array  $filters  Optional filters (session_type, date_range, status)
     * @return array Statistics data
     */
    public function getRecordingStatistics(array $filters = []): array
    {
        $query = SessionRecording::query();

        // Apply filters
        if (isset($filters['session_type'])) {
            $query->where('recordable_type', $filters['session_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $recordings = $query->get();

        return [
            'total_count' => $recordings->count(),
            'completed_count' => $recordings->where('status', RecordingStatus::COMPLETED->value)->count(),
            'recording_count' => $recordings->where('status', RecordingStatus::RECORDING->value)->count(),
            'processing_count' => $recordings->where('status', RecordingStatus::PROCESSING->value)->count(),
            'failed_count' => $recordings->where('status', RecordingStatus::FAILED->value)->count(),
            'total_size_bytes' => $recordings->where('status', RecordingStatus::COMPLETED->value)->sum('file_size'),
            'total_size_formatted' => $this->formatBytes($recordings->where('status', RecordingStatus::COMPLETED->value)->sum('file_size')),
            'total_duration_seconds' => $recordings->where('status', RecordingStatus::COMPLETED->value)->sum('duration'),
            'total_duration_formatted' => $this->formatDuration($recordings->where('status', RecordingStatus::COMPLETED->value)->sum('duration')),
            'average_file_size_bytes' => $recordings->where('status', RecordingStatus::COMPLETED->value)->avg('file_size'),
            'average_duration_seconds' => $recordings->where('status', RecordingStatus::COMPLETED->value)->avg('duration'),
        ];
    }

    /**
     * Format bytes to human-readable size
     */
    protected function formatBytes(?int $bytes): string
    {
        if (! $bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = $bytes;

        for ($i = 0; $value > 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        return round($value, 2).' '.$units[$i];
    }

    /**
     * Format duration in seconds to HH:MM:SS
     */
    protected function formatDuration(?int $seconds): string
    {
        if (! $seconds) {
            return '00:00';
        }

        $hours = intval($seconds / 3600);
        $minutes = intval(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%02d:%02d', $minutes, $secs);
    }
}
