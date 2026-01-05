<?php

namespace App\Models;

use App\Enums\HomeworkSubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class InteractiveCourseHomework extends Model
{
    use HasFactory;

    protected $table = 'interactive_course_homework';

    protected $fillable = [
        'academy_id',
        'session_id',
        'student_id',
        'submission_status',
        'submission_text',
        'submission_files',
        'submitted_at',
        'is_late',
        'score',
        'max_score',
        'teacher_feedback',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'submission_status' => HomeworkSubmissionStatus::class,
        'submission_files' => 'array',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'is_late' => 'boolean',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    protected $attributes = [
        'submission_status' => 'pending',  // Uses HomeworkSubmissionStatus::PENDING value
        'is_late' => false,
        'max_score' => 10,  // Fixed grade scale: 0-10
    ];

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourseSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Scopes
     */
    public function scopeForAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopePending($query)
    {
        return $query->where('submission_status', HomeworkSubmissionStatus::PENDING);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereIn('submission_status', [
            HomeworkSubmissionStatus::SUBMITTED,
            HomeworkSubmissionStatus::LATE,
            HomeworkSubmissionStatus::GRADED,
        ]);
    }

    public function scopePendingGrading($query)
    {
        return $query->whereIn('submission_status', [
            HomeworkSubmissionStatus::SUBMITTED,
            HomeworkSubmissionStatus::LATE,
        ]);
    }

    public function scopeGraded($query)
    {
        return $query->where('submission_status', HomeworkSubmissionStatus::GRADED);
    }

    public function scopeLateSubmissions($query)
    {
        return $query->where('is_late', true);
    }

    /**
     * Helper Methods
     */

    /**
     * Submit homework
     * Simple flow: pending → submitted/late
     */
    public function submit(?string $text = null, ?array $files = null): bool
    {
        // Can only submit if pending
        if ($this->submission_status !== HomeworkSubmissionStatus::PENDING) {
            return false;
        }

        $dueDate = $this->session->homework_due_date ?? null;
        $isLate = $dueDate && now()->isAfter($dueDate);

        // Check if late submissions are allowed
        if ($isLate && !($this->session->allow_late_submissions ?? true)) {
            return false;
        }

        $this->update([
            'submission_text' => $text,
            'submission_files' => $files,
            'submitted_at' => now(),
            'is_late' => $isLate,
            'submission_status' => $isLate ? HomeworkSubmissionStatus::LATE : HomeworkSubmissionStatus::SUBMITTED,
        ]);

        return true;
    }

    /**
     * Grade homework
     * Uses fixed 0-10 scale
     */
    public function grade(float $score, ?string $feedback = null, ?int $gradedBy = null): bool
    {
        // Can only grade if submitted or late
        if (!in_array($this->submission_status, [
            HomeworkSubmissionStatus::SUBMITTED,
            HomeworkSubmissionStatus::LATE,
        ], true)) {
            return false;
        }

        // Fixed max score of 10
        $maxScore = 10;

        // Ensure score is within 0-10 range
        $score = max(0, min($score, $maxScore));

        $this->update([
            'score' => $score,
            'max_score' => $maxScore,
            'teacher_feedback' => $feedback,
            'graded_by' => $gradedBy ?? auth()->id(),
            'graded_at' => now(),
            'submission_status' => HomeworkSubmissionStatus::GRADED,
        ]);

        return true;
    }

    /**
     * Check if homework is overdue
     */
    public function isOverdue(): bool
    {
        $dueDate = $this->session->homework_due_date ?? null;
        if (!$dueDate) {
            return false;
        }

        return $this->submission_status === HomeworkSubmissionStatus::PENDING &&
               now()->isAfter($dueDate);
    }

    /**
     * Check if homework can be submitted
     */
    public function canSubmit(): bool
    {
        // Can only submit if pending
        if ($this->submission_status !== HomeworkSubmissionStatus::PENDING) {
            return false;
        }

        $dueDate = $this->session->homework_due_date ?? null;

        // No due date means always can submit
        if (!$dueDate) {
            return true;
        }

        // If not late, can submit
        if (now()->isBefore($dueDate)) {
            return true;
        }

        // If late but allowed
        return $this->session->allow_late_submissions ?? true;
    }

    /**
     * Get submission status label (uses enum)
     */
    public function getSubmissionStatusLabelAttribute(): string
    {
        return $this->submission_status?->label() ?? 'غير معروف';
    }

    /**
     * Get percentage score (0-100 based on 0-10 scale)
     */
    public function getScorePercentageAttribute(): ?float
    {
        if ($this->score === null) {
            return null;
        }

        $maxScore = $this->max_score ?? 10;
        return $maxScore > 0 ? round(($this->score / $maxScore) * 100, 2) : 0;
    }

    /**
     * Check if pending
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->submission_status === HomeworkSubmissionStatus::PENDING;
    }

    /**
     * Check if submitted
     */
    public function getIsSubmittedAttribute(): bool
    {
        return in_array($this->submission_status, [
            HomeworkSubmissionStatus::SUBMITTED,
            HomeworkSubmissionStatus::LATE,
            HomeworkSubmissionStatus::GRADED,
        ], true);
    }

    /**
     * Check if graded
     */
    public function getIsGradedAttribute(): bool
    {
        return $this->submission_status === HomeworkSubmissionStatus::GRADED;
    }

    /**
     * Check if homework has files
     */
    public function hasFiles(): bool
    {
        return !empty($this->submission_files);
    }

    /**
     * Get file count
     */
    public function getFileCountAttribute(): int
    {
        return count($this->submission_files ?? []);
    }

    /**
     * Get days until due
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->session->homework_due_date) {
            return null;
        }

        return now()->diffInDays($this->session->homework_due_date, false);
    }

    /**
     * Check if submission is recent (within 24 hours)
     */
    public function isRecentSubmission(): bool
    {
        return $this->submitted_at &&
               $this->submitted_at->diffInHours(now()) <= 24;
    }
}
