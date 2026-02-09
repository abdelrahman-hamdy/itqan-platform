<?php

namespace App\Models;

use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $academy_id
 * @property string $name
 * @property string|null $name_en
 * @property string|null $description
 * @property string|null $description_en
 * @property bool $is_active
 * @property int|null $created_by
 * @property string|null $notes
 * @property string|null $education_system
 * @property string|null $assessment_system
 * @property array|null $grading_scale
 * @property float|null $pass_percentage
 * @property int|null $level_number
 * @property string|null $target_age_group
 * @property int|null $total_subjects
 * @property int|null $core_subjects_count
 * @property int|null $elective_subjects_count
 * @property int|null $total_credit_hours
 * @property int|null $min_credit_hours
 * @property int|null $max_credit_hours
 * @property array|null $graduation_requirements
 * @property array|null $learning_outcomes
 * @property array|null $skill_requirements
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class AcademicGradeLevel extends Model
{
    use HasFactory, ScopedToAcademy;

    protected $fillable = [
        'academy_id',
        'name',
        'name_en',
        'description',
        'description_en',
        'is_active',
        'created_by',
        'notes',
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
     * الشخص الذي أنشأ المرحلة
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * المواد الأكاديمية لهذه المرحلة
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(AcademicSubject::class, 'academic_subject_grade_levels', 'grade_level_id', 'subject_id')
            ->withPivot(['hours_per_week', 'semester', 'is_mandatory'])
            ->withTimestamps();
    }

    /**
     * المعلمون الأكاديميون الذين يدرسون في هذه المرحلة
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(AcademicTeacherProfile::class, 'academic_teacher_grade_levels', 'grade_level_id', 'teacher_id')
            ->withPivot(['years_experience', 'specialization_notes'])
            ->withTimestamps();
    }

    /**
     * الطلاب المسجلين في هذه المرحلة
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academic_student_grade_levels', 'grade_level_id', 'student_id')
            ->withPivot(['enrollment_date', 'status', 'academic_year', 'gpa', 'completion_percentage'])
            ->withTimestamps();
    }

    /**
     * الدورات التفاعلية لهذه المرحلة
     */
    public function interactiveCourses(): HasMany
    {
        return $this->hasMany(InteractiveCourse::class, 'grade_level_id');
    }

    /**
     * الدورات المسجلة لهذه المرحلة
     */
    public function recordedCourses(): HasMany
    {
        return $this->hasMany(RecordedCourse::class, 'grade_level_id');
    }

    /**
     * نطاق المراحل النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق المراحل مرتبة حسب المستوى
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('id')->orderBy('name');
    }

    /**
     * نطاق المراحل حسب النظام التعليمي
     */
    public function scopeForEducationSystem($query, $system)
    {
        return $query->where('education_system', $system);
    }

    /**
     * نطاق المراحل حسب الأكاديمية
     */
    public function scopeForAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * الحصول على النظام التعليمي بالعربية
     */
    public function getEducationSystemInArabicAttribute()
    {
        $systems = [
            'primary' => 'ابتدائي',
            'middle' => 'متوسط',
            'secondary' => 'ثانوي',
            'university' => 'جامعي',
            'vocational' => 'مهني',
            'international' => 'دولي',
            'special_needs' => 'ذوي الاحتياجات الخاصة',
        ];

        return $systems[$this->education_system] ?? $this->education_system;
    }

    /**
     * Get the display name based on current locale
     * Returns English name if locale is 'en' and name_en is set, otherwise returns Arabic name
     */
    public function getDisplayName(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'en' && ! empty($this->name_en)) {
            return $this->name_en;
        }

        return $this->name ?? __('student.profile.grade_level_default');
    }

    /**
     * الحصول على عدد المواد
     */
    public function getSubjectsCountAttribute()
    {
        return $this->subjects()->count();
    }

    /**
     * الحصول على عدد المعلمين
     */
    public function getTeachersCountAttribute()
    {
        return $this->teachers()->count();
    }

    /**
     * الحصول على عدد الطلاب النشطين
     */
    public function getActiveStudentsCountAttribute()
    {
        return $this->students()->wherePivot('status', 'active')->count();
    }

    /**
     * الحصول على المواد الأساسية
     */
    public function getCoreSubjectsAttribute()
    {
        return $this->subjects()->wherePivot('is_mandatory', true)->get();
    }

    /**
     * الحصول على المواد الاختيارية
     */
    public function getElectiveSubjectsAttribute()
    {
        return $this->subjects()->wherePivot('is_mandatory', false)->get();
    }

    /**
     * الحصول على متطلبات التخرج المنسقة
     */
    public function getFormattedGraduationRequirementsAttribute()
    {
        return collect($this->graduation_requirements ?? [])->map(function ($requirement) {
            return [
                'type' => $requirement['type'] ?? 'credit_hours',
                'description' => $requirement['description'] ?? '',
                'minimum_value' => $requirement['minimum_value'] ?? 0,
                'is_mandatory' => $requirement['is_mandatory'] ?? true,
            ];
        })->toArray();
    }

    /**
     * الحصول على مخرجات التعلم المنسقة
     */
    public function getFormattedLearningOutcomesAttribute()
    {
        return collect($this->learning_outcomes ?? [])->map(function ($outcome, $index) {
            return [
                'id' => $index + 1,
                'outcome' => $outcome['description'] ?? $outcome,
                'category' => $outcome['category'] ?? 'general',
                'assessment_method' => $outcome['assessment_method'] ?? 'multiple',
            ];
        })->toArray();
    }

    /**
     * الحصول على متطلبات المهارات المنسقة
     */
    public function getFormattedSkillRequirementsAttribute()
    {
        $skillCategories = [
            'cognitive' => 'المهارات المعرفية',
            'social' => 'المهارات الاجتماعية',
            'emotional' => 'المهارات العاطفية',
            'physical' => 'المهارات الحركية',
            'creative' => 'المهارات الإبداعية',
            'technical' => 'المهارات التقنية',
            'communication' => 'مهارات التواصل',
            'critical_thinking' => 'التفكير النقدي',
        ];

        return collect($this->skill_requirements ?? [])->map(function ($skill) use ($skillCategories) {
            return [
                'name' => $skill['name'] ?? '',
                'category' => $skillCategories[$skill['category']] ?? $skill['category'],
                'level' => $skill['level'] ?? 'basic',
                'description' => $skill['description'] ?? '',
            ];
        })->toArray();
    }

    /**
     * الحصول على نظام التقييم المنسق
     */
    public function getFormattedAssessmentSystemAttribute()
    {
        $systems = [
            'percentage' => 'النسبة المئوية',
            'letter_grade' => 'الدرجات الحرفية',
            'gpa' => 'المعدل التراكمي',
            'pass_fail' => 'نجاح/رسوب',
            'rubric' => 'المعايير',
        ];

        return $systems[$this->assessment_system] ?? $this->assessment_system;
    }

    /**
     * الحصول على سلم التقديرات المنسق
     */
    public function getFormattedGradingScaleAttribute()
    {
        if (! is_array($this->grading_scale)) {
            return $this->getDefaultGradingScale();
        }

        return collect($this->grading_scale)->map(function ($grade) {
            return [
                'letter' => $grade['letter'] ?? '',
                'percentage_min' => $grade['percentage_min'] ?? 0,
                'percentage_max' => $grade['percentage_max'] ?? 100,
                'gpa_value' => $grade['gpa_value'] ?? 0,
                'description' => $grade['description'] ?? '',
            ];
        })->toArray();
    }

    /**
     * الحصول على سلم التقديرات الافتراضي
     */
    private function getDefaultGradingScale()
    {
        return [
            ['letter' => 'أ+', 'percentage_min' => 95, 'percentage_max' => 100, 'gpa_value' => 4.0, 'description' => 'ممتاز مرتفع'],
            ['letter' => 'أ', 'percentage_min' => 90, 'percentage_max' => 94, 'gpa_value' => 3.7, 'description' => 'ممتاز'],
            ['letter' => 'ب+', 'percentage_min' => 85, 'percentage_max' => 89, 'gpa_value' => 3.3, 'description' => 'جيد جداً مرتفع'],
            ['letter' => 'ب', 'percentage_min' => 80, 'percentage_max' => 84, 'gpa_value' => 3.0, 'description' => 'جيد جداً'],
            ['letter' => 'ج+', 'percentage_min' => 75, 'percentage_max' => 79, 'gpa_value' => 2.7, 'description' => 'جيد مرتفع'],
            ['letter' => 'ج', 'percentage_min' => 70, 'percentage_max' => 74, 'gpa_value' => 2.3, 'description' => 'جيد'],
            ['letter' => 'د+', 'percentage_min' => 65, 'percentage_max' => 69, 'gpa_value' => 2.0, 'description' => 'مقبول مرتفع'],
            ['letter' => 'د', 'percentage_min' => 60, 'percentage_max' => 64, 'gpa_value' => 1.7, 'description' => 'مقبول'],
            ['letter' => 'هـ', 'percentage_min' => 0, 'percentage_max' => 59, 'gpa_value' => 0.0, 'description' => 'راسب'],
        ];
    }

    /**
     * تحديد ما إذا كانت المادة متاحة في هذه المرحلة
     */
    public function hasSubject($subjectId)
    {
        return $this->subjects()->where('academic_subjects.id', $subjectId)->exists();
    }

    /**
     * ربط المرحلة بمادة دراسية
     */
    public function attachSubject($subjectId, $data = [])
    {
        return $this->subjects()->attach($subjectId, array_merge([
            'hours_per_week' => 3,
            'semester' => 'both',
            'is_mandatory' => true,
        ], $data));
    }

    /**
     * حساب المعدل التراكمي لدرجة معينة
     */
    public function calculateGPA($percentage)
    {
        $gradingScale = $this->formatted_grading_scale;

        foreach ($gradingScale as $grade) {
            if ($percentage >= $grade['percentage_min'] && $percentage <= $grade['percentage_max']) {
                return $grade['gpa_value'];
            }
        }

        return 0.0;
    }

    /**
     * تحديد الدرجة الحرفية لنسبة معينة
     */
    public function getLetterGrade($percentage)
    {
        $gradingScale = $this->formatted_grading_scale;

        foreach ($gradingScale as $grade) {
            if ($percentage >= $grade['percentage_min'] && $percentage <= $grade['percentage_max']) {
                return $grade['letter'];
            }
        }

        return 'هـ';
    }

    /**
     * تحديد ما إذا كانت الدرجة نجاح
     */
    public function isPassingGrade($percentage)
    {
        return $percentage >= $this->pass_percentage;
    }

    /**
     * الحصول على إحصائيات المرحلة
     */
    public function getGradeLevelStatsAttribute()
    {
        return [
            'subjects_count' => $this->subjects_count,
            'core_subjects_count' => $this->core_subjects->count(),
            'elective_subjects_count' => $this->elective_subjects->count(),
            'teachers_count' => $this->teachers_count,
            'active_students_count' => $this->active_students_count,
            'interactive_courses_count' => $this->interactiveCourses()->count(),
            'recorded_courses_count' => $this->recordedCourses()->count(),
        ];
    }

    /**
     * تصدير بيانات المرحلة
     */
    public function export()
    {
        return [
            'basic_info' => [
                'name' => $this->name,
                'name_en' => $this->name_en,
                'description' => $this->description,
                'level_number' => $this->level_number,
                'education_system' => $this->education_system_in_arabic,
                'target_age_group' => $this->target_age_group,
            ],
            'academic_structure' => [
                'total_subjects' => $this->total_subjects,
                'core_subjects_count' => $this->core_subjects_count,
                'elective_subjects_count' => $this->elective_subjects_count,
                'total_credit_hours' => $this->total_credit_hours,
                'min_credit_hours' => $this->min_credit_hours,
                'max_credit_hours' => $this->max_credit_hours,
            ],
            'assessment' => [
                'assessment_system' => $this->formatted_assessment_system,
                'grading_scale' => $this->formatted_grading_scale,
                'pass_percentage' => $this->pass_percentage,
            ],
            'curriculum' => [
                'learning_outcomes' => $this->formatted_learning_outcomes,
                'skill_requirements' => $this->formatted_skill_requirements,
                'graduation_requirements' => $this->formatted_graduation_requirements,
            ],
            'statistics' => $this->grade_level_stats,
        ];
    }
}
