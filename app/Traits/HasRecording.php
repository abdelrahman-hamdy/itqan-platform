<?php

namespace App\Traits;

use App\Models\SessionRecording;
use App\Models\User;
use App\Services\RecordingService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Log;

/**
 * Trait for models that support session recording
 *
 * Provides common recording functionality that can be shared across
 * different session types (InteractiveCourseSession, and potentially
 * QuranSession, AcademicSession in the future)
 *
 * IMPORTANT: Models using this trait must implement RecordingCapable interface
 */
trait HasRecording
{
    /**
     * Polymorphic relationship to session recordings
     */
    public function recordings(): MorphMany
    {
        return $this->morphMany(SessionRecording::class, 'recordable');
    }

    /**
     * Check if recording is enabled for this session
     */
    public function isRecordingEnabled(): bool
    {
        // Default implementation - child classes can override
        // For InteractiveCourseSession: check course's recording_enabled
        if (method_exists($this, 'course') && $this->course) {
            return (bool) $this->course->recording_enabled;
        }

        return false;
    }

    /**
     * Check if this session can be recorded
     */
    public function canBeRecorded(): bool
    {
        // Must have recording enabled
        if (!$this->isRecordingEnabled()) {
            return false;
        }

        // Must have a meeting room created
        if (!$this->meeting_room_name) {
            return false;
        }

        // Session must be active (ready or ongoing)
        if (!in_array($this->status?->value, ['ready', 'ongoing'])) {
            return false;
        }

        // If already recording, cannot start another
        if ($this->isRecording()) {
            return false;
        }

        return true;
    }

    /**
     * Get the recording room name (same as meeting room)
     */
    public function getRecordingRoomName(): ?string
    {
        return $this->meeting_room_name;
    }

    /**
     * Get recording configuration for this session
     */
    public function getRecordingConfiguration(): array
    {
        $metadata = $this->getRecordingMetadata();

        return [
            'room_name' => $this->getRecordingRoomName(),
            'filename' => $this->getRecordingFilename(),
            'storage_path' => $this->getRecordingStoragePath(),
            'metadata' => $metadata,
            'layout' => 'grid', // Layout type for composite recording
            'audio_only' => false,
            'video_only' => false,
            'preset' => 'HD',
        ];
    }

    /**
     * Get the storage path for recordings
     */
    public function getRecordingStoragePath(): string
    {
        $sessionType = $this->getMeetingSessionType();
        $sessionId = $this->id;

        // Example: /recordings/interactive/2024/11/session-123
        return sprintf(
            '/recordings/%s/%s/%s/session-%s',
            $sessionType,
            now()->format('Y'),
            now()->format('m'),
            $sessionId
        );
    }

    /**
     * Get the base filename for recordings
     */
    public function getRecordingFilename(): string
    {
        $sessionType = $this->getMeetingSessionType();
        $sessionId = $this->id;
        $timestamp = now()->format('Y-m-d_His');

        // Example: interactive-session-123-2024-11-30_143025
        return sprintf(
            '%s-session-%s-%s',
            $sessionType,
            $sessionId,
            $timestamp
        );
    }

    /**
     * Check if recording is currently in progress
     */
    public function isRecording(): bool
    {
        return $this->recordings()
            ->whereIn('status', ['recording', 'processing'])
            ->exists();
    }

    /**
     * Get all recordings for this session
     */
    public function getRecordings(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->recordings()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the active/latest recording record
     */
    public function getActiveRecording(): ?SessionRecording
    {
        return $this->recordings()
            ->whereIn('status', ['recording', 'processing'])
            ->latest()
            ->first();
    }

    /**
     * Get the latest completed recording
     */
    public function getLatestCompletedRecording(): ?SessionRecording
    {
        return $this->recordings()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();
    }

    /**
     * Check if a user can start/stop recording for this session
     */
    public function canUserControlRecording(User $user): bool
    {
        // Must have permission to manage the meeting
        if (!method_exists($this, 'canUserManageMeeting')) {
            return false;
        }

        return $this->canUserManageMeeting($user);
    }

    /**
     * Check if a user can access recordings of this session
     */
    public function canUserAccessRecordings(User $user): bool
    {
        // Admins can access all recordings
        if (in_array($user->user_type, ['super_admin', 'admin'])) {
            return true;
        }

        // Teachers can access recordings they created/taught
        if ($user->user_type === 'academic_teacher' || $user->user_type === 'quran_teacher') {
            // Check if this is their session
            if (method_exists($this, 'canUserManageMeeting')) {
                return $this->canUserManageMeeting($user);
            }
        }

        // Students can access recordings if they're participants
        if ($user->user_type === 'student') {
            if (method_exists($this, 'isUserParticipant')) {
                return $this->isUserParticipant($user);
            }
        }

        return false;
    }

    /**
     * Get metadata to attach to recording
     */
    public function getRecordingMetadata(): array
    {
        $metadata = [
            'session_id' => $this->id,
            'session_type' => $this->getMeetingSessionType(),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'duration_minutes' => $this->duration_minutes,
        ];

        // Add academy information
        if (method_exists($this, 'getAcademy') && $this->getAcademy()) {
            $metadata['academy_id'] = $this->getAcademy()->id;
            $metadata['academy_name'] = $this->getAcademy()->name;
        }

        // Add session-specific metadata
        if (method_exists($this, 'getExtendedRecordingMetadata')) {
            $metadata = array_merge($metadata, $this->getExtendedRecordingMetadata());
        }

        return $metadata;
    }

    /**
     * Start recording this session
     *
     * @return SessionRecording The created recording record
     * @throws \Exception If recording cannot be started
     */
    public function startRecording(): SessionRecording
    {
        if (!$this->canBeRecorded()) {
            throw new \Exception('This session cannot be recorded at this time');
        }

        try {
            $recordingService = app(RecordingService::class);

            return $recordingService->startRecording($this);

        } catch (\Exception $e) {
            Log::error('Failed to start recording', [
                'session_type' => $this->getMeetingSessionType(),
                'session_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to start recording: ' . $e->getMessage());
        }
    }

    /**
     * Stop the active recording
     *
     * @return bool Whether recording was stopped successfully
     */
    public function stopRecording(): bool
    {
        $activeRecording = $this->getActiveRecording();

        if (!$activeRecording) {
            return false;
        }

        try {
            $recordingService = app(RecordingService::class);

            return $recordingService->stopRecording($activeRecording);

        } catch (\Exception $e) {
            Log::error('Failed to stop recording', [
                'session_type' => $this->getMeetingSessionType(),
                'session_id' => $this->id,
                'recording_id' => $activeRecording->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get recording statistics for this session
     */
    public function getRecordingStats(): array
    {
        $allRecordings = $this->getRecordings();

        return [
            'total_recordings' => $allRecordings->count(),
            'completed_recordings' => $allRecordings->where('status', 'completed')->count(),
            'failed_recordings' => $allRecordings->where('status', 'failed')->count(),
            'is_recording' => $this->isRecording(),
            'active_recording' => $this->getActiveRecording(),
            'latest_completed' => $this->getLatestCompletedRecording(),
            'total_size_bytes' => $allRecordings->where('status', 'completed')->sum('file_size'),
            'total_duration_minutes' => $allRecordings->where('status', 'completed')->sum('duration'),
        ];
    }
}
