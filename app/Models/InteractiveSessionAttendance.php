<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class InteractiveSessionAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'student_id',
        'attendance_status',
        'join_time',
        'leave_time',
        'participation_score',
        'notes',
    ];

    protected $casts = [
        'join_time' => 'datetime',
        'leave_time' => 'datetime',
        'participation_score' => 'decimal:1',
    ];

    protected $attributes = [
        'attendance_status' => 'absent',
    ];

    /**
     * العلاقة مع جلسة الدورة التفاعلية
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourseSession::class, 'session_id');
    }

    /**
     * العلاقة مع الطالب
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
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
            $q->where('scheduled_date', now()->toDateString());
        });
    }

    /**
     * نطاق الحضور لهذا الأسبوع
     */
    public function scopeThisWeek($query)
    {
        return $query->whereHas('session', function ($q) {
            $q->whereBetween('scheduled_date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString()
            ]);
        });
    }

    /**
     * نطاق الحضور لهذا الشهر
     */
    public function scopeThisMonth($query)
    {
        return $query->whereHas('session', function ($q) {
            $q->whereMonth('scheduled_date', now()->month)
              ->whereYear('scheduled_date', now()->year);
        });
    }

    /**
     * الحصول على حالة الحضور بالعربية
     */
    public function getAttendanceStatusInArabicAttribute(): string
    {
        return match($this->attendance_status) {
            'present' => 'حاضر',
            'absent' => 'غائب',
            'late' => 'متأخر',
            default => 'غير معروفة'
        };
    }

    /**
     * الحصول على مدة الحضور بالدقائق
     */
    public function getAttendanceDurationMinutesAttribute(): int
    {
        if (!$this->join_time || !$this->leave_time) {
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
        $sessionStartTime = $this->session->scheduled_datetime;
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
        if (!$this->join_time) {
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
        $newNotes = "[{$timestamp}] {$notes}\n" . $currentNotes;

        $this->update(['notes' => $newNotes]);
        return true;
    }

    /**
     * الحصول على تقييم الأداء
     */
    public function getPerformanceRatingAttribute(): string
    {
        if (!$this->participation_score) {
            return 'غير محدد';
        }

        return match(true) {
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
        ];
    }

    /**
     * التحقق من أن الطالب يمكنه الانضمام للجلسة
     */
    public function canJoinSession(): bool
    {
        // التحقق من أن الطالب مسجل في الدورة
        $isEnrolled = $this->student->interactiveCourseEnrollments()
                                   ->where('course_id', $this->session->course_id)
                                   ->where('enrollment_status', 'enrolled')
                                   ->exists();

        if (!$isEnrolled) {
            return false;
        }

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
               !$this->leave_time;
    }
}
