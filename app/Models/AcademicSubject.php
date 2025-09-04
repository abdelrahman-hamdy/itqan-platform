<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AcademicSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'name_en',
        'description',
        'category',
        'field',
        'level_scope',
        'prerequisites',
        'color_code',
        'icon',
        'is_core_subject',
        'is_elective',
        'credit_hours',
        'difficulty_level',
        'estimated_duration_weeks',
        'curriculum_framework',
        'learning_objectives',
        'assessment_methods',
        'required_materials',
        'is_active',
        'display_order',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'is_core_subject' => 'boolean',
        'is_elective' => 'boolean',
        'is_active' => 'boolean',
        'credit_hours' => 'integer',
        'difficulty_level' => 'integer',
        'estimated_duration_weeks' => 'integer',
        'display_order' => 'integer',
        'prerequisites' => 'array',
        'level_scope' => 'array',
        'learning_objectives' => 'array',
        'assessment_methods' => 'array',
        'required_materials' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_core_subject' => true,
        'is_elective' => false,
        'difficulty_level' => 1,
        'credit_hours' => 3,
        'estimated_duration_weeks' => 16,
        'display_order' => 0,
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
     * الجلسات الخاصة لهذه المادة
     */
    public function privateSessions(): HasMany
    {
        return $this->hasMany(PrivateSession::class, 'subject_id');
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
     * نطاق المواد حسب الفئة
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * نطاق المواد حسب المجال
     */
    public function scopeByField($query, $field)
    {
        return $query->where('field', $field);
    }

    /**
     * نطاق المواد الأساسية
     */
    public function scopeCoreSubjects($query)
    {
        return $query->where('is_core_subject', true);
    }

    /**
     * نطاق المواد الاختيارية
     */
    public function scopeElectiveSubjects($query)
    {
        return $query->where('is_elective', true);
    }

    /**
     * نطاق المواد حسب الأكاديمية
     */
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * نطاق المواد حسب مستوى الصعوبة
     */
    public function scopeByDifficulty($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * الحصول على فئة المادة بالعربية
     */
    public function getCategoryInArabicAttribute()
    {
        $categories = [
            'sciences' => 'العلوم',
            'mathematics' => 'الرياضيات',
            'languages' => 'اللغات',
            'humanities' => 'العلوم الإنسانية',
            'social_studies' => 'الدراسات الاجتماعية',
            'arts' => 'الفنون',
            'technology' => 'التكنولوجيا',
            'physical_education' => 'التربية البدنية',
            'religious_studies' => 'الدراسات الدينية',
            'vocational' => 'المهني',
        ];

        return $categories[$this->category] ?? $this->category;
    }

    /**
     * الحصول على المجال بالعربية
     */
    public function getFieldInArabicAttribute()
    {
        $fields = [
            'natural_sciences' => 'العلوم الطبيعية',
            'applied_sciences' => 'العلوم التطبيقية',
            'formal_sciences' => 'العلوم الصورية',
            'humanities' => 'العلوم الإنسانية',
            'social_sciences' => 'العلوم الاجتماعية',
            'interdisciplinary' => 'متعدد التخصصات',
        ];

        return $fields[$this->field] ?? $this->field;
    }

    /**
     * الحصول على مستوى الصعوبة بالعربية
     */
    public function getDifficultyLevelInArabicAttribute()
    {
        $levels = [
            1 => 'مبتدئ',
            2 => 'متوسط',
            3 => 'متقدم',
            4 => 'خبير',
            5 => 'متقن',
        ];

        return $levels[$this->difficulty_level] ?? 'غير محدد';
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
        return $this->teachers()->whereHas('user', function($query) {
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
     * الحصول على أهداف التعلم المنسقة
     */
    public function getFormattedLearningObjectivesAttribute()
    {
        if (!is_array($this->learning_objectives)) {
            return [];
        }

        return collect($this->learning_objectives)->map(function($objective, $index) {
            return [
                'id' => $index + 1,
                'objective' => $objective,
                'category' => $this->detectObjectiveCategory($objective),
            ];
        })->toArray();
    }

    /**
     * تحديد فئة الهدف التعليمي
     */
    private function detectObjectiveCategory($objective)
    {
        $keywords = [
            'knowledge' => ['يعرف', 'يحدد', 'يذكر', 'يسمي', 'يصف'],
            'comprehension' => ['يفهم', 'يفسر', 'يوضح', 'يشرح', 'يقارن'],
            'application' => ['يطبق', 'يستخدم', 'يحل', 'ينفذ', 'يمارس'],
            'analysis' => ['يحلل', 'يفحص', 'يميز', 'يصنف', 'يقيم'],
            'synthesis' => ['ينشئ', 'يصمم', 'يطور', 'يبتكر', 'يركب'],
            'evaluation' => ['يقيم', 'ينقد', 'يحكم', 'يختار', 'يبرر'],
        ];

        foreach ($keywords as $category => $words) {
            foreach ($words as $word) {
                if (str_contains($objective, $word)) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    /**
     * الحصول على طرق التقييم المنسقة
     */
    public function getFormattedAssessmentMethodsAttribute()
    {
        $methods = [
            'written_exam' => 'اختبار كتابي',
            'oral_exam' => 'اختبار شفهي',
            'practical_exam' => 'اختبار عملي',
            'project' => 'مشروع',
            'assignment' => 'واجب',
            'presentation' => 'عرض تقديمي',
            'portfolio' => 'ملف إنجاز',
            'continuous_assessment' => 'تقييم مستمر',
        ];

        return collect($this->assessment_methods ?? [])->map(function($method) use ($methods) {
            return $methods[$method] ?? $method;
        })->toArray();
    }

    /**
     * الحصول على المواد المطلوبة المنسقة
     */
    public function getFormattedRequiredMaterialsAttribute()
    {
        return collect($this->required_materials ?? [])->map(function($material) {
            return [
                'name' => $material['name'] ?? '',
                'type' => $material['type'] ?? 'book',
                'required' => $material['required'] ?? true,
                'url' => $material['url'] ?? null,
            ];
        })->toArray();
    }

    /**
     * تحديد ما إذا كانت المادة متاحة لمرحلة دراسية معينة
     */
    public function isAvailableForGradeLevel($gradeLevelId)
    {
        return $this->gradeLevels()->where('academic_grade_levels.id', $gradeLevelId)->exists();
    }

    /**
     * تحديد ما إذا كان لدى المادة متطلبات سابقة
     */
    public function hasPrerequisites()
    {
        return !empty($this->prerequisites);
    }

    /**
     * الحصول على المتطلبات السابقة كنماذج
     */
    public function getPrerequisiteSubjects()
    {
        if (!$this->hasPrerequisites()) {
            return collect([]);
        }

        return static::whereIn('id', $this->prerequisites)->get();
    }

    /**
     * ربط المادة بمرحلة دراسية
     */
    public function attachGradeLevel($gradeLevelId, $data = [])
    {
        return $this->gradeLevels()->attach($gradeLevelId, array_merge([
            'hours_per_week' => 3,
            'semester' => 'both',
            'is_mandatory' => $this->is_core_subject,
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
            'private_sessions_count' => $this->privateSessions()->count(),
            'interactive_courses_count' => $this->interactiveCourses()->count(),
            'recorded_courses_count' => $this->recordedCourses()->count(),
        ];
    }

    /**
     * تصدير بيانات المادة
     */
    public function export()
    {
        return [
            'basic_info' => [
                'name' => $this->name,
                'name_en' => $this->name_en,
                'description' => $this->description,
                'category' => $this->category_in_arabic,
                'field' => $this->field_in_arabic,
            ],
            'academic_info' => [
                'credit_hours' => $this->credit_hours,
                'difficulty_level' => $this->difficulty_level_in_arabic,
                'estimated_duration_weeks' => $this->estimated_duration_weeks,
                'is_core_subject' => $this->is_core_subject,
                'is_elective' => $this->is_elective,
            ],
            'curriculum' => [
                'learning_objectives' => $this->formatted_learning_objectives,
                'assessment_methods' => $this->formatted_assessment_methods,
                'required_materials' => $this->formatted_required_materials,
                'prerequisites' => $this->getPrerequisiteSubjects()->pluck('name'),
            ],
            'statistics' => $this->subject_stats,
        ];
    }
} 