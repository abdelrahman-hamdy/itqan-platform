<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Models\SessionRecording;
use App\Models\User;

/**
 * Interface for models that support session recording
 *
 * This interface ensures consistent recording functionality across different session types
 * Currently implemented for: InteractiveCourseSession
 * Future: Can be extended to QuranSession, AcademicSession when needed
 */
interface RecordingCapable
{
    /**
     * Check if recording is enabled for this session
     *
     * @return bool Whether recording is enabled
     */
    public function isRecordingEnabled(): bool;

    /**
     * Check if this session can be recorded (considering permissions, status, etc.)
     *
     * @return bool Whether the session can be recorded
     */
    public function canBeRecorded(): bool;

    /**
     * Get the recording room name (typically same as meeting room)
     *
     * @return string|null The LiveKit room name for recording
     */
    public function getRecordingRoomName(): ?string;

    /**
     * Get recording configuration for this session
     *
     * @return array Configuration options for LiveKit Egress
     */
    public function getRecordingConfiguration(): array;

    /**
     * Get the storage path for recordings of this session
     *
     * @return string The directory path for storing recordings
     */
    public function getRecordingStoragePath(): string;

    /**
     * Get the base filename for recordings (without extension)
     *
     * @return string Base filename for recording files
     */
    public function getRecordingFilename(): string;

    /**
     * Check if recording is currently in progress
     *
     * @return bool Whether a recording is active
     */
    public function isRecording(): bool;

    /**
     * Get all completed recordings for this session
     */
    public function getRecordings(): Collection;

    /**
     * Get the active/latest recording record
     */
    public function getActiveRecording(): ?SessionRecording;

    /**
     * Check if a user can start/stop recording for this session
     *
     * @param  User  $user  The user to check
     * @return bool Whether the user can control recording
     */
    public function canUserControlRecording(User $user): bool;

    /**
     * Check if a user can access recordings of this session
     *
     * @param  User  $user  The user to check
     * @return bool Whether the user can view recordings
     */
    public function canUserAccessRecordings(User $user): bool;

    /**
     * Get metadata to attach to recording
     *
     * @return array Metadata for recording file
     */
    public function getRecordingMetadata(): array;
}
