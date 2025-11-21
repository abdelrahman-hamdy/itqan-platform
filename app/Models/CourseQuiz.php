<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseQuiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'recorded_course_id',
        'section_id',
        'title',
        'title_en',
        'description',
        'description_en',
        'duration_minutes',
        'passing_score',
        'max_attempts',
        'show_correct_answers',
        'randomize_questions',
        'is_published',
        'order',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'max_attempts' => 'integer',
        'show_correct_answers' => 'boolean',
        'randomize_questions' => 'boolean',
        'is_published' => 'boolean',
        'order' => 'integer',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(RecordedCourse::class, 'recorded_course_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'quiz_id')->orderBy('order');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('available_until')
                    ->orWhere('available_until', '>=', now());
            });
    }

    // Methods
    public function isAvailable(): bool
    {
        if (!$this->is_published) {
            return false;
        }

        if ($this->available_from && now()->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until && now()->gt($this->available_until)) {
            return false;
        }

        return true;
    }

    public function getTotalQuestions(): int
    {
        return $this->questions()->count();
    }
}
