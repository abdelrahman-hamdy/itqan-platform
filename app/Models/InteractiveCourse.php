<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InteractiveCourse extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'assigned_teacher_id',
        'created_by',
        'updated_by',
        'title',
        'title_en',
        'description',
        'description_en',
        'language',
        'subject_id',
        'grade_level_id',
        'course_code',
        'course_type',
        'difficulty_level',
        'max_students',
        'duration_weeks',
        'sessions_per_week',
        'session_duration_minutes',
        'total_sessions',
        'student_price',
        'enrollment_fee',
        'is_enrollment_fee_required',
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
        'is_published',
        'publication_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'enrollment_deadline' => 'date',
        'publication_date' => 'datetime',
        'schedule' => 'array',
        'learning_outcomes' => 'array',
        'prerequisites' => 'array',
        'is_published' => 'boolean',
        'is_enrollment_fee_required' => 'boolean',
        'student_price' => 'decimal:2',
        'enrollment_fee' => 'decimal:2',
        'teacher_payment' => 'decimal:2',
        'teacher_fixed_amount' => 'decimal:2',
        'amount_per_student' => 'decimal:2',
        'amount_per_session' => 'decimal:2',
        'max_students' => 'integer',
        'duration_weeks' => 'integer',
        'sessions_per_week' => 'integer',
        'session_duration_minutes' => 'integer',
        'total_sessions' => 'integer',
    ];

    /**
     * Boot method to auto-generate course code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->course_code)) {
                $academyId = $model->academy_id ?? \App\Services\AcademyContextService::getCurrentAcademyId() ?? \App\Services\AcademyContextService::getDefaultAcademy()?->id ?? 2;
                $count = static::where('academy_id', $academyId)->count() + 1;
                $model->course_code = 'IC-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
            }

            // Calculate total sessions
            if ($model->duration_weeks && $model->sessions_per_week) {
                $model->total_sessions = $model->duration_weeks * $model->sessions_per_week;
            }
        });

        static::updating(function ($model) {
            // Recalculate total sessions if needed
            if ($model->isDirty(['duration_weeks', 'sessions_per_week'])) {
                $model->total_sessions = $model->duration_weeks * $model->sessions_per_week;
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    public function sessions(): HasMany
    {
        return $this->hasMany(InteractiveCourseSession::class, 'course_id');
    }

    public function teacherPayments(): HasMany
    {
        return $this->hasMany(InteractiveTeacherPayment::class, 'course_id');
    }

    public function enrolledStudents(): HasMany
    {
        return $this->hasMany(InteractiveCourseEnrollment::class, 'course_id')
            ->where('enrollment_status', 'enrolled');
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
            default => $this->course_type,
        };
    }

    public function getStatusInArabicAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'published' => 'منشور',
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
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
        return $this->enrollments()->where('enrollment_status', 'enrolled')->count();
    }

    public function getAvailableSlots(): int
    {
        return max(0, $this->max_students - $this->getCurrentEnrollmentCount());
    }

    public function isEnrollmentOpen(): bool
    {
        return $this->status === 'published'
            && $this->enrollment_deadline >= now()->toDateString()
            && $this->getAvailableSlots() > 0;
    }

    public function canStart(): bool
    {
        return $this->status === 'published'
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
        return $query->where('status', 'published')->where('is_published', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByTeacher($query, int $teacherId)
    {
        return $query->where('assigned_teacher_id', $teacherId);
    }

    public function scopeBySubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByGradeLevel($query, int $gradeLevelId)
    {
        return $query->where('grade_level_id', $gradeLevelId);
    }
}
