<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AcademicTeacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'academy_id',
        'teacher_code',
        'education_level',
        'university',
        'graduation_year',
        'qualification_degree',
        'teaching_experience_years',
        'certifications',
        'languages',
        'available_days',
        'available_time_start',
        'available_time_end',
        'session_price_individual',
        'min_session_duration',
        'max_session_duration',
        'max_students_per_group',
        'bio_arabic',
        'bio_english',
        'is_approved',
        'approval_date',
        'approved_by',
        'status',
        'rating',
        'total_students',
        'total_sessions',
        'total_courses_created',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'graduation_year' => 'integer',
        'teaching_experience_years' => 'integer',
        'certifications' => 'array',
        'languages' => 'array',
        'available_days' => 'array',
        'available_time_start' => 'datetime:H:i',
        'available_time_end' => 'datetime:H:i',
        'approval_date' => 'datetime',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
        // 'can_create_courses' => 'boolean', // Removed - column doesn't exist in table
        'session_price_individual' => 'decimal:2',
        'rating' => 'decimal:2',
        'min_session_duration' => 'integer',
        'max_session_duration' => 'integer',
        'max_students_per_group' => 'integer',
        'total_students' => 'integer',
        'total_sessions' => 'integer',
        'total_courses_created' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
        'is_active' => true,
        // 'can_create_courses' => false, // Removed - column doesn't exist in table
        'min_session_duration' => 45,
        'max_session_duration' => 90,
        'max_students_per_group' => 6,
        'session_price_individual' => 0,
        // 'session_price_group' => 0, // Removed - column doesn't exist in table
        'rating' => 0,
        'total_students' => 0,
        'total_sessions' => 0,
        'total_courses_created' => 0,
    ];

    /**
     * العلاقة مع المستخدم الأساسي
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة مع الأكاديمية
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * المواد التي يدرسها المعلم
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'academic_teacher_subjects', 'teacher_id', 'subject_id')
                    ->withPivot(['proficiency_level', 'years_experience', 'is_primary', 'certification'])
                    ->withTimestamps();
    }

    /**
     * المراحل الدراسية التي يدرسها المعلم
     */
    public function gradeLevels(): BelongsToMany
    {
        return $this->belongsToMany(AcademicGradeLevel::class, 'academic_teacher_grade_levels', 'teacher_id', 'grade_level_id')
                    ->withPivot(['years_experience', 'specialization_notes'])
                    ->withTimestamps();
    }

    /**
     * الجلسات الخاصة
     */
    public function privateSessions(): HasMany
    {
        return $this->hasMany(PrivateSession::class, 'teacher_id');
    }

    /**
     * الدورات التفاعلية
     */
    public function interactiveCourses(): HasMany
    {
        return $this->hasMany(InteractiveCourse::class, 'teacher_id');
    }

    /**
     * الدورات المسجلة التي أنشأها
     */
    public function recordedCourses(): HasMany
    {
        return $this->hasMany(RecordedCourse::class, 'instructor_id');
    }

    /**
     * الطلاب المرتبطون بهذا المعلم
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academic_teacher_students', 'teacher_id', 'student_id')
                    ->withPivot(['start_date', 'end_date', 'status', 'current_subjects', 'performance_rating'])
                    ->withTimestamps();
    }

    /**
     * اشتراكات الطلاب مع هذا المعلم
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(AcademicSubscription::class, 'teacher_id');
    }

    /**
     * المعلم الذي وافق على هذا المعلم
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * نطاق المعلمين المعتمدين
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * نطاق المعلمين النشطين
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق المعلمين حسب مجال التخصص
     */
    public function scopeBySpecialization($query, $field)
    {
        return $query->where('specialization_field', $field);
    }

    /**
     * نطاق المعلمين حسب الأكاديمية
     */
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * نطاق المعلمين الذين يدرسون مادة معينة
     */
    public function scopeBySubject($query, $subjectId)
    {
        return $query->whereHas('subjects', function($q) use ($subjectId) {
            $q->where('academic_subjects.id', $subjectId);
        });
    }

    /**
     * نطاق المعلمين الذين يدرسون مرحلة معينة
     */
    public function scopeByGradeLevel($query, $gradeLevelId)
    {
        return $query->whereHas('gradeLevels', function($q) use ($gradeLevelId) {
            $q->where('academic_grade_levels.id', $gradeLevelId);
        });
    }

    /**
     * الحصول على الاسم الكامل للمعلم
     */
    public function getFullNameAttribute()
    {
        return $this->user->full_name;
    }

    /**
     * الحصول على عدد الطلاب النشطين
     */
    public function getActiveStudentsCountAttribute()
    {
        return $this->students()->wherePivot('status', 'active')->count();
    }

    /**
     * الحصول على متوسط التقييم
     */
    public function getAverageRatingAttribute()
    {
        return round($this->rating, 1);
    }

    /**
     * الحصول على مجال التخصص بالعربية
     */
    public function getSpecializationFieldInArabicAttribute()
    {
        $fields = [
            'mathematics' => 'الرياضيات',
            'physics' => 'الفيزياء',
            'chemistry' => 'الكيمياء',
            'biology' => 'الأحياء',
            'arabic_language' => 'اللغة العربية',
            'english_language' => 'اللغة الإنجليزية',
            'history' => 'التاريخ',
            'geography' => 'الجغرافيا',
            'islamic_studies' => 'التربية الإسلامية',
            'computer_science' => 'علوم الحاسوب',
            'art' => 'التربية الفنية',
            'music' => 'التربية الموسيقية',
            'physical_education' => 'التربية البدنية',
            'philosophy' => 'الفلسفة',
            'psychology' => 'علم النفس',
            'sociology' => 'علم الاجتماع',
            'economics' => 'الاقتصاد',
        ];

        return $fields[$this->specialization_field] ?? $this->specialization_field;
    }

    /**
     * الحصول على مستوى التعليم بالعربية
     */
    public function getEducationLevelInArabicAttribute()
    {
        $levels = [
            'bachelor' => 'بكالوريوس',
            'master' => 'ماجستير',
            'phd' => 'دكتوراه',
            'diploma' => 'دبلوم',
        ];

        return $levels[$this->education_level] ?? $this->education_level;
    }

    /**
     * تحديد ما إذا كان المعلم متاحاً في يوم معين
     */
    public function isAvailableOnDay($day)
    {
        return in_array($day, $this->available_days ?? []);
    }

    /**
     * الحصول على الأوقات المتاحة للمعلم
     */
    public function getAvailableTimeSlotsAttribute()
    {
        return $this->available_times ?? [];
    }

    /**
     * الحصول على المواد الأساسية للمعلم
     */
    public function getPrimarySubjectsAttribute()
    {
        return $this->subjects()->wherePivot('is_primary', true)->get();
    }

    /**
     * الحصول على المواد الثانوية للمعلم
     */
    public function getSecondarySubjectsAttribute()
    {
        return $this->subjects()->wherePivot('is_primary', false)->get();
    }

    /**
     * تحديد ما إذا كان المعلم مؤهل لتدريس مادة معينة
     */
    public function canTeachSubject($subjectId)
    {
        return $this->subjects()->where('academic_subjects.id', $subjectId)->exists();
    }

    /**
     * تحديد ما إذا كان المعلم مؤهل لتدريس مرحلة معينة
     */
    public function canTeachGradeLevel($gradeLevelId)
    {
        return $this->gradeLevels()->where('academic_grade_levels.id', $gradeLevelId)->exists();
    }

    /**
     * الحصول على اللغات التي يدرس بها المعلم
     */
    public function getTeachingLanguagesAttribute()
    {
        $languageMap = [
            'ar' => 'العربية',
            'en' => 'الإنجليزية',
            'fr' => 'الفرنسية',
            'de' => 'الألمانية',
            'es' => 'الإسبانية',
        ];

        return collect($this->languages ?? [])->map(function($lang) use ($languageMap) {
            return $languageMap[$lang] ?? $lang;
        })->toArray();
    }

    /**
     * الحصول على الشهادات بتنسيق منظم
     */
    public function getFormattedCertificationsAttribute()
    {
        return collect($this->certifications ?? [])->map(function($cert) {
            return [
                'name' => $cert['name'] ?? '',
                'issuer' => $cert['issuer'] ?? '',
                'year' => $cert['year'] ?? '',
                'expiry' => $cert['expiry'] ?? null,
            ];
        })->toArray();
    }

    /**
     * الحصول على طرق التدريس المفضلة بالعربية
     */
    public function getPreferredTeachingMethodsInArabicAttribute()
    {
        $methods = [
            'lecture' => 'المحاضرة التقليدية',
            'interactive' => 'التفاعلي',
            'problem_solving' => 'حل المشكلات',
            'project_based' => 'التعلم القائم على المشاريع',
            'collaborative' => 'التعلم التشاركي',
            'flipped_classroom' => 'الفصل المقلوب',
            'gamification' => 'التلعيب',
            'visual_learning' => 'التعلم البصري',
        ];

        return collect($this->preferred_teaching_methods ?? [])->map(function($method) use ($methods) {
            return $methods[$method] ?? $method;
        })->toArray();
    }

    /**
     * ربط المعلم بمادة دراسية
     */
    public function attachSubject($subjectId, $data = [])
    {
        return $this->subjects()->attach($subjectId, array_merge([
            'proficiency_level' => 'intermediate',
            'years_experience' => $this->teaching_experience_years,
            'is_primary' => false,
        ], $data));
    }

    /**
     * ربط المعلم بمرحلة دراسية
     */
    public function attachGradeLevel($gradeLevelId, $data = [])
    {
        return $this->gradeLevels()->attach($gradeLevelId, array_merge([
            'years_experience' => $this->teaching_experience_years,
        ], $data));
    }

    /**
     * الحصول على إحصائيات المعلم
     */
    public function getTeacherStatsAttribute()
    {
        return [
            'total_students' => $this->total_students,
            'active_students' => $this->active_students_count,
            'total_sessions' => $this->total_sessions,
            'total_courses' => $this->total_courses_created,
            'subjects_count' => $this->subjects->count(),
            'grade_levels_count' => $this->gradeLevels->count(),
            'average_rating' => $this->average_rating,
        ];
    }
} 