<?php

namespace App\Models;

use App\Enums\HomeworkSubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HomeworkSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        // Identification
        'academy_id',
        'student_id',
        'submission_code',
        'submitable_type',
        'submitable_id',
        'homework_type',

        // Submission Content
        'submission_text',
        'submission_files',

        // Timing
        'due_date',
        'submitted_at',
        'is_late',
        'days_late',

        // Grading & Scoring
        'score',
        'max_score',
        'score_percentage',
        'grade_letter',
        'teacher_feedback',
        'graded_at',
        'graded_by',

        // Status & Progress
        'submission_status',
        'progress_percentage',

        // Auto-save
        'last_auto_save_at',
        'auto_save_content',

        // Revision Tracking
        'revision_count',
        'returned_at',
        'return_reason',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'returned_at' => 'datetime',
        'last_auto_save_at' => 'datetime',
        'due_date' => 'datetime',
        'grade' => 'decimal:1',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'score_percentage' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
        'submission_files' => 'array',
        'is_late' => 'boolean',
        'days_late' => 'integer',
        'revision_count' => 'integer',
        'submission_status' => HomeworkSubmissionStatus::class,
    ];

    // ========================================
    // Relationships
    // ========================================

    /**
     * Get the parent session (polymorphic)
     * Can be QuranSession, AcademicSession, or InteractiveCourseSession
     */
    public function submitable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the student who submitted
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Alias for student() relationship (for API compatibility)
     */
    public function user(): BelongsTo
    {
        return $this->student();
    }

    /**
     * Get the teacher who graded
     */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Get the academy
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    // ========================================
    // Scopes
    // ========================================

    // Status-based scopes (using new submission_status field)
    public function scopeNotStarted($query)
    {
        return $query->where('submission_status', 'not_started');
    }

    public function scopeDraft($query)
    {
        return $query->where('submission_status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->whereIn('submission_status', ['submitted', 'late', 'graded', 'resubmitted']);
    }

    public function scopeGraded($query)
    {
        return $query->where('submission_status', 'graded');
    }

    public function scopeLate($query)
    {
        return $query->where('is_late', true);
    }

    public function scopeReturned($query)
    {
        return $query->where('submission_status', 'returned');
    }

    public function scopeResubmitted($query)
    {
        return $query->where('submission_status', 'resubmitted');
    }

    // Type-based scopes
    public function scopeAcademic($query)
    {
        return $query->where('homework_type', 'academic');
    }

    public function scopeInteractive($query)
    {
        return $query->where('homework_type', 'interactive');
    }

    public function scopeQuran($query)
    {
        return $query->where('homework_type', 'quran');
    }

    // Filter scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('homework_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('submission_status', $status);
    }

    // Due date scopes
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereIn('submission_status', ['not_started', 'draft']);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopeDueSoon($query, $hours = 24)
    {
        return $query->whereBetween('due_date', [now(), now()->addHours($hours)])
            ->whereIn('submission_status', ['not_started', 'draft']);
    }

    // ========================================
    // Methods
    // ========================================

    /**
     * Submit the homework (final submission)
     */
    public function submit(?string $text = null, ?array $files = null): self
    {
        $isLate = $this->due_date && now()->isAfter($this->due_date);
        $daysLate = $isLate ? now()->diffInDays($this->due_date) : 0;

        $this->update([
            'submission_text' => $text ?? $this->submission_text,
            'submission_files' => $files ?? $this->submission_files,
            'submitted_at' => now(),
            'is_late' => $isLate,
            'days_late' => $daysLate,
            'submission_status' => $isLate ? 'late' : 'submitted',
            'progress_percentage' => 100,
            'auto_save_content' => null,
            'last_auto_save_at' => null,
        ]);

        return $this;
    }

    /**
     * Save as draft (auto-save)
     */
    public function saveDraft(?string $text = null, ?array $files = null): self
    {
        $progressPercentage = $this->calculateProgress($text, $files);

        $this->update([
            'auto_save_content' => json_encode([
                'text' => $text,
                'files' => $files,
                'saved_at' => now()->toIso8601String(),
            ]),
            'last_auto_save_at' => now(),
            'submission_status' => 'draft',
            'progress_percentage' => $progressPercentage,
        ]);

        return $this;
    }

    /**
     * Grade the homework submission
     */
    public function grade(float $score, int $gradedBy, ?string $feedback = null, ?float $maxScore = null): self
    {
        $maxScore = $maxScore ?? $this->max_score ?? 100;

        if ($score < 0 || $score > $maxScore) {
            throw new \InvalidArgumentException("Score must be between 0 and {$maxScore}");
        }

        $scorePercentage = ($score / $maxScore) * 100;
        $gradeLetter = $this->calculateGradeLetter($scorePercentage);

        $this->update([
            'score' => $score,
            'max_score' => $maxScore,
            'score_percentage' => $scorePercentage,
            'grade_letter' => $gradeLetter,
            'teacher_feedback' => $feedback,
            'graded_by' => $gradedBy,
            'graded_at' => now(),
            'submission_status' => 'graded',
        ]);

        return $this;
    }

    /**
     * Return submission for revision
     */
    public function returnForRevision(string $reason, int $returnedBy): self
    {
        $this->update([
            'submission_status' => 'returned',
            'returned_at' => now(),
            'return_reason' => $reason,
            'revision_count' => $this->revision_count + 1,
        ]);

        return $this;
    }

    /**
     * Resubmit after revision
     */
    public function resubmit(?string $text = null, ?array $files = null): self
    {
        $this->update([
            'submission_text' => $text ?? $this->submission_text,
            'submission_files' => $files ?? $this->submission_files,
            'submitted_at' => now(),
            'submission_status' => 'resubmitted',
            'return_reason' => null,
        ]);

        return $this;
    }

    /**
     * Check if student can submit (or resubmit)
     */
    public function canSubmit(): bool
    {
        return in_array($this->submission_status, [
            'not_started',
            'draft',
            'returned',
        ]);
    }

    /**
     * Check if submission is late
     */
    public function isLate(): bool
    {
        return $this->is_late || $this->submission_status === 'late';
    }

    /**
     * Check if submission is graded
     */
    public function isGraded(): bool
    {
        return $this->submission_status === 'graded';
    }

    /**
     * Check if submission is pending (not started or draft)
     */
    public function isPending(): bool
    {
        return in_array($this->submission_status, ['not_started', 'draft']);
    }

    /**
     * Check if submission has been submitted
     */
    public function isSubmitted(): bool
    {
        return in_array($this->submission_status, [
            'submitted',
            'late',
            'graded',
            'resubmitted',
        ]);
    }

    /**
     * Check if submission is in draft state
     */
    public function isDraft(): bool
    {
        return $this->submission_status === 'draft';
    }

    /**
     * Check if submission was returned for revision
     */
    public function isReturned(): bool
    {
        return $this->submission_status === 'returned';
    }

    /**
     * Check if submission is overdue (past due date and not submitted)
     */
    public function isOverdue(): bool
    {
        return $this->due_date
            && now()->isAfter($this->due_date)
            && $this->isPending();
    }

    /**
     * Get time remaining until deadline
     */
    public function timeRemaining(): ?\Carbon\CarbonInterval
    {
        if (!$this->due_date || $this->isSubmitted()) {
            return null;
        }

        return now()->diffAsCarbonInterval($this->due_date, false);
    }

    /**
     * Get human-readable status
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->submission_status) {
            'not_started' => 'لم يتم البدء',
            'draft' => 'مسودة - قيد العمل',
            'submitted' => 'تم التسليم',
            'late' => 'تم التسليم متأخراً',
            'graded' => 'تم التصحيح',
            'returned' => 'تم الإرجاع للمراجعة',
            'resubmitted' => 'تم إعادة التسليم',
            default => 'غير معروف',
        };
    }

    /**
     * Get performance level based on score
     */
    public function getPerformanceLevelAttribute(): ?string
    {
        if (!$this->isGraded() || !$this->score_percentage) {
            return null;
        }

        return match(true) {
            $this->score_percentage >= 90 => 'ممتاز',
            $this->score_percentage >= 80 => 'جيد جداً',
            $this->score_percentage >= 70 => 'جيد',
            $this->score_percentage >= 60 => 'مقبول',
            default => 'ضعيف',
        };
    }

    // ========================================
    // Private Helper Methods
    // ========================================

    /**
     * Calculate progress percentage based on content
     */
    private function calculateProgress(?string $text, ?array $files): float
    {
        $textProgress = $text ? (strlen($text) > 50 ? 50 : (strlen($text) / 50) * 50) : 0;
        $filesProgress = $files && count($files) > 0 ? 50 : 0;

        return min(99, $textProgress + $filesProgress); // Max 99% for draft, 100% only on submit
    }

    /**
     * Calculate grade letter from percentage
     */
    private function calculateGradeLetter(float $percentage): string
    {
        return match(true) {
            $percentage >= 95 => 'A+',
            $percentage >= 90 => 'A',
            $percentage >= 85 => 'B+',
            $percentage >= 80 => 'B',
            $percentage >= 75 => 'C+',
            $percentage >= 70 => 'C',
            $percentage >= 65 => 'D+',
            $percentage >= 60 => 'D',
            default => 'F',
        };
    }

    // ========================================
    // Boot Method
    // ========================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($submission) {
            // Generate unique submission code
            if (empty($submission->submission_code)) {
                $submission->submission_code = 'HW-' . strtoupper(uniqid());
            }

            // Set default status
            if (empty($submission->submission_status)) {
                $submission->submission_status = 'not_started';
            }

            // Set default max_score if not provided
            if (empty($submission->max_score)) {
                $submission->max_score = 100;
            }

            // Initialize progress
            if ($submission->progress_percentage === null) {
                $submission->progress_percentage = 0;
            }
        });
    }
}
