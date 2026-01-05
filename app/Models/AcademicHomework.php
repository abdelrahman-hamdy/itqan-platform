<?php

namespace App\Models;

use App\Enums\HomeworkStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicHomework extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'academic_homework';

    protected $fillable = [
        'academy_id',
        'academic_session_id',
        'academic_subscription_id',
        'teacher_id',
        'title',
        'description',
        'instructions',
        'learning_objectives',
        'requirements',
        'teacher_files',
        'reference_links',
        'submission_type',
        'allow_late_submissions',
        'max_files',
        'max_file_size_mb',
        'allowed_file_types',
        'assigned_at',
        'due_date',
        'estimated_duration_minutes',
        'max_score',
        'grading_scale',
        'grading_criteria',
        'auto_grade',
        'status',
        'is_active',
        'is_mandatory',
        'priority',
        'difficulty_level',
        'total_students',
        'submitted_count',
        'graded_count',
        'late_count',
        'average_score',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => HomeworkStatus::class,
        'learning_objectives' => 'array',
        'requirements' => 'array',
        'teacher_files' => 'array',
        'allowed_file_types' => 'array',
        'grading_criteria' => 'array',
        'assigned_at' => 'datetime',
        'due_date' => 'datetime',
        'estimated_duration_minutes' => 'integer',
        'max_score' => 'decimal:2',
        'average_score' => 'decimal:2',
        'auto_grade' => 'boolean',
        'allow_late_submissions' => 'boolean',
        'is_active' => 'boolean',
        'is_mandatory' => 'boolean',
        'total_students' => 'integer',
        'submitted_count' => 'integer',
        'graded_count' => 'integer',
        'late_count' => 'integer',
        'max_files' => 'integer',
        'max_file_size_mb' => 'integer',
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
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AcademicSubscription::class, 'academic_subscription_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get homework submissions for this assignment
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AcademicHomeworkSubmission::class, 'academic_homework_id');
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

    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('academic_session_id', $sessionId);
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', HomeworkStatus::PUBLISHED->value);
    }

    public function scopePublished($query)
    {
        return $query->where('status', HomeworkStatus::PUBLISHED->value);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', HomeworkStatus::PUBLISHED->value);
    }

    public function scopeDueSoon($query, int $days = 3)
    {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)])
            ->where('status', HomeworkStatus::PUBLISHED->value);
    }

    public function scopeNeedsGrading($query)
    {
        return $query->where('submitted_count', '>', 'graded_count');
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Accessors
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status === HomeworkStatus::PUBLISHED;
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    public function getSubmissionRateAttribute(): float
    {
        if ($this->total_students === 0) {
            return 0;
        }

        return round(($this->submitted_count / $this->total_students) * 100, 2);
    }

    public function getGradingProgressAttribute(): float
    {
        if ($this->submitted_count === 0) {
            return 0;
        }

        return round(($this->graded_count / $this->submitted_count) * 100, 2);
    }

    public function getPendingGradingCountAttribute(): int
    {
        return $this->submitted_count - $this->graded_count;
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'draft' => 'مسودة',
            'published' => 'منشور',
            'closed' => 'مغلق',
            'archived' => 'مؤرشف',
            default => $this->status,
        };
    }

    public function getPriorityTextAttribute(): string
    {
        return match($this->priority) {
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'عالية',
            'urgent' => 'عاجلة',
            default => $this->priority,
        };
    }

    public function getDifficultyLevelTextAttribute(): ?string
    {
        if (!$this->difficulty_level) {
            return null;
        }

        return match($this->difficulty_level) {
            'beginner' => 'مبتدئ',
            'intermediate' => 'متوسط',
            'advanced' => 'متقدم',
            'expert' => 'خبير',
            default => $this->difficulty_level,
        };
    }

    public function getSubmissionTypeTextAttribute(): string
    {
        return match($this->submission_type) {
            'text' => 'نص فقط',
            'file' => 'ملف فقط',
            'both' => 'نص وملف',
            default => $this->submission_type,
        };
    }

    public function getGradingScaleTextAttribute(): string
    {
        return match($this->grading_scale) {
            'points' => 'نقاط',
            'percentage' => 'نسبة مئوية',
            'letter' => 'حروف',
            'pass_fail' => 'نجاح/رسوب',
            default => $this->grading_scale,
        };
    }

    /**
     * Helper Methods
     */
    public function publish(): bool
    {
        if ($this->status !== HomeworkStatus::DRAFT) {
            return false;
        }

        $this->update([
            'status' => HomeworkStatus::PUBLISHED,
            'assigned_at' => now(),
        ]);

        return true;
    }

    public function close(): bool
    {
        if ($this->status !== HomeworkStatus::PUBLISHED) {
            return false;
        }

        $this->update(['status' => HomeworkStatus::IN_PROGRESS]);

        return true;
    }

    public function archive(): bool
    {
        $this->update(['status' => HomeworkStatus::ARCHIVED, 'is_active' => false]);

        return true;
    }

    public function updateStatistics(): void
    {
        $submissions = $this->submissions();

        $this->update([
            'submitted_count' => $submissions->whereIn('submission_status', ['submitted', 'late', 'graded', 'returned'])->count(),
            'graded_count' => $submissions->whereIn('submission_status', ['graded', 'returned'])->count(),
            'late_count' => $submissions->where('is_late', true)->count(),
            'average_score' => $submissions->whereNotNull('score')->avg('score'),
        ]);
    }

    /**
     * Get submission for a student
     */
    public function getSubmissionForStudent(int $studentId): ?AcademicHomeworkSubmission
    {
        return $this->submissions()->where('student_id', $studentId)->first();
    }

    public function hasSubmissionFrom(int $studentId): bool
    {
        return $this->submissions()
            ->where('student_id', $studentId)
            ->whereIn('submission_status', ['submitted', 'late', 'graded', 'returned'])
            ->exists();
    }

    public function canBeSubmittedBy(int $studentId): bool
    {
        if ($this->status !== HomeworkStatus::PUBLISHED) {
            return false;
        }

        if (!$this->is_active) {
            return false;
        }

        // Check if already submitted
        if ($this->hasSubmissionFrom($studentId)) {
            return false;
        }

        // Check if overdue and late submissions not allowed
        if ($this->is_overdue && !$this->allow_late_submissions) {
            return false;
        }

        return true;
    }

    /**
     * Static Methods
     */
    public static function createForSession(AcademicSession $session, array $data): self
    {
        return self::create(array_merge($data, [
            'academy_id' => $session->academy_id,
            'academic_session_id' => $session->id,
            'academic_subscription_id' => $session->academic_subscription_id,
            'teacher_id' => $session->academic_teacher_id,
            'assigned_at' => now(),
            'status' => HomeworkStatus::PUBLISHED,
        ]));
    }

    public static function getUpcomingForStudent(int $studentId, int $academyId, int $limit = 5)
    {
        return self::query()
            ->where('academy_id', $academyId)
            ->where('status', HomeworkStatus::PUBLISHED->value)
            ->where('is_active', true)
            ->whereHas('session.academicSubscription', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->whereDoesntHave('submissions', function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                    ->whereIn('submission_status', ['submitted', 'late', 'graded', 'returned']);
            })
            ->orderBy('due_date', 'asc')
            ->limit($limit)
            ->get();
    }

    public static function getForTeacher(int $teacherId, int $academyId, ?string $status = null)
    {
        $query = self::query()
            ->where('academy_id', $academyId)
            ->where('teacher_id', $teacherId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('due_date', 'desc')->get();
    }
}
