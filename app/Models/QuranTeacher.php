<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuranTeacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'academy_id',
        'teacher_code',
        'specialization',
        'has_ijazah',
        'ijazah_type',
        'ijazah_from',
        'ijazah_date',
        'memorization_level',
        'teaching_experience_years',
        'preferred_age_groups',
        'teaching_methods',
        'available_days',
        'available_times',
        'session_price_individual',
        'session_price_group',
        'min_session_duration',
        'max_session_duration',
        'max_students_per_circle',
        'bio_arabic',
        'bio_english',
        'is_approved',
        'approval_date',
        'approved_by',
        'status',
        'rating',
        'total_students',
        'total_sessions',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'has_ijazah' => 'boolean',
        'ijazah_date' => 'date',
        'approval_date' => 'datetime',
        'preferred_age_groups' => 'array',
        'teaching_methods' => 'array',
        'available_days' => 'array',
        'available_times' => 'array',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
        'session_price_individual' => 'decimal:2',
        'session_price_group' => 'decimal:2',
        'rating' => 'decimal:2',
        'teaching_experience_years' => 'integer',
        'min_session_duration' => 'integer',
        'max_session_duration' => 'integer',
        'max_students_per_circle' => 'integer',
        'total_students' => 'integer',
        'total_sessions' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
        'is_active' => true,
        'min_session_duration' => 30,
        'max_session_duration' => 60,
        'max_students_per_circle' => 8,
        'session_price_individual' => 0,
        'session_price_group' => 0,
        'rating' => 0,
        'total_students' => 0,
        'total_sessions' => 0,
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
     * الجلسات الفردية
     */
    public function individualSessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'teacher_id');
    }

    /**
     * الحلقات الجماعية
     */
    public function circles(): HasMany
    {
        return $this->hasMany(QuranCircle::class, 'teacher_id');
    }

    /**
     * الطلاب المرتبطون بهذا المعلم
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'quran_teacher_students', 'teacher_id', 'student_id')
                    ->withPivot(['start_date', 'end_date', 'status', 'current_surah', 'verses_memorized'])
                    ->withTimestamps();
    }

    /**
     * اشتراكات القرآن لهذا المعلم
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(QuranSubscription::class, 'teacher_id');
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
     * نطاق المعلمين حسب التخصص
     */
    public function scopeBySpecialization($query, $specialization)
    {
        return $query->where('specialization', $specialization);
    }

    /**
     * نطاق المعلمين حسب الأكاديمية
     */
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
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
     * تحديد ما إذا كان المعلم لديه إجازة
     */
    public function hasValidIjazah()
    {
        return $this->has_ijazah && 
               !empty($this->ijazah_type) && 
               !empty($this->ijazah_from) && 
               $this->ijazah_date;
    }

    /**
     * الحصول على تخصصات التدريس
     */
    public function getSpecializationOptionsAttribute()
    {
        return [
            'memorization' => 'التحفيظ',
            'tajweed' => 'التجويد',
            'recitation' => 'التلاوة',
            'interpretation' => 'التفسير',
            'arabic_language' => 'اللغة العربية القرآنية',
            'islamic_studies' => 'الدراسات الإسلامية',
        ];
    }

    /**
     * الحصول على طرق التدريس المتاحة
     */
    public function getTeachingMethodsOptionsAttribute()
    {
        return [
            'traditional' => 'الطريقة التقليدية',
            'modern' => 'الطرق الحديثة',
            'mixed' => 'مختلطة',
            'interactive' => 'تفاعلية',
            'gamification' => 'التلعيب',
        ];
    }
} 