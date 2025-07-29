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
        'course_section_id',
        'lesson_id',
        'title',
        'title_en',
        'description',
        'description_en',
        'quiz_type',
        'time_limit_minutes',
        'max_attempts',
        'pass_score_percentage',
        'questions_count',
        'total_points',
        'is_randomized',
        'show_results_immediately',
        'allow_review',
        'is_required',
        'is_published',
        'difficulty_level',
        'instructions',
        'completion_message',
        'failure_message',
        'attempts_count',
        'avg_score',
        'pass_rate_percentage',
        'created_by',
        'updated_by',
        'published_at'
    ];

    protected $casts = [
        'is_randomized' => 'boolean',
        'show_results_immediately' => 'boolean',
        'allow_review' => 'boolean',
        'is_required' => 'boolean',
        'is_published' => 'boolean',
        'time_limit_minutes' => 'integer',
        'max_attempts' => 'integer',
        'pass_score_percentage' => 'decimal:1',
        'questions_count' => 'integer',
        'total_points' => 'integer',
        'attempts_count' => 'integer',
        'avg_score' => 'decimal:1',
        'pass_rate_percentage' => 'decimal:1',
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

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('quiz_type', $type);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    // Accessors
    public function getTimeLimitFormattedAttribute(): string
    {
        if (!$this->time_limit_minutes) {
            return 'غير محدود';
        }
        
        if ($this->time_limit_minutes < 60) {
            return $this->time_limit_minutes . ' دقيقة';
        }
        
        $hours = floor($this->time_limit_minutes / 60);
        $minutes = $this->time_limit_minutes % 60;
        
        if ($minutes > 0) {
            return $hours . ' ساعة و ' . $minutes . ' دقيقة';
        }
        
        return $hours . ' ساعة';
    }

    public function getMaxAttemptsFormattedAttribute(): string
    {
        return $this->max_attempts ? $this->max_attempts . ' محاولات' : 'غير محدود';
    }

    public function getPassScoreFormattedAttribute(): string
    {
        return $this->pass_score_percentage . '%';
    }

    public function getUserAttemptCountAttribute(): int
    {
        if (!auth()->check()) {
            return 0;
        }
        
        return $this->attempts()
            ->where('user_id', auth()->id())
            ->count();
    }

    public function getUserBestScoreAttribute(): ?float
    {
        if (!auth()->check()) {
            return null;
        }
        
        return $this->attempts()
            ->where('user_id', auth()->id())
            ->max('score_percentage');
    }

    public function getUserLatestAttemptAttribute(): ?QuizAttempt
    {
        if (!auth()->check()) {
            return null;
        }
        
        return $this->attempts()
            ->where('user_id', auth()->id())
            ->latest()
            ->first();
    }

    public function getCanAttemptAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }
        
        if (!$this->is_published) {
            return false;
        }
        
        if ($this->max_attempts && $this->user_attempt_count >= $this->max_attempts) {
            return false;
        }
        
        return true;
    }

    public function getIsPassedAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }
        
        $bestScore = $this->user_best_score;
        return $bestScore && $bestScore >= $this->pass_score_percentage;
    }

    // Methods
    public function startAttempt(User $user): QuizAttempt
    {
        if (!$this->canAttemptBy($user)) {
            throw new \Exception('لا يمكن بدء محاولة جديدة لهذا الاختبار');
        }
        
        return $this->attempts()->create([
            'user_id' => $user->id,
            'started_at' => now(),
            'expires_at' => $this->time_limit_minutes ? now()->addMinutes($this->time_limit_minutes) : null,
            'status' => 'in_progress'
        ]);
    }

    public function canAttemptBy(User $user): bool
    {
        if (!$this->is_published) {
            return false;
        }
        
        $userAttempts = $this->attempts()
            ->where('user_id', $user->id)
            ->count();
            
        if ($this->max_attempts && $userAttempts >= $this->max_attempts) {
            return false;
        }
        
        // Check if user has active attempt
        $activeAttempt = $this->attempts()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();
            
        if ($activeAttempt) {
            return false;
        }
        
        return true;
    }

    public function getActiveAttempt(User $user): ?QuizAttempt
    {
        return $this->attempts()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();
    }

    public function updateStats(): void
    {
        $attempts = $this->attempts()->where('status', 'completed');
        
        $this->update([
            'questions_count' => $this->questions()->count(),
            'total_points' => $this->questions()->sum('points'),
            'attempts_count' => $attempts->count(),
            'avg_score' => $attempts->avg('score_percentage') ?? 0,
            'pass_rate_percentage' => $attempts->count() > 0 ? 
                ($attempts->where('score_percentage', '>=', $this->pass_score_percentage)->count() / $attempts->count()) * 100 : 0
        ]);
    }

    public function generateRandomQuestions(int $count = null): array
    {
        $questionCount = $count ?? $this->questions_count;
        
        if ($this->is_randomized) {
            return $this->questions()
                ->inRandomOrder()
                ->limit($questionCount)
                ->get()
                ->toArray();
        }
        
        return $this->questions()
            ->orderBy('order')
            ->limit($questionCount)
            ->get()
            ->toArray();
    }
} 