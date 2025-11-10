<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicHomeworkSubmission extends Model
{
    use HasFactory, SoftDeletes;

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
        'score',
        'max_score',
        'score_percentage',
        'grade_letter',
        'teacher_feedback',
        'grading_breakdown',
        'late_penalty_applied',
        'late_penalty_amount',
        'bonus_points',
        'graded_by',
        'graded_at',
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
        'teacher_reviewed',
        'parent_notified',
        'flagged_for_review',
        'flag_reason',
        'parent_viewed',
        'parent_viewed_at',
        'parent_feedback',
        'parent_signature',
        'plagiarism_checked',
        'originality_score',
        'plagiarism_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
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
        'submission_status' => 'not_submitted',
        'is_late' => false,
        'days_late' => 0,
        'submission_attempt' => 1,
        'revision_count' => 0,
        'late_penalty_applied' => false,
        'late_penalty_amount' => 0,
        'bonus_points' => 0,
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

    public function scopeNotSubmitted($query)
    {
        return $query->whereIn('submission_status', ['not_submitted', 'draft']);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereIn('submission_status', ['submitted', 'late', 'pending_review', 'under_review', 'graded', 'returned']);
    }

    public function scopePendingGrading($query)
    {
        return $query->whereIn('submission_status', ['submitted', 'late', 'pending_review']);
    }

    public function scopeGraded($query)
    {
        return $query->whereIn('submission_status', ['graded', 'returned']);
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
        return match($this->submission_status) {
            'not_submitted' => 'لم يتم التسليم',
            'draft' => 'مسودة',
            'submitted' => 'تم التسليم',
            'late' => 'تسليم متأخر',
            'pending_review' => 'بانتظار المراجعة',
            'under_review' => 'قيد المراجعة',
            'graded' => 'تم التصحيح',
            'returned' => 'تم الإرجاع',
            'revision_requested' => 'مطلوب تعديل',
            'resubmitted' => 'أعيد تسليمه',
            default => $this->submission_status,
        };
    }

    public function getIsSubmittedAttribute(): bool
    {
        return in_array($this->submission_status, ['submitted', 'late', 'pending_review', 'under_review', 'graded', 'returned', 'resubmitted']);
    }

    public function getIsGradedAttribute(): bool
    {
        return in_array($this->submission_status, ['graded', 'returned']);
    }

    public function getCanSubmitAttribute(): bool
    {
        return in_array($this->submission_status, ['not_submitted', 'draft']);
    }

    public function getCanEditAttribute(): bool
    {
        return in_array($this->submission_status, ['not_submitted', 'draft']);
    }

    public function getGradePerformanceAttribute(): ?string
    {
        if (!$this->score_percentage) {
            return null;
        }

        return match(true) {
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
        return !empty($this->submission_files);
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->homework || !$this->homework->due_date) {
            return null;
        }

        return now()->diffInDays($this->homework->due_date, false);
    }

    /**
     * Helper Methods
     */
    public function submit(?string $text = null, ?array $files = null): bool
    {
        if (!$this->can_submit) {
            return false;
        }

        $homework = $this->homework;
        $isLate = $homework->due_date && now()->isAfter($homework->due_date);

        // Check if late submissions allowed
        if ($isLate && !$homework->allow_late_submissions) {
            return false;
        }

        // Calculate days late
        $daysLate = 0;
        if ($isLate && $homework->due_date) {
            $daysLate = now()->diffInDays($homework->due_date);
        }

        // Add to revision history
        $revisionHistory = $this->revision_history ?? [];
        $revisionHistory[] = [
            'submitted_at' => now()->toDateTimeString(),
            'text' => $text,
            'files' => $files,
            'is_late' => $isLate,
            'attempt' => $this->submission_attempt,
        ];

        $this->update([
            'submission_text' => $text,
            'submission_files' => $files,
            'submitted_at' => now(),
            'is_late' => $isLate,
            'days_late' => $daysLate,
            'submission_status' => $isLate ? 'late' : 'submitted',
            'revision_history' => $revisionHistory,
            'last_edited_at' => now(),
        ]);

        // Update homework statistics
        $homework->updateStatistics();

        return true;
    }

    public function grade(
        float $score,
        ?string $feedback = null,
        ?array $qualityScores = null,
        ?int $gradedBy = null
    ): bool {
        if (!in_array($this->submission_status, ['submitted', 'late', 'pending_review', 'under_review'])) {
            return false;
        }

        $homework = $this->homework;
        $maxScore = $homework->max_score ?? 100;

        // Ensure score doesn't exceed max
        if ($score > $maxScore) {
            $score = $maxScore;
        }

        // Calculate percentage
        $percentage = ($score / $maxScore) * 100;

        // Apply late penalty if configured
        $latePenalty = 0;
        if ($this->is_late && $this->days_late > 0) {
            // Example: 5% penalty per day, max 50%
            $latePenalty = min(50, $this->days_late * 5);
            $score = $score * (1 - ($latePenalty / 100));
            $percentage = ($score / $maxScore) * 100;
        }

        // Determine letter grade
        $gradeLetter = match(true) {
            $percentage >= 90 => 'A',
            $percentage >= 80 => 'B',
            $percentage >= 70 => 'C',
            $percentage >= 60 => 'D',
            default => 'F',
        };

        $updateData = [
            'score' => $score,
            'max_score' => $maxScore,
            'score_percentage' => $percentage,
            'grade_letter' => $gradeLetter,
            'teacher_feedback' => $feedback,
            'graded_by' => $gradedBy ?? auth()->id(),
            'graded_at' => now(),
            'submission_status' => 'graded',
            'teacher_reviewed' => true,
        ];

        if ($latePenalty > 0) {
            $updateData['late_penalty_applied'] = true;
            $updateData['late_penalty_amount'] = $latePenalty;
        }

        if ($qualityScores) {
            $updateData = array_merge($updateData, [
                'content_quality_score' => $qualityScores['content'] ?? null,
                'presentation_score' => $qualityScores['presentation'] ?? null,
                'effort_score' => $qualityScores['effort'] ?? null,
                'creativity_score' => $qualityScores['creativity'] ?? null,
            ]);
        }

        $this->update($updateData);

        // Update homework statistics
        $homework->updateStatistics();

        return true;
    }

    public function returnToStudent(): bool
    {
        if ($this->submission_status !== 'graded') {
            return false;
        }

        $this->update([
            'submission_status' => 'returned',
            'returned_at' => now(),
        ]);

        return true;
    }

    public function requestRevision(string $reason): bool
    {
        if (!in_array($this->submission_status, ['submitted', 'late', 'graded'])) {
            return false;
        }

        $this->update([
            'submission_status' => 'revision_requested',
            'teacher_feedback' => ($this->teacher_feedback ? $this->teacher_feedback . "\n\n" : '') . "مطلوب تعديل: " . $reason,
        ]);

        return true;
    }

    public function saveDraft(?string $text = null, ?array $files = null): bool
    {
        if (!$this->can_edit) {
            return false;
        }

        $this->update([
            'submission_text' => $text,
            'submission_files' => $files,
            'submission_status' => 'draft',
            'last_edited_at' => now(),
        ]);

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
            'max_score' => $homework->max_score,
            'submission_status' => 'not_submitted',
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
            ->whereIn('submission_status', ['not_submitted', 'draft'])
            ->with(['homework.session'])
            ->get();
    }
}
