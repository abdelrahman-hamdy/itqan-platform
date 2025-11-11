<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRecording extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'course_id',
        'teacher_id',
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
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'processed_at' => 'datetime',
        'metadata' => 'array',
        'duration' => 'integer',
        'file_size' => 'integer',
    ];

    protected $attributes = [
        'status' => 'recording',
        'file_format' => 'mp4',
    ];

    /**
     * Relationships
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourseSession::class, 'session_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourse::class, 'course_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'teacher_id');
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRecording($query)
    {
        return $query->where('status', 'recording');
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Helper methods
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRecording(): bool
    {
        return $this->status === 'recording';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

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

    public function getDisplayNameAttribute(): string
    {
        return $this->file_name ?? 'تسجيل الدورة - ' . $this->started_at->format('Y-m-d H:i');
    }
}