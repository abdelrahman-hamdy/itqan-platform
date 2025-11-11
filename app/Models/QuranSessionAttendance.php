<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranSessionAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'student_id',
        'attendance_status',
        'join_time',
        'leave_time',
        'auto_join_time',
        'auto_leave_time',
        'auto_duration_minutes',
        'auto_tracked',
        'manually_overridden',
        'overridden_by',
        'overridden_at',
        'override_reason',
        'meeting_events',
        'connection_quality_score',
        'participation_score',
        'notes',
        'recitation_quality',
        'tajweed_accuracy',
        'verses_reviewed',
        'homework_completion',
        'papers_memorized_today',
        'verses_memorized_today',
        'pages_memorized_today',
        'pages_reviewed_today',
    ];

    protected $casts = [
        'join_time' => 'datetime',
        'leave_time' => 'datetime',
        'auto_join_time' => 'datetime',
        'auto_leave_time' => 'datetime',
        'overridden_at' => 'datetime',
        'auto_tracked' => 'boolean',
        'manually_overridden' => 'boolean',
        'meeting_events' => 'array',
        'participation_score' => 'decimal:1',
        'recitation_quality' => 'decimal:1',
        'tajweed_accuracy' => 'decimal:1',
        'homework_completion' => 'boolean',
        'papers_memorized_today' => 'decimal:2',
        'verses_memorized_today' => 'integer',
        'pages_memorized_today' => 'decimal:2',
        'pages_reviewed_today' => 'decimal:2',
        'connection_quality_score' => 'integer',
        'auto_duration_minutes' => 'integer',
    ];

    protected $attributes = [
        'attendance_status' => 'absent',
    ];

    /**
     * العلاقة مع جلسة القرآن
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    /**
     * العلاقة مع الطالب
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * العلاقة مع المعلم الذي عدل الحضور يدوياً
     */
    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    /**
     * نطاق الطلاب الحاضرين
     */
    public function scopePresent($query)
    {
        return $query->where('attendance_status', 'present');
    }

    /**
     * نطاق الطلاب الغائبين
     */
    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', 'absent');
    }

    /**
     * نطاق الطلاب المتأخرين
     */
    public function scopeLate($query)
    {
        return $query->where('attendance_status', 'late');
    }

    /**
     * نطاق الحضور لهذا اليوم
     */
    public function scopeToday($query)
    {
        return $query->whereHas('session', function ($q) {
            $q->whereDate('scheduled_at', now()->toDateString());
        });
    }

    /**
     * نطاق الحضور لهذا الأسبوع
     */
    public function scopeThisWeek($query)
    {
        return $query->whereHas('session', function ($q) {
            $q->whereBetween('scheduled_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        });
    }

    /**
     * نطاق الحضور لهذا الشهر
     */
    public function scopeThisMonth($query)
    {
        return $query->whereHas('session', function ($q) {
            $q->whereMonth('scheduled_at', now()->month)
                ->whereYear('scheduled_at', now()->year);
        });
    }

    /**
     * نطاق الحضور المتتبع تلقائياً
     */
    public function scopeAutoTracked($query)
    {
        return $query->where('auto_tracked', true);
    }

    /**
     * نطاق الحضور المعدل يدوياً
     */
    public function scopeManuallyOverridden($query)
    {
        return $query->where('manually_overridden', true);
    }

    /**
     * نطاق الحضور غير المعدل يدوياً
     */
    public function scopeNotManuallyOverridden($query)
    {
        return $query->where('manually_overridden', false);
    }

    /**
     * الحصول على حالة الحضور بالعربية
     */
    public function getAttendanceStatusInArabicAttribute(): string
    {
        return match ($this->attendance_status) {
            'present' => 'حاضر',
            'absent' => 'غائب',
            'late' => 'متأخر',
            'partial' => 'حضور جزئي',
            'left_early' => 'غادر مبكراً',
            default => 'غير معروفة'
        };
    }

    /**
     * الحصول على مدة الحضور بالدقائق
     */
    public function getAttendanceDurationMinutesAttribute(): int
    {
        if (! $this->join_time || ! $this->leave_time) {
            return 0;
        }

        return $this->join_time->diffInMinutes($this->leave_time);
    }

    /**
     * التحقق من أن الطالب حضر الجلسة كاملة
     */
    public function getAttendedFullSessionAttribute(): bool
    {
        if ($this->attendance_status !== 'present') {
            return false;
        }

        $sessionDuration = $this->session->duration_minutes;
        $attendedDuration = $this->attendance_duration_minutes;

        // يعتبر حضر الجلسة كاملة إذا حضر 80% على الأقل من مدة الجلسة
        return $attendedDuration >= ($sessionDuration * 0.8);
    }

    /**
     * تسجيل دخول الطالب للجلسة
     */
    public function recordJoin(): bool
    {
        if ($this->attendance_status === 'present' && $this->join_time) {
            return false; // تم تسجيل الدخول مسبقاً
        }

        $now = now();
        $sessionStartTime = $this->session->scheduled_at;
        $lateThreshold = $sessionStartTime->addMinutes(15); // متأخر بعد 15 دقيقة

        $attendanceStatus = $now->isAfter($lateThreshold) ? 'late' : 'present';

        $this->update([
            'attendance_status' => $attendanceStatus,
            'join_time' => $now,
        ]);

        return true;
    }

    /**
     * تسجيل خروج الطالب من الجلسة
     */
    public function recordLeave(): bool
    {
        if (! $this->join_time) {
            return false; // لم يسجل دخول بعد
        }

        $this->update([
            'leave_time' => now(),
        ]);

        return true;
    }

    /**
     * تسجيل درجة المشاركة
     */
    public function recordParticipationScore(float $score): bool
    {
        if ($score < 0 || $score > 10) {
            return false; // درجة غير صحيحة
        }

        $this->update([
            'participation_score' => $score,
        ]);

        return true;
    }

    /**
     * إضافة ملاحظات المعلم
     */
    public function addTeacherNotes(string $notes): bool
    {
        $currentNotes = $this->notes ?: '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $newNotes = "[{$timestamp}] {$notes}\n".$currentNotes;

        $this->update(['notes' => $newNotes]);

        return true;
    }

    /**
     * الحصول على تقييم الأداء
     */
    public function getPerformanceRatingAttribute(): string
    {
        if (! $this->participation_score) {
            return 'غير محدد';
        }

        return match (true) {
            $this->participation_score >= 9 => 'ممتاز',
            $this->participation_score >= 7 => 'جيد جداً',
            $this->participation_score >= 5 => 'جيد',
            $this->participation_score >= 3 => 'مقبول',
            default => 'ضعيف'
        };
    }

    /**
     * الحصول على تفاصيل الحضور
     */
    public function getAttendanceDetailsAttribute(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student_name' => $this->student->full_name ?? 'غير محدد',
            'session_id' => $this->session_id,
            'session_title' => $this->session->title ?? 'غير محدد',
            'attendance_status' => $this->attendance_status,
            'attendance_status_in_arabic' => $this->attendance_status_in_arabic,
            'join_time' => $this->join_time?->format('Y-m-d H:i:s'),
            'leave_time' => $this->leave_time?->format('Y-m-d H:i:s'),
            'attendance_duration_minutes' => $this->attendance_duration_minutes,
            'participation_score' => $this->participation_score,
            'performance_rating' => $this->performance_rating,
            'attended_full_session' => $this->attended_full_session,
            'notes' => $this->notes,
            'recitation_quality' => $this->recitation_quality,
            'tajweed_accuracy' => $this->tajweed_accuracy,
            'homework_completion' => $this->homework_completion,
        ];
    }

    /**
     * التحقق من أن الطالب يمكنه الانضمام للجلسة
     */
    public function canJoinSession(): bool
    {
        // التحقق من أن الجلسة قابلة للانضمام
        return $this->session->status === 'ongoing' &&
               $this->attendance_status === 'absent';
    }

    /**
     * التحقق من أن الطالب يمكنه الخروج من الجلسة
     */
    public function canLeaveSession(): bool
    {
        return $this->attendance_status === 'present' &&
               $this->join_time &&
               ! $this->leave_time;
    }

    /**
     * تسجيل تقييم التلاوة
     */
    public function recordRecitationQuality(float $quality): bool
    {
        if ($quality < 0 || $quality > 10) {
            return false;
        }

        $this->update(['recitation_quality' => $quality]);

        return true;
    }

    /**
     * تسجيل دقة التجويد
     */
    public function recordTajweedAccuracy(float $accuracy): bool
    {
        if ($accuracy < 0 || $accuracy > 10) {
            return false;
        }

        $this->update(['tajweed_accuracy' => $accuracy]);

        return true;
    }

    /**
     * تسجيل إكمال الواجب
     */
    public function recordHomeworkCompletion(bool $completed): bool
    {
        $this->update(['homework_completion' => $completed]);

        return true;
    }

    /**
     * حساب حالة الحضور تلقائياً من أحداث الاجتماع
     */
    public function calculateAttendanceFromMeetingEvents(): string
    {
        if (! $this->meeting_events || empty($this->meeting_events)) {
            return 'absent';
        }

        $joinTime = $this->auto_join_time;
        $leaveTime = $this->auto_leave_time;
        $sessionStart = $this->session->scheduled_at;

        if (! $joinTime) {
            return 'absent';
        }

        // متأخر إذا انضم بعد 10 دقائق من بداية الجلسة
        $minutesLate = $joinTime->diffInMinutes($sessionStart, false);
        if ($minutesLate > 10) {
            return 'late';
        }

        // غادر مبكراً إذا غادر قبل 10 دقائق من النهاية المتوقعة
        $expectedEnd = $sessionStart->copy()->addMinutes($this->session->duration_minutes);
        if ($leaveTime && $leaveTime->isBefore($expectedEnd->subMinutes(10))) {
            return 'left_early';
        }

        return 'present';
    }

    /**
     * تسجيل حدث اجتماع (انضمام/مغادرة)
     */
    public function recordMeetingEvent(string $eventType, array $eventData = []): void
    {
        $events = $this->meeting_events ?? [];
        $events[] = [
            'type' => $eventType, // 'joined', 'left', 'reconnected'
            'timestamp' => now()->toISOString(),
            'data' => $eventData,
        ];

        $this->meeting_events = $events;
        $this->auto_tracked = true;

        // تحديث أوقات الانضمام/المغادرة التلقائية
        if ($eventType === 'joined' && ! $this->auto_join_time) {
            $this->auto_join_time = now();
        }

        if ($eventType === 'left') {
            $this->auto_leave_time = now();
            $this->auto_duration_minutes = $this->auto_join_time
                ? $this->auto_join_time->diffInMinutes(now())
                : 0;
        }

        // حساب حالة الحضور تلقائياً إذا لم يتم التعديل يدوياً
        if (! $this->manually_overridden) {
            $this->attendance_status = $this->calculateAttendanceFromMeetingEvents();
        }

        $this->save();
    }

    /**
     * تعديل الحضور يدوياً
     */
    public function manuallyOverride(array $overrideData, ?string $reason = null, $teacherId = null): self
    {
        $this->update($overrideData);
        $this->manually_overridden = true;
        $this->overridden_by = $teacherId ?? auth()->id();
        $this->overridden_at = now();
        $this->override_reason = $reason;
        $this->save();

        return $this;
    }

    /**
     * إلغاء التعديل اليدوي والعودة للتتبع التلقائي
     */
    public function revertToAutoTracking(): self
    {
        if ($this->auto_tracked) {
            $this->attendance_status = $this->calculateAttendanceFromMeetingEvents();
        }

        $this->manually_overridden = false;
        $this->overridden_by = null;
        $this->overridden_at = null;
        $this->override_reason = null;
        $this->save();

        return $this;
    }

    /**
     * الحصول على مدة الحضور التلقائية بالدقائق
     */
    public function getAutoAttendanceDurationMinutesAttribute(): int
    {
        if (! $this->auto_join_time || ! $this->auto_leave_time) {
            return $this->auto_duration_minutes ?? 0;
        }

        return $this->auto_join_time->diffInMinutes($this->auto_leave_time);
    }

    /**
     * التحقق من أن الحضور تم تتبعه تلقائياً
     */
    public function getIsAutoTrackedAttribute(): bool
    {
        return $this->auto_tracked && ! empty($this->meeting_events);
    }

    /**
     * الحصول على آخر حدث اجتماع
     */
    public function getLastMeetingEventAttribute(): ?array
    {
        if (! $this->meeting_events || empty($this->meeting_events)) {
            return null;
        }

        return end($this->meeting_events);
    }

    /**
     * الحصول على جودة الاتصال
     */
    public function getConnectionQualityAttribute(): string
    {
        if (! $this->connection_quality_score) {
            return 'غير محدد';
        }

        return match (true) {
            $this->connection_quality_score >= 8 => 'ممتاز',
            $this->connection_quality_score >= 6 => 'جيد',
            $this->connection_quality_score >= 4 => 'متوسط',
            default => 'ضعيف'
        };
    }

    /**
     * تسجيل الصفحات المحفوظة والمراجعة اليوم
     */
    public function recordPagesProgress(float $memorizedPages = 0, float $reviewedPages = 0): bool
    {
        $this->update([
            'pages_memorized_today' => $memorizedPages,
            'pages_reviewed_today' => $reviewedPages,
        ]);

        return true;
    }
}
