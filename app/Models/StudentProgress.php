<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recorded_course_id',
        'course_section_id',
        'lesson_id',
        'progress_type',
        'progress_percentage',
        'watch_time_seconds',
        'total_time_seconds',
        'is_completed',
        'completed_at',
        'last_accessed_at',
        'current_position_seconds',
        'quiz_score',
        'quiz_attempts',
        'notes',
        'bookmarked_at',
        'rating',
        'review_text'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'progress_percentage' => 'decimal:2',
        'watch_time_seconds' => 'integer',
        'total_time_seconds' => 'integer',
        'current_position_seconds' => 'integer',
        'quiz_score' => 'decimal:2',
        'quiz_attempts' => 'integer',
        'rating' => 'integer',
        'completed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'bookmarked_at' => 'datetime'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recordedCourse(): BelongsTo
    {
        return $this->belongsTo(RecordedCourse::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeInProgress($query)
    {
        return $query->where('is_completed', false)
                    ->where('progress_percentage', '>', 0);
    }

    public function scopeNotStarted($query)
    {
        return $query->where('progress_percentage', 0);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('progress_type', $type);
    }

    public function scopeBookmarked($query)
    {
        return $query->whereNotNull('bookmarked_at');
    }

    public function scopeWithRating($query)
    {
        return $query->whereNotNull('rating');
    }

    // Accessors
    public function getWatchTimeFormattedAttribute(): string
    {
        return $this->formatDuration($this->watch_time_seconds);
    }

    public function getTotalTimeFormattedAttribute(): string
    {
        return $this->formatDuration($this->total_time_seconds);
    }

    public function getCurrentPositionFormattedAttribute(): string
    {
        return $this->formatDuration($this->current_position_seconds);
    }

    public function getProgressStatusAttribute(): string
    {
        if ($this->is_completed) {
            return 'مكتمل';
        }
        
        if ($this->progress_percentage > 0) {
            return 'قيد المشاهدة';
        }
        
        return 'لم يبدأ';
    }

    public function getProgressBadgeColorAttribute(): string
    {
        if ($this->is_completed) {
            return 'success';
        }
        
        if ($this->progress_percentage > 0) {
            return 'warning';
        }
        
        return 'secondary';
    }

    public function getIsBookmarkedAttribute(): bool
    {
        return !is_null($this->bookmarked_at);
    }

    public function getCompletionTimeAttribute(): ?string
    {
        if (!$this->completed_at) {
            return null;
        }
        
        return $this->completed_at->diffForHumans();
    }

    // Methods
    public function updateProgress(int $currentSeconds, int $totalSeconds = null): self
    {
        $totalSeconds = $totalSeconds ?? $this->total_time_seconds ?? $currentSeconds;
        $progressPercentage = $totalSeconds > 0 ? min(100, ($currentSeconds / $totalSeconds) * 100) : 0;
        
        $this->update([
            'current_position_seconds' => $currentSeconds,
            'watch_time_seconds' => max($this->watch_time_seconds ?? 0, $currentSeconds),
            'total_time_seconds' => $totalSeconds,
            'progress_percentage' => $progressPercentage,
            'last_accessed_at' => now(),
            'is_completed' => $progressPercentage >= 90, // Consider 90% as completed
            'completed_at' => $progressPercentage >= 90 && !$this->completed_at ? now() : $this->completed_at
        ]);
        
        return $this;
    }

    public function markAsCompleted(): self
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'progress_percentage' => 100,
            'last_accessed_at' => now()
        ]);
        
        return $this;
    }

    public function addBookmark(): self
    {
        $this->update([
            'bookmarked_at' => now()
        ]);
        
        return $this;
    }

    public function removeBookmark(): self
    {
        $this->update([
            'bookmarked_at' => null
        ]);
        
        return $this;
    }

    public function toggleBookmark(): self
    {
        if ($this->is_bookmarked) {
            return $this->removeBookmark();
        }
        
        return $this->addBookmark();
    }

    public function addRating(int $rating, string $reviewText = null): self
    {
        $this->update([
            'rating' => $rating,
            'review_text' => $reviewText
        ]);
        
        return $this;
    }

    public function addNote(string $note): self
    {
        $existingNotes = $this->notes ? json_decode($this->notes, true) : [];
        $existingNotes[] = [
            'text' => $note,
            'timestamp' => now()->toISOString(),
            'position_seconds' => $this->current_position_seconds
        ];
        
        $this->update([
            'notes' => json_encode($existingNotes)
        ]);
        
        return $this;
    }

    public function getNotesArray(): array
    {
        return $this->notes ? json_decode($this->notes, true) : [];
    }

    public function resetProgress(): self
    {
        $this->update([
            'progress_percentage' => 0,
            'watch_time_seconds' => 0,
            'current_position_seconds' => 0,
            'is_completed' => false,
            'completed_at' => null,
            'quiz_score' => null,
            'quiz_attempts' => 0,
            'notes' => null,
            'bookmarked_at' => null,
            'rating' => null,
            'review_text' => null
        ]);
        
        return $this;
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' ثانية';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return $minutes . ' دقيقة' . ($remainingSeconds > 0 ? ' و ' . $remainingSeconds . ' ثانية' : '');
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . ' ساعة' . 
               ($remainingMinutes > 0 ? ' و ' . $remainingMinutes . ' دقيقة' : '') .
               ($remainingSeconds > 0 ? ' و ' . $remainingSeconds . ' ثانية' : '');
    }

    // Static methods
    public static function getOrCreate(User $user, RecordedCourse $course, Lesson $lesson = null): self
    {
        $attributes = [
            'user_id' => $user->id,
            'recorded_course_id' => $course->id,
        ];
        
        if ($lesson) {
            $attributes['lesson_id'] = $lesson->id;
            $attributes['course_section_id'] = $lesson->course_section_id;
            $attributes['progress_type'] = 'lesson';
        } else {
            $attributes['progress_type'] = 'course';
        }
        
        return self::firstOrCreate($attributes, [
            'progress_percentage' => 0,
            'watch_time_seconds' => 0,
            'total_time_seconds' => $lesson ? $lesson->video_duration_seconds : 0,
            'is_completed' => false,
            'last_accessed_at' => now()
        ]);
    }
} 