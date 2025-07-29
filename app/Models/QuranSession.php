<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'teacher_id',
        'student_id',
        'subscription_id',
        'session_code',
        'title',
        'description',
        'session_type',
        'status',
        'scheduled_at',
        'started_at',
        'ended_at',
        'actual_duration_minutes',
        'planned_duration_minutes',
        'google_event_id',
        'google_meet_url',
        'session_notes',
        'student_notes',
        'homework_assigned',
        'homework_description',
        'current_surah',
        'current_ayah_from',
        'current_ayah_to',
        'verses_reviewed',
        'verses_memorized_today',
        'verses_corrected',
        'reading_mistakes',
        'tajweed_notes',
        'student_performance_rating',
        'student_attendance_status',
        'next_session_plan',
        'is_makeup_session',
        'original_session_id',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'session_fee',
        'payment_status',
        'recording_url',
        'session_materials',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'actual_duration_minutes' => 'integer',
        'planned_duration_minutes' => 'integer',
        'current_ayah_from' => 'integer',
        'current_ayah_to' => 'integer',
        'verses_reviewed' => 'integer',
        'verses_memorized_today' => 'integer',
        'verses_corrected' => 'integer',
        'student_performance_rating' => 'decimal:1',
        'session_fee' => 'decimal:2',
        'is_makeup_session' => 'boolean',
        'reading_mistakes' => 'array',
        'session_materials' => 'array',
    ];

    protected $attributes = [
        'status' => 'scheduled',
        'session_type' => 'individual',
        'planned_duration_minutes' => 45,
        'student_attendance_status' => 'pending',
        'payment_status' => 'pending',
        'is_makeup_session' => false,
        'verses_reviewed' => 0,
        'verses_memorized_today' => 0,
        'verses_corrected' => 0,
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
     * العلاقة مع الطالب
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * العلاقة مع الاشتراك
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(QuranSubscription::class, 'subscription_id');
    }

    /**
     * الجلسة الأصلية (في حالة كانت هذه جلسة تعويضية)
     */
    public function originalSession(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'original_session_id');
    }

    /**
     * الجلسات التعويضية لهذه الجلسة
     */
    public function makeupSessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'original_session_id');
    }

    /**
     * الشخص الذي ألغى الجلسة
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * نطاق الجلسات المجدولة
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * نطاق الجلسات المكتملة
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * نطاق الجلسات الملغاة
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * نطاق الجلسات الجارية
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * نطاق الجلسات القادمة
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled');
    }

    /**
     * نطاق الجلسات اليوم
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    /**
     * نطاق الجلسات حسب المعلم
     */
    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * نطاق الجلسات حسب الطالب
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * نطاق الجلسات حسب الأكاديمية
     */
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    /**
     * الحصول على مدة الجلسة الفعلية بالدقائق
     */
    public function getActualDurationAttribute()
    {
        if ($this->started_at && $this->ended_at) {
            return $this->ended_at->diffInMinutes($this->started_at);
        }
        return null;
    }

    /**
     * الحصول على حالة الجلسة باللغة العربية
     */
    public function getStatusInArabicAttribute()
    {
        $statuses = [
            'scheduled' => 'مجدولة',
            'in_progress' => 'جارية',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            'no_show' => 'غياب بدون إعذار',
            'postponed' => 'مؤجلة',
        ];

        return $statuses[$this->status] ?? 'غير محدد';
    }

    /**
     * تحديد ما إذا كان يمكن إلغاء الجلسة
     */
    public function canBeCancelled()
    {
        return in_array($this->status, ['scheduled']) && 
               $this->scheduled_at > now()->addHours(2);
    }

    /**
     * تحديد ما إذا كان يمكن بدء الجلسة
     */
    public function canBeStarted()
    {
        return $this->status === 'scheduled' && 
               $this->scheduled_at <= now()->addMinutes(15);
    }

    /**
     * تحديد ما إذا كان يمكن إنهاء الجلسة
     */
    public function canBeEnded()
    {
        return $this->status === 'in_progress';
    }

    /**
     * الحصول على تقييم الأداء باللغة العربية
     */
    public function getPerformanceRatingInArabicAttribute()
    {
        if (!$this->student_performance_rating) {
            return 'غير مقيم';
        }

        $ratings = [
            1 => 'ضعيف',
            2 => 'مقبول',
            3 => 'جيد',
            4 => 'جيد جداً',
            5 => 'ممتاز',
        ];

        return $ratings[round($this->student_performance_rating)] ?? 'غير محدد';
    }

    /**
     * الحصول على إجمالي الآيات المراجعة والمحفوظة
     */
    public function getTotalVersesWorkedAttribute()
    {
        return ($this->verses_reviewed ?? 0) + ($this->verses_memorized_today ?? 0);
    }

    /**
     * الحصول على تفاصيل السورة الحالية
     */
    public function getCurrentSurahDetailsAttribute()
    {
        if (!$this->current_surah) {
            return null;
        }

        return [
            'surah' => $this->current_surah,
            'from_ayah' => $this->current_ayah_from,
            'to_ayah' => $this->current_ayah_to,
            'verses_count' => $this->current_ayah_to - $this->current_ayah_from + 1,
        ];
    }

    /**
     * بدء الجلسة
     */
    public function start()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * إنهاء الجلسة
     */
    public function complete($data = [])
    {
        $this->update(array_merge([
            'status' => 'completed',
            'ended_at' => now(),
            'actual_duration_minutes' => now()->diffInMinutes($this->started_at),
        ], $data));
    }

    /**
     * إلغاء الجلسة
     */
    public function cancel($reason, $cancelledBy)
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
        ]);
    }
} 