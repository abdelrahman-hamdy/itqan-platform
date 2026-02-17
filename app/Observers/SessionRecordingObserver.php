<?php

namespace App\Observers;

use App\Enums\RecordingStatus;
use Throwable;
use App\Models\SessionRecording;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * SessionRecording Observer
 *
 * Handles cleanup of recording storage files when recordings are deleted.
 * Supports multiple storage backends:
 * - S3 (via configured disk in livekit.recordings.disk)
 * - Local LiveKit server filesystem (remote files served via nginx)
 *
 * File deletion is best-effort: failures are logged but do not prevent
 * the database record from being deleted/updated.
 */
class SessionRecordingObserver
{
    /**
     * Handle the SessionRecording "deleting" event (before hard delete).
     *
     * Attempts to remove the associated storage file from S3 or configured disk.
     * This fires on actual model deletion (e.g., $recording->delete() or forceDelete()).
     */
    public function deleting(SessionRecording $recording): void
    {
        $this->deleteStorageFile($recording);
    }

    /**
     * Handle the SessionRecording "updating" event.
     *
     * When a recording's status is changed to "deleted" (soft delete via markAsDeleted),
     * clean up the storage file if configured to do so.
     */
    public function updating(SessionRecording $recording): void
    {
        // Only act when status is being changed to 'deleted'
        if (! $recording->isDirty('status')) {
            return;
        }

        $newStatus = $recording->status;

        // Handle both string and enum values
        $statusValue = $newStatus instanceof RecordingStatus
            ? $newStatus->value
            : $newStatus;

        if ($statusValue !== 'deleted') {
            return;
        }

        // Check if file deletion on status change is enabled
        if (! config('livekit.recordings.delete_files_on_delete', true)) {
            Log::info('Recording file deletion skipped (disabled by config)', [
                'recording_id' => $recording->id,
                'file_path' => $recording->file_path,
            ]);

            return;
        }

        $this->deleteStorageFile($recording);
    }

    /**
     * Delete the recording file from storage.
     *
     * Supports three storage scenarios:
     * 1. S3 disk configured (livekit.recordings.disk) - deletes from S3
     * 2. Local storage file - deletes from default disk
     * 3. Remote LiveKit server file - logs warning (cannot delete remotely)
     *
     * All errors are caught and logged without interrupting the model operation.
     */
    protected function deleteStorageFile(SessionRecording $recording): void
    {
        if (empty($recording->file_path)) {
            return;
        }

        try {
            $diskName = config('livekit.recordings.disk');

            // Scenario 1: S3 or configured disk
            if (! empty($diskName)) {
                $this->deleteFromDisk($recording, $diskName);

                return;
            }

            // Scenario 2: Local file (not a remote LiveKit server path)
            if (! $recording->isRemoteFile()) {
                $this->deleteFromDefaultDisk($recording);

                return;
            }

            // Scenario 3: Remote file on LiveKit server
            // We cannot delete files from the LiveKit server directly.
            // These must be cleaned up via a separate process (SSH, cron, etc.)
            Log::info('Recording file on remote LiveKit server cannot be auto-deleted', [
                'recording_id' => $recording->id,
                'file_path' => $recording->file_path,
                'remote_url' => $recording->getRemoteUrl(),
            ]);

        } catch (Throwable $e) {
            // Log but do not prevent the model operation from completing
            Log::error('Failed to delete recording storage file', [
                'recording_id' => $recording->id,
                'file_path' => $recording->file_path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete recording file from a specific disk (e.g., S3).
     */
    protected function deleteFromDisk(SessionRecording $recording, string $diskName): void
    {
        $disk = Storage::disk($diskName);
        $filePath = $recording->file_path;

        // Normalize the path: remove leading slashes for S3 compatibility
        $normalizedPath = ltrim($filePath, '/');

        if ($disk->exists($normalizedPath)) {
            $deleted = $disk->delete($normalizedPath);

            if ($deleted) {
                Log::info('Recording file deleted from storage disk', [
                    'recording_id' => $recording->id,
                    'disk' => $diskName,
                    'file_path' => $normalizedPath,
                ]);
            } else {
                Log::warning('Recording file deletion returned false', [
                    'recording_id' => $recording->id,
                    'disk' => $diskName,
                    'file_path' => $normalizedPath,
                ]);
            }
        } else {
            Log::info('Recording file not found on storage disk (may have been already deleted)', [
                'recording_id' => $recording->id,
                'disk' => $diskName,
                'file_path' => $normalizedPath,
            ]);
        }
    }

    /**
     * Delete recording file from the default storage disk.
     */
    protected function deleteFromDefaultDisk(SessionRecording $recording): void
    {
        $filePath = $recording->file_path;

        if (Storage::exists($filePath)) {
            $deleted = Storage::delete($filePath);

            if ($deleted) {
                Log::info('Recording file deleted from default storage', [
                    'recording_id' => $recording->id,
                    'file_path' => $filePath,
                ]);
            } else {
                Log::warning('Recording file deletion from default storage returned false', [
                    'recording_id' => $recording->id,
                    'file_path' => $filePath,
                ]);
            }
        } else {
            Log::info('Recording file not found on default storage (may have been already deleted)', [
                'recording_id' => $recording->id,
                'file_path' => $filePath,
            ]);
        }
    }
}
