<?php

namespace App\Models;

use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Enums\CertificateTemplateStyle;
use App\Enums\EnrollmentStatus;
use App\Enums\InteractiveCourseStatus;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InteractiveCourse extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'assigned_teacher_id',
        'title',
        'description',
        'subject_id',
        'grade_level_id',
        'course_code',
        'difficulty_level',
        'max_students',
        'duration_weeks',
        'sessions_per_week',
        'session_duration_minutes',
        'total_sessions',
        'student_price',
        'teacher_payment',
        'payment_type',
        'teacher_fixed_amount',
        'amount_per_student',
        'amount_per_session',
        'start_date',
        'end_date',
        'enrollment_deadline',
        'schedule',
        'learning_outcomes',
        'prerequisites',
        'course_outline',
        'status',
        // SECURITY: avg_rating, total_reviews excluded from fillable — system-calculated
        'is_published',
        'publication_date',
        'certificate_enabled',
        'certificate_template_style',
        'recording_enabled',
        'supervisor_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'enrollment_deadline' => 'date',
        'publication_date' => 'datetime',
        'schedule' => 'array',
        'learning_outcomes' => 'array',
        'prerequisites' => 'array',
        'status' => InteractiveCourseStatus::class,
        'is_published' => 'boolean',
        'student_price' => 'decimal:2',
        'teacher_payment' => 'decimal:2',
        'teacher_fixed_amount' => 'decimal:2',
        'amount_per_student' => 'decimal:2',
        'amount_per_session' => 'decimal:2',
        'max_students' => 'integer',
        'duration_weeks' => 'integer',
        'sessions_per_week' => 'integer',
        'session_duration_minutes' => 'integer',
        'total_sessions' => 'integer',
        'certificate_enabled' => 'boolean',
        'certificate_template_style' => CertificateTemplateStyle::class,
        'recording_enabled' => 'boolean',
        'avg_rating' => 'decimal:2',
        'total_reviews' => 'integer',
    ];

    /**
     * Boot method to auto-generate course code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->course_code)) {
                $academyId = $model->academy_id ?? AcademyContextService::getCurrentAcademyId() ?? AcademyContextService::getDefaultAcademy()?->id ?? 2;
                $count = static::where('academy_id', $academyId)->count() + 1;
                $model->course_code = 'IC-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
            }

            // Calculate duration_weeks based on total_sessions and sessions_per_week
            if ($model->total_sessions && $model->sessions_per_week) {
                $model->duration_weeks = ceil($model->total_sessions / $model->sessions_per_week);
            }

            // Calculate end_date based on start_date and duration_weeks
            if ($model->start_date && $model->duration_weeks) {
                $model->end_date = $model->start_date->copy()->addWeeks($model->duration_weeks);
            }
        });

        static::updating(function ($model) {
            // Recalculate duration_weeks if total_sessions or sessions_per_week changed
            if ($model->isDirty(['total_sessions', 'sessions_per_week'])) {
                if ($model->total_sessions && $model->sessions_per_week) {
                    $model->duration_weeks = ceil($model->total_sessions / $model->sessions_per_week);
                }
            }

            // Recalculate end_date if start_date or duration_weeks changed
            if ($model->isDirty(['start_date', 'duration_weeks'])) {
                if ($model->start_date && $model->duration_weeks) {
                    $model->end_date = $model->start_date->copy()->addWeeks($model->duration_weeks);
                }
            }
        });
    }

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function assignedTeacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'assigned_teacher_id');
    }

    /**
     * Alias for assignedTeacher (for API compatibility)
     */
    public function teacher(): BelongsTo
    {
        return $this->assignedTeacher();
    }

    /**
     * Alias for subject (category is another name for subject in course context)
     */
    public function category(): BelongsTo
    {
        return $this->subject();
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicGradeLevel::class, 'grade_level_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(InteractiveCourseEnrollment::class, 'course_id');
    }

    /**
     * Get all reviews for this course
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(CourseReview::class, 'reviewable');
    }

    /**
     * Get only approved reviews
     */
    public function approvedReviews(): MorphMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    /**
     * Check if a user has already reviewed this course
     */
    public function hasReviewFrom(int $userId): bool
    {
        return $this->reviews()->where('user_id', $userId)->exists();
    }

    /**
     * Update review statistics
     */
    public function updateReviewStats(): void
    {
        $stats = $this->approvedReviews()
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_reviews')
            ->first();

        $this->update([
            'avg_rating' => round($stats->avg_rating ?? 0, 2),
            'total_reviews' => $stats->total_reviews ?? 0,
        ]);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(InteractiveCourseSession::class, 'course_id');
    }

    public function enrolledStudents(): HasMany
    {
        return $this->hasMany(InteractiveCourseEnrollment::class, 'course_id')
            ->where('enrollment_status', EnrollmentStatus::ENROLLED);
    }

    public function quizAssignments(): MorphMany
    {
        return $this->morphMany(QuizAssignment::class, 'assignable');
    }

    /**
     * Get all quizzes assigned to this course through quiz assignments
     */
    public function quizzes(): HasManyThrough
    {
        return $this->hasManyThrough(
            Quiz::class,
            QuizAssignment::class,
            'assignable_id',
            'id',
            'id',
            'quiz_id'
        )->where('assignable_type', self::class);
    }

    /**
     * Helper methods
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->title.' ('.$this->course_code.')';
    }

    public function getCourseTypeInArabicAttribute(): string
    {
        return match ($this->course_type) {
            'intensive' => 'مكثف',
            'regular' => 'منتظم',
            'exam_prep' => 'تحضير للامتحانات',
            default => $this->course_type ?? 'غير محدد',
        };
    }

    public function getStatusInArabicAttribute(): string
    {
        return $this->status->label();
    }

    public function getPaymentTypeInArabicAttribute(): string
    {
        return match ($this->payment_type) {
            'fixed_amount' => 'مبلغ ثابت',
            'per_student' => 'لكل طالب',
            'per_session' => 'لكل جلسة',
            default => $this->payment_type,
        };
    }

    public function getCurrentEnrollmentCount(): int
    {
        return $this->enrollments()->where('enrollment_status', EnrollmentStatus::ENROLLED)->count();
    }

    public function getAvailableSlots(): int
    {
        return max(0, $this->max_students - $this->getCurrentEnrollmentCount());
    }

    public function isEnrollmentOpen(): bool
    {
        // Check if course status allows enrollment and has available slots
        if (! $this->status->allowsEnrollment() || $this->getAvailableSlots() <= 0) {
            return false;
        }

        // If no enrollment deadline set, allow enrollment as long as course isn't completed
        if ($this->enrollment_deadline === null) {
            return true;
        }

        // Otherwise, check if we're before the deadline
        return $this->enrollment_deadline >= now()->toDateString();
    }

    public function canStart(): bool
    {
        return $this->status->allowsEnrollment()
            && $this->start_date <= now()->toDateString()
            && $this->getCurrentEnrollmentCount() > 0;
    }

    /**
     * Scopes
     */
    public function scopeForAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopePublished($query)
    {
        return $query->where('status', InteractiveCourseStatus::PUBLISHED)->where('is_published', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', InteractiveCourseStatus::ACTIVE);
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('assigned_teacher_id', $teacherId);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForGradeLevel($query, int $gradeLevelId)
    {
        return $query->where('grade_level_id', $gradeLevelId);
    }
}
