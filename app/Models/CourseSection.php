<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'recorded_course_id',
        'title',
        'title_en',
        'description',
        'description_en',
        'order',
        'is_published',
        'is_free_preview',
        'duration_minutes',
        'lessons_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_free_preview' => 'boolean',
        'order' => 'integer',
        'duration_minutes' => 'integer',
        'lessons_count' => 'integer',
    ];

    // Relationships
    public function recordedCourse(): BelongsTo
    {
        return $this->belongsTo(RecordedCourse::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('id');
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

    // Accessors
    public function getDurationFormattedAttribute(): string
    {
        if ($this->duration_minutes < 60) {
            return $this->duration_minutes.' دقيقة';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($minutes > 0) {
            return $hours.' ساعة و '.$minutes.' دقيقة';
        }

        return $hours.' ساعة';
    }

    public function getCompletionPercentageAttribute(): float
    {
        $totalLessons = $this->lessons()->count();
        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = $this->lessons()
            ->whereHas('progress', function ($query) {
                $query->where('user_id', auth()->id())
                    ->where('is_completed', true);
            })
            ->count();

        return ($completedLessons / $totalLessons) * 100;
    }

    // Methods
    public function updateStats(): void
    {
        $this->update([
            'lessons_count' => $this->lessons()->count(),
            'duration_minutes' => $this->lessons()->sum('duration_minutes'),
        ]);
    }

    public function getNextLesson(User $user): ?Lesson
    {
        return $this->lessons()
            ->whereDoesntHave('progress', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('is_completed', true);
            })
            ->orderBy('order')
            ->first();
    }

    public function isCompletedBy(User $user): bool
    {
        $totalLessons = $this->lessons()->count();
        if ($totalLessons === 0) {
            return false;
        }

        $completedLessons = $this->lessons()
            ->whereHas('progress', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('is_completed', true);
            })
            ->count();

        return $completedLessons === $totalLessons;
    }
}
