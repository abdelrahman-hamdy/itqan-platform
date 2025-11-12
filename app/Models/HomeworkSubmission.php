<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HomeworkSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'student_id',
        'submission_code',
        'content',
        'file_path',
        'submitted_at',
        'graded_at',
        'grade',
        'teacher_feedback',
        'graded_by',
        'status',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'grade' => 'decimal:1',
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeGraded($query)
    {
        return $query->where('status', 'graded');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    // ========================================
    // Methods
    // ========================================

    /**
     * Submit the homework
     */
    public function submit(?string $content = null, ?string $filePath = null): void
    {
        $this->update([
            'content' => $content,
            'file_path' => $filePath,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);
    }

    /**
     * Grade the homework submission
     */
    public function grade(float $grade, ?string $feedback = null, int $gradedBy): void
    {
        if ($grade < 0 || $grade > 10) {
            throw new \InvalidArgumentException('Grade must be between 0 and 10');
        }

        $this->update([
            'grade' => $grade,
            'teacher_feedback' => $feedback,
            'graded_by' => $gradedBy,
            'graded_at' => now(),
            'status' => 'graded',
        ]);
    }

    /**
     * Check if submission is late
     */
    public function isLate(): bool
    {
        return $this->status === 'late';
    }

    /**
     * Check if submission is graded
     */
    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    /**
     * Check if submission is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if submission has been submitted
     */
    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'graded']);
    }

    // ========================================
    // Boot Method
    // ========================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($submission) {
            if (empty($submission->submission_code)) {
                $submission->submission_code = 'HW-' . strtoupper(uniqid());
            }

            if (empty($submission->status)) {
                $submission->status = 'pending';
            }
        });
    }
}
