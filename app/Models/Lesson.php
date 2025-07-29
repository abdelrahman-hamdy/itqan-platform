<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'recorded_course_id',
        'course_section_id',
        'title',
        'title_en',
        'description',
        'description_en',
        'lesson_code',
        'video_url',
        'video_duration_seconds',
        'video_size_mb',
        'video_quality',
        'transcript',
        'notes',
        'attachments',
        'order',
        'is_published',
        'is_free_preview',
        'is_downloadable',
        'lesson_type',
        'quiz_id',
        'assignment_requirements',
        'learning_objectives',
        'difficulty_level',
        'estimated_study_time_minutes',
        'view_count',
        'avg_rating',
        'total_comments',
        'created_by',
        'updated_by',
        'published_at'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_free_preview' => 'boolean',
        'is_downloadable' => 'boolean',
        'attachments' => 'array',
        'assignment_requirements' => 'array',
        'learning_objectives' => 'array',
        'video_duration_seconds' => 'integer',
        'video_size_mb' => 'decimal:2',
        'order' => 'integer',
        'view_count' => 'integer',
        'avg_rating' => 'decimal:1',
        'total_comments' => 'integer',
        'estimated_study_time_minutes' => 'integer',
        'published_at' => 'datetime'
    ];

    // Relationships
    public function recordedCourse(): BelongsTo
    {
        return $this->belongsTo(RecordedCourse::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(CourseQuiz::class, 'quiz_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LessonComment::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LessonNote::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeFreePreview($query)
    {
        return $query->where('is_free_preview', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('lesson_type', $type);
    }

    public function scopeVideoLessons($query)
    {
        return $query->where('lesson_type', 'video');
    }

    public function scopeQuizLessons($query)
    {
        return $query->where('lesson_type', 'quiz');
    }

    // Accessors
    public function getDurationFormattedAttribute(): string
    {
        if ($this->video_duration_seconds < 60) {
            return $this->video_duration_seconds . ' ثانية';
        }
        
        $minutes = floor($this->video_duration_seconds / 60);
        $seconds = $this->video_duration_seconds % 60;
        
        if ($minutes < 60) {
            return $minutes . ' دقيقة' . ($seconds > 0 ? ' و ' . $seconds . ' ثانية' : '');
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . ' ساعة' . 
               ($remainingMinutes > 0 ? ' و ' . $remainingMinutes . ' دقيقة' : '') .
               ($seconds > 0 ? ' و ' . $seconds . ' ثانية' : '');
    }

    public function getDurationMinutesAttribute(): int
    {
        return ceil($this->video_duration_seconds / 60);
    }

    public function getVideoSizeFormattedAttribute(): string
    {
        if ($this->video_size_mb < 1024) {
            return number_format($this->video_size_mb, 1) . ' MB';
        }
        
        return number_format($this->video_size_mb / 1024, 1) . ' GB';
    }

    public function getProgressPercentageAttribute(): float
    {
        if (!auth()->check()) {
            return 0;
        }
        
        $progress = $this->progress()
            ->where('user_id', auth()->id())
            ->first();
            
        return $progress ? $progress->progress_percentage : 0;
    }

    public function getIsCompletedAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }
        
        return $this->progress()
            ->where('user_id', auth()->id())
            ->where('is_completed', true)
            ->exists();
    }

    // Methods
    public function markAsWatched(User $user, int $watchTimeSeconds = null): LessonProgress
    {
        $progress = $this->progress()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'watch_time_seconds' => $watchTimeSeconds ?? $this->video_duration_seconds,
                'progress_percentage' => $watchTimeSeconds ? 
                    min(100, ($watchTimeSeconds / $this->video_duration_seconds) * 100) : 100,
                'is_completed' => $watchTimeSeconds ? 
                    $watchTimeSeconds >= ($this->video_duration_seconds * 0.9) : true,
                'completed_at' => $watchTimeSeconds && $watchTimeSeconds >= ($this->video_duration_seconds * 0.9) ? 
                    now() : null,
                'last_watched_at' => now()
            ]
        );

        // Update view count
        $this->increment('view_count');
        
        return $progress;
    }

    public function getNextLesson(): ?Lesson
    {
        return Lesson::where('recorded_course_id', $this->recorded_course_id)
            ->where('order', '>', $this->order)
            ->where('is_published', true)
            ->orderBy('order')
            ->first();
    }

    public function getPreviousLesson(): ?Lesson
    {
        return Lesson::where('recorded_course_id', $this->recorded_course_id)
            ->where('order', '<', $this->order)
            ->where('is_published', true)
            ->orderBy('order', 'desc')
            ->first();
    }

    public function isAccessibleBy(User $user): bool
    {
        // Check if lesson is free preview
        if ($this->is_free_preview) {
            return true;
        }
        
        // Check if user is enrolled in the course
        return $this->recordedCourse->isEnrolledBy($user);
    }

    public function updateStats(): void
    {
        $this->update([
            'avg_rating' => $this->comments()->avg('rating') ?? 0,
            'total_comments' => $this->comments()->count()
        ]);
    }
} 