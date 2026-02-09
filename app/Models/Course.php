<?php

namespace App\Models;

use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory, ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        'subject_id',
        'grade_level_id',
        'teacher_id',
        'title',
        'description',
        'type',
        'level',
        'duration_weeks',
        'sessions_per_week',
        'session_duration_minutes',
        'max_students',
        'price',
        'currency',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'duration_weeks' => 'integer',
        'sessions_per_week' => 'integer',
        'session_duration_minutes' => 'integer',
        'max_students' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Academy relationship
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Subject relationship
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class);
    }

    /**
     * Grade level relationship
     */
    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicGradeLevel::class);
    }

    /**
     * Teacher relationship
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Students enrolled in this course
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_enrollments')
            ->withPivot(['enrolled_at', 'completed_at', 'status'])
            ->withTimestamps();
    }

    /**
     * Assignments for this course
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Quizzes for this course
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * Scope for active courses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for courses filtered by type
     */
    public function scopeForType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get enrolled students count
     */
    public function getEnrolledStudentsCountAttribute()
    {
        return $this->students()->count();
    }

    /**
     * Check if course is full
     */
    public function getIsFullAttribute()
    {
        return $this->enrolled_students_count >= $this->max_students;
    }
}
