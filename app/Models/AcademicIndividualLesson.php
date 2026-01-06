<?php

namespace App\Models;

use App\Enums\LessonStatus;
use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicIndividualLesson extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'academic_teacher_id',
        'student_id',
        'academic_subscription_id',
        'lesson_code',
        'name',
        'description',
        'academic_subject_id',
        'academic_grade_level_id',
        'total_sessions',
        'sessions_scheduled',
        'sessions_completed',
        'sessions_remaining',
        'lesson_topics_covered',
        'current_topics',
        'progress_percentage',
        'default_duration_minutes',
        'preferred_times',
        'status',
        'started_at',
        'completed_at',
        'last_session_at',
        'recording_enabled',
        'materials_used',
        'learning_objectives',
        'admin_notes',
        'supervisor_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => LessonStatus::class,
        'preferred_times' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_session_at' => 'datetime',
        'recording_enabled' => 'boolean',
        'materials_used' => 'array',
        'learning_objectives' => 'array',
        'progress_percentage' => 'decimal:2',
        'total_sessions' => 'integer',
        'sessions_scheduled' => 'integer',
        'sessions_completed' => 'integer',
        'sessions_remaining' => 'integer',
        'default_duration_minutes' => 'integer',
    ];

    /**
     * Boot method to auto-generate lesson code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->lesson_code)) {
                $academyId = $model->academy_id ?? 2;
                $count = static::where('academy_id', $academyId)->count() + 1;
                $model->lesson_code = 'AL-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
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

    public function academicTeacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'academic_teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function academicSubscription(): BelongsTo
    {
        return $this->belongsTo(AcademicSubscription::class);
    }

    /**
     * Alias for academicSubscription (for API compatibility)
     */
    public function subscription(): BelongsTo
    {
        return $this->academicSubscription();
    }

    public function academicSubject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class);
    }

    public function academicGradeLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicGradeLevel::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class, 'academic_individual_lesson_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function quizAssignments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(QuizAssignment::class, 'assignable');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', LessonStatus::ACTIVE->value);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('academic_teacher_id', $teacherId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Helper methods
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name.' ('.$this->lesson_code.')';
    }

    public function isActive(): bool
    {
        return $this->status === LessonStatus::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === LessonStatus::COMPLETED;
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_sessions == 0) {
            return 0;
        }

        return round(($this->sessions_completed / $this->total_sessions) * 100, 2);
    }
}
