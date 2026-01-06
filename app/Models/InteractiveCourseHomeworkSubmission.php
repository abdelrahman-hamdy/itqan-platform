<?php

namespace App\Models;

use App\Enums\HomeworkSubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * InteractiveCourseHomeworkSubmission
 *
 * Represents a student's submission for an interactive course homework assignment.
 * Aligned with AcademicHomeworkSubmission structure for consistency.
 */
class InteractiveCourseHomeworkSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'interactive_course_homework_submissions';

    protected $fillable = [
        'academy_id',
        'interactive_course_homework_id',
        'interactive_course_session_id',
        'student_id',
        'submission_text',
        'submission_files',
        'submission_status',
        'submitted_at',
        'is_late',
        'days_late',
        'score',
        'max_score',
        'score_percentage',
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
        'days_late' => 'integer',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'score_percentage' => 'decimal:2',
    ];

    protected $attributes = [
        'submission_status' => 'pending',
        'is_late' => false,
        'days_late' => 0,
        'max_score' => 10,
    ];

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function homework(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourseHomework::class, 'interactive_course_homework_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourseSession::class, 'interactive_course_session_id');
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

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForHomework($query, int $homeworkId)
    {
        return $query->where('interactive_course_homework_id', $homeworkId);
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
     * Accessors
     */
    public function getSubmissionStatusTextAttribute(): string
    {
        return $this->submission_status->label();
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->submission_status === HomeworkSubmissionStatus::PENDING;
    }

    public function getIsSubmittedAttribute(): bool
    {
        return in_array($this->submission_status, [
            HomeworkSubmissionStatus::SUBMITTED,
            HomeworkSubmissionStatus::LATE,
            HomeworkSubmissionStatus::GRADED,
        ], true);
    }

    public function getIsGradedAttribute(): bool
    {
        return $this->submission_status === HomeworkSubmissionStatus::GRADED;
    }

    public function getCanSubmitAttribute(): bool
    {
        return $this->submission_status === HomeworkSubmissionStatus::PENDING;
    }

    public function getGradePerformanceAttribute(): ?string
    {
        if (! $this->score_percentage) {
            return null;
        }

        return match (true) {
            $this->score_percentage >= 90 => 'ممتاز',
            $this->score_percentage >= 80 => 'جيد جداً',
            $this->score_percentage >= 70 => 'جيد',
            $this->score_percentage >= 60 => 'مقبول',
            default => 'يحتاج تحسين',
        };
    }

    public function getFileCountAttribute(): int
    {
        return count($this->submission_files ?? []);
    }

    public function hasFiles(): bool
    {
        return ! empty($this->submission_files);
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (! $this->homework || ! $this->homework->due_date) {
            return null;
        }

        return now()->diffInDays($this->homework->due_date, false);
    }

    /**
     * Helper Methods
     */

    /**
     * Submit the homework
     * Simple flow: pending → submitted/late
     */
    public function submit(?string $text = null, ?array $files = null): bool
    {
        // Can only submit if pending
        if ($this->submission_status !== HomeworkSubmissionStatus::PENDING) {
            return false;
        }

        $homework = $this->homework;
        $isLate = $homework && $homework->due_date && now()->isAfter($homework->due_date);

        // Check if late submissions allowed
        if ($isLate && $homework && ! $homework->allow_late_submissions) {
            return false;
        }

        // Calculate days late
        $daysLate = 0;
        if ($isLate && $homework && $homework->due_date) {
            $daysLate = now()->diffInDays($homework->due_date);
        }

        $this->update([
            'submission_text' => $text,
            'submission_files' => $files,
            'submitted_at' => now(),
            'is_late' => $isLate,
            'days_late' => $daysLate,
            'submission_status' => $isLate ? HomeworkSubmissionStatus::LATE : HomeworkSubmissionStatus::SUBMITTED,
        ]);

        // Update homework statistics
        if ($homework) {
            $homework->updateStatistics();
        }

        return true;
    }

    /**
     * Grade the homework submission
     * Uses fixed 0-10 scale
     */
    public function grade(
        float $score,
        ?string $feedback = null,
        ?int $gradedBy = null
    ): bool {
        // Can only grade if submitted or late
        if (! in_array($this->submission_status, [
            HomeworkSubmissionStatus::SUBMITTED,
            HomeworkSubmissionStatus::LATE,
        ], true)) {
            return false;
        }

        // Fixed max score of 10
        $maxScore = 10;

        // Ensure score is within 0-10 range
        $score = max(0, min($score, $maxScore));

        // Calculate percentage
        $percentage = ($score / $maxScore) * 100;

        $this->update([
            'score' => $score,
            'max_score' => $maxScore,
            'score_percentage' => $percentage,
            'teacher_feedback' => $feedback,
            'graded_by' => $gradedBy ?? auth()->id(),
            'graded_at' => now(),
            'submission_status' => HomeworkSubmissionStatus::GRADED,
        ]);

        // Update homework statistics
        if ($this->homework) {
            $this->homework->updateStatistics();
        }

        return true;
    }

    /**
     * Static Methods
     */
    public static function createForHomework(InteractiveCourseHomework $homework, int $studentId): self
    {
        return self::create([
            'academy_id' => $homework->academy_id,
            'interactive_course_homework_id' => $homework->id,
            'interactive_course_session_id' => $homework->interactive_course_session_id,
            'student_id' => $studentId,
            'max_score' => 10,
            'submission_status' => HomeworkSubmissionStatus::PENDING,
        ]);
    }

    public static function getForStudent(int $studentId, int $academyId, ?string $status = null)
    {
        $query = self::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->with(['homework', 'session']);

        if ($status) {
            $query->where('submission_status', $status);
        }

        return $query->orderBy('submitted_at', 'desc')->get();
    }

    public static function getPendingForStudent(int $studentId, int $academyId)
    {
        return self::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('submission_status', HomeworkSubmissionStatus::PENDING)
            ->with(['homework.session'])
            ->get();
    }
}
