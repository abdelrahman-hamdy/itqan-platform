<?php

namespace App\Models;

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
        'teacher_feedback',
        'graded_by',
        'graded_at',
        'revision_count',
        'revision_history',
    ];

    protected $casts = [
        'submission_files' => 'array',
        'revision_history' => 'array',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'is_late' => 'boolean',
        'score' => 'decimal:2',
        'revision_count' => 'integer',
    ];

    protected $attributes = [
        'submission_status' => 'not_submitted',
        'is_late' => false,
        'revision_count' => 0,
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

    public function scopeNotSubmitted($query)
    {
        return $query->where('submission_status', 'not_submitted');
    }

    public function scopeSubmitted($query)
    {
        return $query->whereIn('submission_status', ['submitted', 'late', 'graded', 'returned']);
    }

    public function scopePendingGrading($query)
    {
        return $query->whereIn('submission_status', ['submitted', 'late']);
    }

    public function scopeGraded($query)
    {
        return $query->where('submission_status', 'graded');
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
     */
    public function submit(?string $text = null, ?array $files = null): bool
    {
        $dueDate = $this->session->homework_due_date;
        $isLate = $dueDate && now()->isAfter($dueDate);

        // Check if late submissions are allowed
        if ($isLate && !$this->session->allow_late_submissions) {
            return false;
        }

        // Prepare revision history
        $revisionHistory = $this->revision_history ?? [];
        $revisionHistory[] = [
            'submitted_at' => now()->toDateTimeString(),
            'text' => $text,
            'files' => $files,
            'is_late' => $isLate,
        ];

        $this->update([
            'submission_text' => $text,
            'submission_files' => $files,
            'submitted_at' => now(),
            'is_late' => $isLate,
            'submission_status' => $isLate ? 'late' : 'submitted',
            'revision_count' => $this->revision_count + 1,
            'revision_history' => $revisionHistory,
        ]);

        return true;
    }

    /**
     * Grade homework
     */
    public function grade(float $score, ?string $feedback = null, ?int $gradedBy = null): bool
    {
        if (!in_array($this->submission_status, ['submitted', 'late'])) {
            return false;
        }

        $maxScore = $this->session->homework_max_score ?? 100;
        if ($score > $maxScore) {
            $score = $maxScore;
        }

        $this->update([
            'score' => $score,
            'teacher_feedback' => $feedback,
            'graded_by' => $gradedBy,
            'graded_at' => now(),
            'submission_status' => 'graded',
        ]);

        return true;
    }

    /**
     * Return homework to student (make visible)
     */
    public function returnToStudent(): bool
    {
        if ($this->submission_status !== 'graded') {
            return false;
        }

        $this->update(['submission_status' => 'returned']);
        return true;
    }

    /**
     * Check if homework is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->session->homework_due_date) {
            return false;
        }

        return $this->submission_status === 'not_submitted' &&
               now()->isAfter($this->session->homework_due_date);
    }

    /**
     * Check if homework can be submitted
     */
    public function canSubmit(): bool
    {
        $dueDate = $this->session->homework_due_date;

        // No due date means always can submit
        if (!$dueDate) {
            return true;
        }

        // If not late, can submit
        if (now()->isBefore($dueDate)) {
            return true;
        }

        // If late but allowed
        return $this->session->allow_late_submissions;
    }

    /**
     * Get submission status in Arabic
     */
    public function getSubmissionStatusInArabicAttribute(): string
    {
        return match($this->submission_status) {
            'not_submitted' => 'لم يتم التسليم',
            'submitted' => 'تم التسليم',
            'late' => 'تسليم متأخر',
            'graded' => 'تم التصحيح',
            'returned' => 'تم الإرجاع',
            default => 'غير معروف',
        };
    }

    /**
     * Get percentage score
     */
    public function getScorePercentageAttribute(): ?float
    {
        if (!$this->score) {
            return null;
        }

        $maxScore = $this->session->homework_max_score ?? 100;
        return round(($this->score / $maxScore) * 100, 2);
    }

    /**
     * Get grade letter based on score
     */
    public function getGradeLetterAttribute(): ?string
    {
        $percentage = $this->score_percentage;

        if ($percentage === null) {
            return null;
        }

        return match(true) {
            $percentage >= 90 => 'A',
            $percentage >= 80 => 'B',
            $percentage >= 70 => 'C',
            $percentage >= 60 => 'D',
            default => 'F',
        };
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
