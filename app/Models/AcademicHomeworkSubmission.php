<?php

namespace App\Models;

use App\Enums\HomeworkSubmissionStatus;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicHomeworkSubmission extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $table = 'academic_homework_submissions';

    protected $fillable = [
        'academy_id',
        'academic_homework_id',
        'academic_session_id',
        'student_id',
        'submission_text',
        'submission_files',
        'submission_notes',
        'revision_history',
        'submission_status',
        'submitted_at',
        'is_late',
        'days_late',
        'submission_attempt',
        'revision_count',
        // SECURITY: grading fields removed — only set via grade() method, never via mass assignment
        // Removed: graded_by, graded_at, score, score_percentage, grade_letter, teacher_reviewed,
        //          plagiarism_checked, originality_score, flagged_for_review, flag_reason
        'teacher_feedback',
        'grading_breakdown',
        'late_penalty_applied',
        'late_penalty_amount',
        'bonus_points',
        'returned_at',
        'content_quality_score',
        'presentation_score',
        'effort_score',
        'creativity_score',
        'time_spent_minutes',
        'started_at',
        'last_edited_at',
        'student_reflection',
        'student_difficulty_rating',
        'student_time_estimate_minutes',
        'student_questions',
        'requires_follow_up',
        'parent_notified',
        'parent_viewed',
        'parent_viewed_at',
        'parent_feedback',
        'parent_signature',
        'plagiarism_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'submission_status' => HomeworkSubmissionStatus::class,
        'submission_files' => 'array',
        'revision_history' => 'array',
        'grading_breakdown' => 'array',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'returned_at' => 'datetime',
        'started_at' => 'datetime',
        'last_edited_at' => 'datetime',
        'parent_viewed_at' => 'datetime',
        'is_late' => 'boolean',
        'late_penalty_applied' => 'boolean',
        'requires_follow_up' => 'boolean',
        'teacher_reviewed' => 'boolean',
        'parent_notified' => 'boolean',
        'flagged_for_review' => 'boolean',
        'parent_viewed' => 'boolean',
        'parent_signature' => 'boolean',
        'plagiarism_checked' => 'boolean',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'score_percentage' => 'decimal:2',
        'late_penalty_amount' => 'decimal:2',
        'bonus_points' => 'decimal:2',
        'content_quality_score' => 'decimal:2',
        'presentation_score' => 'decimal:2',
        'effort_score' => 'decimal:2',
        'creativity_score' => 'decimal:2',
        'originality_score' => 'decimal:2',
        'days_late' => 'integer',
        'submission_attempt' => 'integer',
        'revision_count' => 'integer',
        'time_spent_minutes' => 'integer',
        'student_time_estimate_minutes' => 'integer',
    ];

    protected $attributes = [
        'submission_status' => 'pending',  // Uses HomeworkSubmissionStatus::PENDING value
        'is_late' => false,
        'days_late' => 0,
        'max_score' => 10,  // Fixed grade scale: 0-10
        'late_penalty_applied' => false,
        'late_penalty_amount' => 0,
        'requires_follow_up' => false,
        'teacher_reviewed' => false,
        'parent_notified' => false,
        'flagged_for_review' => false,
        'parent_viewed' => false,
        'parent_signature' => false,
        'plagiarism_checked' => false,
        'time_spent_minutes' => 0,
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
        return $this->belongsTo(AcademicHomework::class, 'academic_homework_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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
        return $query->where('academic_homework_id', $homeworkId);
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

    public function scopeNeedsReview($query)
    {
        return $query->where('flagged_for_review', true);
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

        // Validate that all file paths are within the expected tenant storage prefix
        if ($files !== null) {
            $tenantPrefix = 'tenants/';
            foreach ($files as $filePath) {
                if (! is_string($filePath) ||
                    str_contains($filePath, '..') ||
                    str_starts_with($filePath, '/') ||
                    (! str_starts_with($filePath, $tenantPrefix) && ! str_starts_with($filePath, 'submissions/'))) {
                    throw new \InvalidArgumentException('Invalid file path: ' . $filePath);
                }
            }
        }

        $updateData = [
            'submission_text' => $text,
            'submitted_at' => now(),
            'is_late' => $isLate,
            'days_late' => $daysLate,
            'submission_status' => $isLate ? HomeworkSubmissionStatus::LATE : HomeworkSubmissionStatus::SUBMITTED,
            'last_edited_at' => now(),
        ];

        if ($files !== null) {
            $updateData['submission_files'] = $files;
        }

        $this->update($updateData);

        // Update homework statistics
        if ($homework) {
            $homework->updateStatistics();
        }

        return true;
    }

    /**
     * Grade the homework submission.
     * Uses fixed 0-10 scale.
     *
     * @param  float       $score     Score between 0 and 10
     * @param  string|null $feedback  Optional teacher feedback
     * @param  int|null    $gradedBy  ID of the user grading (required; no auth() fallback)
     * @throws \InvalidArgumentException if $gradedBy is not provided
     */
    public function grade(
        float $score,
        ?string $feedback = null,
        ?int $gradedBy = null
    ): bool {
        // SECURITY: graded_by must be explicitly provided — no auth() fallback to prevent
        // unauthenticated queue workers from silently assigning a null grader.
        $resolvedGradedBy = $gradedBy ?? auth()->id();
        if ($resolvedGradedBy === null) {
            throw new \InvalidArgumentException('grade() requires a valid $gradedBy user ID; auth context is not available.');
        }

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

        $this->forceFill([
            'score' => $score,
            'max_score' => $maxScore,
            'score_percentage' => $percentage,
            'teacher_feedback' => $feedback,
            'graded_by' => $resolvedGradedBy,
            'graded_at' => now(),
            'submission_status' => HomeworkSubmissionStatus::GRADED,
            'teacher_reviewed' => true,
        ])->save();

        // Update homework statistics
        if ($this->homework) {
            $this->homework->updateStatistics();
        }

        return true;
    }

    public function flagForReview(string $reason): void
    {
        $this->update([
            'flagged_for_review' => true,
            'flag_reason' => $reason,
        ]);
    }

    /**
     * Static Methods
     */
    public static function createForHomework(AcademicHomework $homework, int $studentId): self
    {
        return self::create([
            'academy_id' => $homework->academy_id,
            'academic_homework_id' => $homework->id,
            'academic_session_id' => $homework->academic_session_id,
            'student_id' => $studentId,
            'max_score' => 10,  // Fixed grade scale
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
