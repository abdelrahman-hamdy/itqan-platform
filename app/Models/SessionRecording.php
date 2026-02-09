<?php

namespace App\Models;

use App\Enums\RecordingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * SessionRecording Model
 *
 * Polymorphic recording model that can record any session type implementing RecordingCapable
 * Currently used for: InteractiveCourseSession
 * Future: Can be extended to QuranSession, AcademicSession
 *
 * @property int $id
 * @property string $recordable_type Model class of the recorded session
 * @property int $recordable_id ID of the recorded session
 * @property string $recording_id LiveKit Egress recording ID
 * @property string $meeting_room LiveKit room name
 * @property string $status recording|processing|completed|failed|deleted
 * @property \Carbon\Carbon $started_at When recording started
 * @property \Carbon\Carbon|null $ended_at When recording ended
 * @property int|null $duration Duration in seconds
 * @property string|null $file_path Path to recording file
 * @property string|null $file_name File name
 * @property int|null $file_size File size in bytes
 * @property string $file_format File format (mp4, webm, etc.)
 * @property array|null $metadata Additional metadata
 * @property string|null $processing_error Error message if failed
 * @property \Carbon\Carbon|null $processed_at When processing completed
 * @property \Carbon\Carbon|null $completed_at When recording became available
 */
class SessionRecording extends Model
{
    use HasFactory;

    protected $fillable = [
        'recordable_type',
        'recordable_id',
        'recording_id',
        'meeting_room',
        'status',
        'started_at',
        'ended_at',
        'duration',
        'file_path',
        'file_name',
        'file_size',
        'file_format',
        'metadata',
        'processing_error',
        'processed_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
        'duration' => 'integer',
        'file_size' => 'integer',
        'status' => RecordingStatus::class,
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the recordable entity (session) that owns this recording
     */
    public function recordable(): MorphTo
    {
        return $this->morphTo();
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope: Get completed recordings
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', RecordingStatus::COMPLETED->value);
    }

    /**
     * Scope: Get recordings in progress
     */
    public function scopeRecording($query)
    {
        return $query->where('status', RecordingStatus::RECORDING->value);
    }

    /**
     * Scope: Get recordings being processed
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', RecordingStatus::PROCESSING->value);
    }

    /**
     * Scope: Get failed recordings
     */
    public function scopeFailed($query)
    {
        return $query->where('status', RecordingStatus::FAILED->value);
    }

    /**
     * Scope: Filter by recordable type
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('recordable_type', $type);
    }

    /**
     * Scope: Get recent recordings
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ========================================
    // STATUS HELPER METHODS
    // ========================================

    /**
     * Check if recording is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === RecordingStatus::COMPLETED;
    }

    /**
     * Check if recording is in progress
     */
    public function isRecording(): bool
    {
        return $this->status === RecordingStatus::RECORDING;
    }

    /**
     * Check if recording is being processed
     */
    public function isProcessing(): bool
    {
        return $this->status === RecordingStatus::PROCESSING;
    }

    /**
     * Check if recording has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === RecordingStatus::FAILED;
    }

    /**
     * Check if recording has been deleted
     */
    public function isDeleted(): bool
    {
        return $this->status === RecordingStatus::DELETED;
    }

    /**
     * Check if recording file is available
     */
    public function isAvailable(): bool
    {
        return $this->isCompleted() && ! empty($this->file_path);
    }

    // ========================================
    // FORMATTING METHODS
    // ========================================

    /**
     * Get formatted duration (HH:MM:SS or MM:SS)
     */
    public function getFormattedDurationAttribute(): string
    {
        if (! $this->duration) {
            return '00:00';
        }

        $hours = intval($this->duration / 3600);
        $minutes = intval(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get formatted file size (B, KB, MB, GB)
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return '0 B';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get display name for recording
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->file_name) {
            return $this->file_name;
        }

        // Fallback: Generate name from metadata
        $sessionType = $this->metadata['session_type'] ?? 'Session';
        $date = $this->started_at->format('Y-m-d H:i');

        return sprintf('تسجيل %s - %s', $sessionType, $date);
    }

    /**
     * Get status label in Arabic
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'recording' => 'جاري التسجيل',
            'processing' => 'جاري المعالجة',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            'deleted' => 'محذوف',
            default => $this->status,
        };
    }

    /**
     * Get status color for UI display
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'recording' => 'danger', // Red - recording in progress
            'processing' => 'warning', // Yellow - being processed
            'completed' => 'success', // Green - ready
            'failed' => 'danger', // Red - error
            'deleted' => 'gray', // Gray - deleted
            default => 'gray',
        };
    }

    // ========================================
    // ACTION METHODS
    // ========================================

    /**
     * Mark recording as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => RecordingStatus::PROCESSING,
            'ended_at' => $this->ended_at ?? now(),
        ]);
    }

    /**
     * Mark recording as completed
     */
    public function markAsCompleted(array $fileData): void
    {
        $this->update([
            'status' => RecordingStatus::COMPLETED,
            'file_path' => $fileData['file_path'] ?? $this->file_path,
            'file_name' => $fileData['file_name'] ?? $this->file_name,
            'file_size' => $fileData['file_size'] ?? $this->file_size,
            'duration' => $fileData['duration'] ?? $this->duration,
            'processed_at' => now(),
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark recording as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => RecordingStatus::FAILED,
            'processing_error' => $error,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark recording as deleted
     */
    public function markAsDeleted(): void
    {
        $this->update([
            'status' => RecordingStatus::DELETED,
        ]);
    }

    /**
     * Get download URL for recording
     */
    public function getDownloadUrl(): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        // URL will be routed through Laravel controller for authentication
        return route('recordings.download', ['recordingId' => $this->id]);
    }

    /**
     * Get stream URL for recording playback
     */
    public function getStreamUrl(): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        // URL will be routed through Laravel controller for authentication
        return route('recordings.stream', ['recordingId' => $this->id]);
    }

    // ========================================
    // REMOTE FILE METHODS
    // ========================================

    /**
     * Check if this recording is stored on a remote server (LiveKit server)
     * Remote files have paths starting with / (server-local path)
     * Local files are stored in Laravel's storage system
     */
    public function isRemoteFile(): bool
    {
        if (empty($this->file_path)) {
            return false;
        }

        // Remote files have paths starting with /recordings (LiveKit server path)
        // Local files would use storage paths or full URLs
        return str_starts_with($this->file_path, '/recordings') ||
               str_starts_with($this->file_path, '/');
    }

    /**
     * Get the full remote URL for this recording
     * Combines the base URL from config with the file path
     */
    public function getRemoteUrl(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }

        $baseUrl = config('livekit.recordings.base_url');

        if (empty($baseUrl)) {
            return null;
        }

        // If file_path already starts with /recordings, remove it to avoid duplication
        $relativePath = $this->file_path;
        if (str_starts_with($relativePath, '/recordings')) {
            $relativePath = substr($relativePath, strlen('/recordings'));
        }

        // Ensure path starts with /
        if (! str_starts_with($relativePath, '/')) {
            $relativePath = '/'.$relativePath;
        }

        return rtrim($baseUrl, '/').$relativePath;
    }

    /**
     * Get the direct access URL for this recording
     * This is the URL that can be used for direct playback/download
     */
    public function getDirectUrl(): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        if ($this->isRemoteFile()) {
            return $this->getRemoteUrl();
        }

        // For local files, return null (should use stream/download routes)
        return null;
    }
}
