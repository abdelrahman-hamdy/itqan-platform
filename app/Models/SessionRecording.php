<?php

namespace App\Models;

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
    ];

    protected $attributes = [
        'status' => 'recording',
        'file_format' => 'mp4',
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
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Get recordings in progress
     */
    public function scopeRecording($query)
    {
        return $query->where('status', 'recording');
    }

    /**
     * Scope: Get recordings being processed
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope: Get failed recordings
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
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
        return $this->status === 'completed';
    }

    /**
     * Check if recording is in progress
     */
    public function isRecording(): bool
    {
        return $this->status === 'recording';
    }

    /**
     * Check if recording is being processed
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if recording has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if recording has been deleted
     */
    public function isDeleted(): bool
    {
        return $this->status === 'deleted';
    }

    /**
     * Check if recording file is available
     */
    public function isAvailable(): bool
    {
        return $this->isCompleted() && !empty($this->file_path);
    }

    // ========================================
    // FORMATTING METHODS
    // ========================================

    /**
     * Get formatted duration (HH:MM:SS or MM:SS)
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
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
        if (!$this->file_size) {
            return '0 B';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
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
            'status' => 'processing',
            'ended_at' => $this->ended_at ?? now(),
        ]);
    }

    /**
     * Mark recording as completed
     */
    public function markAsCompleted(array $fileData): void
    {
        $this->update([
            'status' => 'completed',
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
            'status' => 'failed',
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
            'status' => 'deleted',
        ]);
    }

    /**
     * Get download URL for recording
     */
    public function getDownloadUrl(): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        // URL will be routed through Laravel controller for authentication
        return route('recordings.download', ['recording' => $this->id]);
    }

    /**
     * Get stream URL for recording playback
     */
    public function getStreamUrl(): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        // URL will be routed through Laravel controller for authentication
        return route('recordings.stream', ['recording' => $this->id]);
    }
}
