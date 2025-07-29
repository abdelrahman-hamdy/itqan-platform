<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranCircle extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'teacher_id',
        'supervisor_id',
        'circle_code',
        'name',
        'description',
        'circle_type',
        'level',
        'age_group_min',
        'age_group_max',
        'max_students',
        'current_students_count',
        'schedule_days',
        'schedule_times',
        'session_duration_minutes',
        'start_date',
        'end_date',
        'monthly_fee',
        'currency',
        'status',
        'is_active',
        'google_calendar_id',
        'meeting_room_url',
        'circle_objectives',
        'current_curriculum',
        'progress_tracking',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'age_group_min' => 'integer',
        'age_group_max' => 'integer',
        'max_students' => 'integer',
        'current_students_count' => 'integer',
        'session_duration_minutes' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_fee' => 'decimal:2',
        'schedule_days' => 'array',
        'schedule_times' => 'array',
        'circle_objectives' => 'array',
        'current_curriculum' => 'array',
        'progress_tracking' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'active',
        'is_active' => true,
        'max_students' => 8,
        'current_students_count' => 0,
        'session_duration_minutes' => 60,
        'level' => 'beginner',
        'circle_type' => 'memorization',
    ];

    /**
     * العلاقة مع الأكاديمية
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * العلاقة مع معلم القرآن
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(QuranTeacher::class, 'teacher_id');
    }

    /**
     * العلاقة مع المشرف
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * الشخص الذي أنشأ الحلقة
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * الطلاب المسجلين في الحلقة
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'quran_circle_students', 'circle_id', 'student_id')
                    ->withPivot([
                        'enrollment_date', 
                        'status', 
                        'current_level', 
                        'total_verses_memorized',
                        'last_surah',
                        'last_ayah',
                        'attendance_percentage',
                        'performance_rating',
                        'notes'
                    ])
                    ->withTimestamps();
    }

    /**
     * جلسات الحلقة
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(QuranCircleSession::class, 'circle_id');
    }

    /**
     * اشتراكات الطلاب في هذه الحلقة
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(QuranSubscription::class, 'circle_id');
    }

    /**
     * نطاق الحلقات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * نطاق الحلقات حسب المعلم
     */
    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * نطاق الحلقات حسب المستوى
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * نطاق الحلقات حسب الفئة العمرية
     */
    public function scopeByAgeGroup($query, $minAge, $maxAge)
    {
        return $query->where('age_group_min', '>=', $minAge)
                    ->where('age_group_max', '<=', $maxAge);
    }

    /**
     * نطاق الحلقات حسب الأكاديمية
     */
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * نطاق الحلقات التي لديها مقاعد متاحة
     */
    public function scopeHasAvailableSeats($query)
    {
        return $query->whereColumn('current_students_count', '<', 'max_students');
    }

    /**
     * الحصول على عدد المقاعد المتاحة
     */
    public function getAvailableSeatsAttribute()
    {
        return $this->max_students - $this->current_students_count;
    }

    /**
     * الحصول على نسبة امتلاء الحلقة
     */
    public function getOccupancyPercentageAttribute()
    {
        if ($this->max_students == 0) return 0;
        return round(($this->current_students_count / $this->max_students) * 100, 1);
    }

    /**
     * تحديد ما إذا كانت الحلقة ممتلئة
     */
    public function isFullAttribute()
    {
        return $this->current_students_count >= $this->max_students;
    }

    /**
     * الحصول على الفئة العمرية المستهدفة
     */
    public function getTargetAgeGroupAttribute()
    {
        return "{$this->age_group_min} - {$this->age_group_max} سنة";
    }

    /**
     * الحصول على أيام الأسبوع بالعربية
     */
    public function getScheduleDaysInArabicAttribute()
    {
        $days = [
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
            'saturday' => 'السبت',
            'sunday' => 'الأحد',
        ];

        return collect($this->schedule_days ?? [])->map(function($day) use ($days) {
            return $days[$day] ?? $day;
        })->toArray();
    }

    /**
     * الحصول على نوع الحلقة بالعربية
     */
    public function getCircleTypeInArabicAttribute()
    {
        $types = [
            'memorization' => 'تحفيظ',
            'recitation' => 'تلاوة وتجويد',
            'interpretation' => 'تفسير',
            'mixed' => 'مختلط',
        ];

        return $types[$this->circle_type] ?? 'غير محدد';
    }

    /**
     * الحصول على مستوى الحلقة بالعربية
     */
    public function getLevelInArabicAttribute()
    {
        $levels = [
            'beginner' => 'مبتدئ',
            'intermediate' => 'متوسط',
            'advanced' => 'متقدم',
            'expert' => 'متقن',
        ];

        return $levels[$this->level] ?? 'غير محدد';
    }

    /**
     * الحصول على حالة الحلقة بالعربية
     */
    public function getStatusInArabicAttribute()
    {
        $statuses = [
            'active' => 'نشطة',
            'inactive' => 'غير نشطة',
            'completed' => 'مكتملة',
            'suspended' => 'معلقة',
            'cancelled' => 'ملغاة',
        ];

        return $statuses[$this->status] ?? 'غير محدد';
    }

    /**
     * الحصول على متوسط أداء الطلاب في الحلقة
     */
    public function getAverageStudentPerformanceAttribute()
    {
        $averageRating = $this->students()
                             ->wherePivot('status', 'active')
                             ->avg('quran_circle_students.performance_rating');
        
        return $averageRating ? round($averageRating, 1) : 0;
    }

    /**
     * الحصول على متوسط نسبة الحضور
     */
    public function getAverageAttendanceAttribute()
    {
        $averageAttendance = $this->students()
                                 ->wherePivot('status', 'active')
                                 ->avg('quran_circle_students.attendance_percentage');
        
        return $averageAttendance ? round($averageAttendance, 1) : 0;
    }

    /**
     * تسجيل طالب في الحلقة
     */
    public function enrollStudent($studentId, $data = [])
    {
        if ($this->is_full) {
            return false;
        }

        $this->students()->attach($studentId, array_merge([
            'enrollment_date' => now(),
            'status' => 'active',
            'current_level' => 'beginner',
            'total_verses_memorized' => 0,
            'attendance_percentage' => 0,
            'performance_rating' => 0,
        ], $data));

        $this->increment('current_students_count');
        
        return true;
    }

    /**
     * إلغاء تسجيل طالب من الحلقة
     */
    public function unenrollStudent($studentId)
    {
        $this->students()->detach($studentId);
        $this->decrement('current_students_count');
    }

    /**
     * تحديد ما إذا كان يمكن للطالب الانضمام للحلقة
     */
    public function canStudentJoin($studentAge)
    {
        return !$this->is_full && 
               $studentAge >= $this->age_group_min && 
               $studentAge <= $this->age_group_max &&
               $this->status === 'active';
    }

    /**
     * الحصول على الجلسة القادمة
     */
    public function getNextSessionAttribute()
    {
        return $this->sessions()
                   ->where('scheduled_at', '>', now())
                   ->where('status', 'scheduled')
                   ->orderBy('scheduled_at', 'asc')
                   ->first();
    }

    /**
     * الحصول على جلسات هذا الأسبوع
     */
    public function getThisWeekSessionsAttribute()
    {
        return $this->sessions()
                   ->whereBetween('scheduled_at', [
                       now()->startOfWeek(),
                       now()->endOfWeek()
                   ])
                   ->orderBy('scheduled_at', 'asc')
                   ->get();
    }
} 