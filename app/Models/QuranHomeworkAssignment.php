<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranHomeworkAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_homework_id',
        'student_id',
        'session_id',
        'new_memorization_completed_pages',
        'new_memorization_quality',

        'review_completed_pages',
        'review_quality',

        'overall_score',
        'completion_status',
        'submitted_by_student',
        'submitted_at',
        'evaluated_by_teacher_at',
        'evaluated_by',
    ];

    protected $casts = [
        'new_memorization_completed_pages' => 'decimal:2',
        'review_completed_pages' => 'decimal:2',
        'overall_score' => 'decimal:1',
        'submitted_by_student' => 'boolean',
        'submitted_at' => 'datetime',
        'evaluated_by_teacher_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function sessionHomework(): BelongsTo
    {
        return $this->belongsTo(QuranSessionHomework::class, 'session_homework_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * Accessor for total completed pages
     */
    public function getTotalCompletedPagesAttribute(): float
    {
        return $this->new_memorization_completed_pages + $this->review_completed_pages;
    }

    /**
     * Accessor for completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        $homework = $this->sessionHomework;
        if (! $homework) {
            return 0;
        }

        $totalRequired = $homework->total_pages;

        return $totalRequired > 0 ? min(100, ($this->total_completed_pages / $totalRequired) * 100) : 0;
    }

    /**
     * Get new memorization completion percentage
     */
    public function getNewMemorizationPercentageAttribute(): float
    {
        $homework = $this->sessionHomework;
        if (! $homework || $homework->new_memorization_pages == 0) {
            return 0;
        }

        return min(100, ($this->new_memorization_completed_pages / $homework->new_memorization_pages) * 100);
    }

    /**
     * Get review completion percentage
     */
    public function getReviewPercentageAttribute(): float
    {
        $homework = $this->sessionHomework;
        if (! $homework || $homework->review_pages == 0) {
            return 0;
        }

        return min(100, ($this->review_completed_pages / $homework->review_pages) * 100);
    }

    /**
     * Get completion status in Arabic
     */
    public function getCompletionStatusArabicAttribute(): string
    {
        return match ($this->completion_status) {
            'not_started' => 'لم يبدأ',
            'in_progress' => 'جاري',
            'partially_completed' => 'مكتمل جزئياً',
            'completed' => 'مكتمل',
            default => 'غير محدد'
        };
    }

    /**
     * Get new memorization quality in Arabic
     */
    public function getNewMemorizationQualityArabicAttribute(): ?string
    {
        if (! $this->new_memorization_quality) {
            return null;
        }

        return match ($this->new_memorization_quality) {
            'excellent' => 'ممتاز',
            'good' => 'جيد',
            'needs_improvement' => 'يحتاج تحسين',
            'not_completed' => 'لم يكمل',
            default => null
        };
    }

    /**
     * Get review quality in Arabic
     */
    public function getReviewQualityArabicAttribute(): ?string
    {
        if (! $this->review_quality) {
            return null;
        }

        return match ($this->review_quality) {
            'excellent' => 'ممتاز',
            'good' => 'جيد',
            'needs_improvement' => 'يحتاج تحسين',
            'not_completed' => 'لم يكمل',
            default => null
        };
    }

    /**
     * Check if assignment is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        $homework = $this->sessionHomework;

        return $homework &&
               $homework->due_date &&
               $homework->due_date->isPast() &&
               $this->completion_status !== 'completed';
    }

    /**
     * Check if assignment is evaluated
     */
    public function getIsEvaluatedAttribute(): bool
    {
        return $this->evaluated_by_teacher_at !== null;
    }

    /**
     * Get overall performance grade
     */
    public function getPerformanceGradeAttribute(): string
    {
        if (! $this->overall_score) {
            return 'غير مقيم';
        }

        return match (true) {
            $this->overall_score >= 9 => 'ممتاز',
            $this->overall_score >= 8 => 'جيد جداً',
            $this->overall_score >= 7 => 'جيد',
            $this->overall_score >= 6 => 'مقبول',
            default => 'يحتاج تحسين'
        };
    }

    /**
     * Scope for completed assignments
     */
    public function scopeCompleted($query)
    {
        return $query->where('completion_status', 'completed');
    }

    /**
     * Scope for assignments by student
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope for assignments by session
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for evaluated assignments
     */
    public function scopeEvaluated($query)
    {
        return $query->whereNotNull('evaluated_by_teacher_at');
    }

    /**
     * Scope for overdue assignments
     */
    public function scopeOverdue($query)
    {
        return $query->whereHas('sessionHomework', function ($q) {
            $q->where('due_date', '<', now())
                ->where('is_active', true);
        })->where('completion_status', '!=', 'completed');
    }

    /**
     * Auto-calculate completion status based on pages completed
     */
    public function updateCompletionStatus(): void
    {
        $homework = $this->sessionHomework;
        if (! $homework) {
            return;
        }

        $totalRequired = $homework->total_pages;
        $totalCompleted = $this->total_completed_pages;

        if ($totalCompleted >= $totalRequired) {
            $this->completion_status = 'completed';
        } elseif ($totalCompleted >= ($totalRequired * 0.5)) {
            $this->completion_status = 'partially_completed';
        } elseif ($totalCompleted > 0) {
            $this->completion_status = 'in_progress';
        } else {
            $this->completion_status = 'not_started';
        }

        $this->save();
    }

    /**
     * Mark as evaluated by teacher
     */
    public function markAsEvaluated($teacherId = null): void
    {
        $this->evaluated_by_teacher_at = now();
        $this->evaluated_by = $teacherId ?? auth()->id();
        $this->save();
    }
}
