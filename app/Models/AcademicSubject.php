<?php

namespace App\Models;

use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSubject extends Model
{
    use HasFactory, ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        'name',
        'name_en',
        'description',
        'is_active',
        'created_by',
        'admin_notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * العلاقة مع الأكاديمية
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * الشخص الذي أنشأ المادة
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * المعلمون الأكاديميون الذين يدرسون هذه المادة
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(AcademicTeacherProfile::class, 'academic_teacher_subjects', 'subject_id', 'teacher_id')
            ->withPivot(['proficiency_level', 'years_experience', 'is_primary', 'certification'])
            ->withTimestamps();
    }

    /**
     * المراحل الدراسية لهذه المادة
     */
    public function gradeLevels(): BelongsToMany
    {
        return $this->belongsToMany(AcademicGradeLevel::class, 'academic_subject_grade_levels', 'subject_id', 'grade_level_id')
            ->withPivot(['hours_per_week', 'semester', 'is_mandatory'])
            ->withTimestamps();
    }

    /**
     * الجلسات الأكاديمية الفردية لهذه المادة
     */
    public function academicIndividualLessons(): HasMany
    {
        return $this->hasMany(AcademicIndividualLesson::class, 'academic_subject_id');
    }

    /**
     * الدورات التفاعلية لهذه المادة
     */
    public function interactiveCourses(): HasMany
    {
        return $this->hasMany(InteractiveCourse::class, 'subject_id');
    }

    /**
     * الدورات المسجلة لهذه المادة
     */
    public function recordedCourses(): HasMany
    {
        return $this->hasMany(RecordedCourse::class, 'subject_id');
    }

    /**
     * نطاق المواد النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق المواد حسب الأكاديمية
     */
    public function scopeForAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * الحصول على عدد المعلمين لهذه المادة
     */
    public function getTeachersCountAttribute()
    {
        return $this->teachers()->count();
    }

    /**
     * الحصول على عدد المعلمين النشطين
     */
    public function getActiveTeachersCountAttribute()
    {
        return $this->teachers()->whereHas('user', function ($query) {
            $query->where('status', 'active');
        })->count();
    }

    /**
     * الحصول على عدد المراحل الدراسية
     */
    public function getGradeLevelsCountAttribute()
    {
        return $this->gradeLevels()->count();
    }

    /**
     * الحصول على المعلمين الأساسيين لهذه المادة
     */
    public function getPrimaryTeachersAttribute()
    {
        return $this->teachers()->wherePivot('is_primary', true)->get();
    }

    /**
     * تحديد ما إذا كانت المادة متاحة لمرحلة دراسية معينة
     */
    public function isAvailableForGradeLevel($gradeLevelId)
    {
        return $this->gradeLevels()->where('academic_grade_levels.id', $gradeLevelId)->exists();
    }

    /**
     * ربط المادة بمرحلة دراسية
     */
    public function attachGradeLevel($gradeLevelId, $data = []): void
    {
        $this->gradeLevels()->attach($gradeLevelId, array_merge([
            'hours_per_week' => 3,
            'semester' => 'both',
            'is_mandatory' => true,
        ], $data));
    }

    /**
     * الحصول على إحصائيات المادة
     */
    public function getSubjectStatsAttribute()
    {
        return [
            'teachers_count' => $this->teachers_count,
            'active_teachers_count' => $this->active_teachers_count,
            'grade_levels_count' => $this->grade_levels_count,
            'individual_lessons_count' => $this->academicIndividualLessons()->count(),
            'interactive_courses_count' => $this->interactiveCourses()->count(),
            'recorded_courses_count' => $this->recordedCourses()->count(),
        ];
    }
}
