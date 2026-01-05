<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Lesson extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'recorded_course_id',
        'course_section_id',
        'title',
        'description',
        'video_url',
        'video_size_mb',
        'video_quality',
        'transcript',
        'attachments',
        'is_published',
        'is_free_preview',
        'is_downloadable',
        'quiz_id',
        'assignment_requirements',
        'learning_objectives',
        'view_count',
        'avg_rating',
        'total_comments',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_free_preview' => 'boolean',
        'is_downloadable' => 'boolean',
        'attachments' => 'array',
        'assignment_requirements' => 'array',
        'learning_objectives' => 'array',
        'video_size_mb' => 'decimal:2',
        'view_count' => 'integer',
        'avg_rating' => 'decimal:1',
        'total_comments' => 'integer',
        'published_at' => 'datetime',
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
        return $this->hasMany(StudentProgress::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('videos')
            ->singleFile()
            ->acceptsMimeTypes(['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo']);

        $this->addMediaCollection('thumbnails')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain']);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('id');
    }

    public function scopeFreePreview($query)
    {
        return $query->where('is_free_preview', true);
    }

    // Accessors
    public function getOrderAttribute(): int
    {
        // Calculate order dynamically based on position in course lessons
        // Use direct query to avoid circular reference
        $lessons = static::where('recorded_course_id', $this->recorded_course_id)
            ->pluck('id')
            ->toArray();
        $position = array_search($this->id, $lessons);

        return $position !== false ? $position + 1 : 0;
    }

    public function getVideoSizeFormattedAttribute(): string
    {
        if ($this->video_size_mb < 1024) {
            return number_format($this->video_size_mb, 1).' MB';
        }

        return number_format($this->video_size_mb / 1024, 1).' GB';
    }

    public function getProgressPercentageAttribute(): float
    {
        if (! auth()->check()) {
            return 0;
        }

        $progress = $this->progress()
            ->where('user_id', auth()->id())
            ->first();

        return $progress ? $progress->progress_percentage : 0;
    }

    public function getIsCompletedAttribute(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return $this->progress()
            ->where('user_id', auth()->id())
            ->where('is_completed', true)
            ->exists();
    }

    /**
     * Get comments/reviews for this lesson
     */
    public function comments(): HasMany
    {
        return $this->hasMany(CourseReview::class, 'lesson_id');
    }

    // Methods
    public function markAsWatched(User $user): StudentProgress
    {
        $progress = $this->progress()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'progress_percentage' => 100, // Mark as 100% complete
                'is_completed' => true,
                'completed_at' => now(),
                'last_accessed_at' => now(),
            ]
        );

        // Update view count
        $this->increment('view_count');

        return $progress;
    }

    public function getNextLesson(): ?Lesson
    {
        return Lesson::where('recorded_course_id', $this->recorded_course_id)
            ->where('id', '>', $this->id)
            ->where('is_published', true)
            ->orderBy('id')
            ->first();
    }

    public function getPreviousLesson(): ?Lesson
    {
        return Lesson::where('recorded_course_id', $this->recorded_course_id)
            ->where('id', '<', $this->id)
            ->where('is_published', true)
            ->orderBy('id', 'desc')
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
            'total_comments' => $this->comments()->count(),
        ]);
    }
}
