<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Carbon\Carbon;

class InteractiveCourseSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'session_number',
        'title',
        'description',
        'scheduled_date',
        'scheduled_time',
        'duration_minutes',
        'google_meet_link',
        'status',
        'attendance_count',
        'materials_uploaded',
        'homework_assigned',
        'homework_description',
        'homework_due_date',
        'homework_max_score',
        'allow_late_submissions',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime:H:i',
        'homework_due_date' => 'datetime',
        'duration_minutes' => 'integer',
        'homework_max_score' => 'integer',
        'attendance_count' => 'integer',
        'materials_uploaded' => 'boolean',
        'homework_assigned' => 'boolean',
        'allow_late_submissions' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'scheduled',
        'attendance_count' => 0,
        'materials_uploaded' => false,
        'homework_assigned' => false,
    ];

    /**
     * العلاقة مع الدورة التفاعلية
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourse::class, 'course_id');
    }

    /**
     * حضور الطلاب في هذه الجلسة
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(InteractiveSessionAttendance::class, 'session_id');
    }

    /**
     * الواجبات المنزلية لهذه الجلسة
     */
    public function homework(): HasMany
    {
        return $this->hasMany(InteractiveCourseHomework::class, 'session_id');
    }

    /**
     * Get the unified meeting record for this session
     */
    public function meeting(): MorphOne
    {
        return $this->morphOne(Meeting::class, 'meetable');
    }

    /**
     * الطلاب الحاضرون
     */
    public function presentStudents(): HasMany
    {
        return $this->hasMany(InteractiveSessionAttendance::class, 'session_id')
                    ->where('attendance_status', 'present');
    }

    /**
     * الطلاب الغائبون
     */
    public function absentStudents(): HasMany
    {
        return $this->hasMany(InteractiveSessionAttendance::class, 'session_id')
                    ->where('attendance_status', 'absent');
    }

    /**
     * الطلاب المتأخرون
     */
    public function lateStudents(): HasMany
    {
        return $this->hasMany(InteractiveSessionAttendance::class, 'session_id')
                    ->where('attendance_status', 'late');
    }

    /**
     * نطاق الجلسات المجدولة
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * نطاق الجلسات الجارية
     */
    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
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
     * نطاق الجلسات القادمة
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date', '>=', now()->toDateString())
                    ->where('status', 'scheduled');
    }

    /**
     * نطاق الجلسات الماضية
     */
    public function scopePast($query)
    {
        return $query->where('scheduled_date', '<', now()->toDateString());
    }

    /**
     * نطاق الجلسات لهذا اليوم
     */
    public function scopeToday($query)
    {
        return $query->where('scheduled_date', now()->toDateString());
    }

    /**
     * نطاق الجلسات لهذا الأسبوع
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString()
        ]);
    }

    /**
     * الحصول على حالة الجلسة بالعربية
     */
    public function getStatusInArabicAttribute(): string
    {
        return match($this->status) {
            'scheduled' => 'مجدولة',
            'ongoing' => 'جارية',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            default => 'غير معروفة'
        };
    }

    /**
     * الحصول على وقت الجلسة المكتمل
     */
    public function getScheduledDateTimeAttribute(): Carbon
    {
        return Carbon::parse($this->scheduled_date . ' ' . $this->scheduled_time);
    }

    /**
     * الحصول على وقت انتهاء الجلسة
     */
    public function getEndTimeAttribute(): Carbon
    {
        return $this->scheduled_datetime->addMinutes($this->duration_minutes);
    }

    /**
     * التحقق من أن الجلسة قابلة للبدء
     */
    public function canStart(): bool
    {
        return $this->status === 'scheduled' && 
               $this->scheduled_datetime->isPast() &&
               $this->scheduled_datetime->diffInMinutes(now()) <= 30; // يمكن البدء قبل 30 دقيقة
    }

    /**
     * التحقق من أن الجلسة قابلة للإلغاء
     */
    public function canCancel(): bool
    {
        return in_array($this->status, ['scheduled', 'ongoing']) &&
               $this->scheduled_datetime->isFuture();
    }

    /**
     * بدء الجلسة
     */
    public function start(): bool
    {
        if (!$this->canStart()) {
            return false;
        }

        $this->update(['status' => 'ongoing']);
        return true;
    }

    /**
     * إكمال الجلسة
     */
    public function complete(): bool
    {
        if ($this->status !== 'ongoing') {
            return false;
        }

        $this->update(['status' => 'completed']);
        return true;
    }

    /**
     * إلغاء الجلسة
     */
    public function cancel(): bool
    {
        if (!$this->canCancel()) {
            return false;
        }

        $this->update(['status' => 'cancelled']);
        return true;
    }

    /**
     * تحديث عدد الحضور
     */
    public function updateAttendanceCount(): void
    {
        $this->update([
            'attendance_count' => $this->attendances()->where('attendance_status', 'present')->count()
        ]);
    }

    /**
     * الحصول على نسبة الحضور
     */
    public function getAttendanceRateAttribute(): float
    {
        $totalEnrolled = $this->course->enrollments()->where('enrollment_status', 'enrolled')->count();
        
        if ($totalEnrolled === 0) {
            return 0;
        }

        return round(($this->attendance_count / $totalEnrolled) * 100, 2);
    }

    /**
     * الحصول على متوسط درجة المشاركة
     */
    public function getAverageParticipationScoreAttribute(): float
    {
        $scores = $this->attendances()
                      ->whereNotNull('participation_score')
                      ->pluck('participation_score');

        if ($scores->isEmpty()) {
            return 0;
        }

        return round($scores->avg(), 1);
    }

    /**
     * إنشاء رابط Google Meet
     */
    public function generateGoogleMeetLink(): string
    {
        // في التطبيق الحقيقي، سيتم دمج مع Google Calendar API
        $meetingId = 'meet_' . uniqid();
        $this->update(['google_meet_link' => "https://meet.google.com/{$meetingId}"]);
        
        return $this->google_meet_link;
    }

    /**
     * الحصول على تفاصيل الجلسة
     */
    public function getSessionDetailsAttribute(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'session_number' => $this->session_number,
            'scheduled_date' => $this->scheduled_date->format('Y-m-d'),
            'scheduled_time' => $this->scheduled_time->format('H:i'),
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'status_in_arabic' => $this->status_in_arabic,
            'attendance_count' => $this->attendance_count,
            'attendance_rate' => $this->attendance_rate,
            'average_participation_score' => $this->average_participation_score,
            'materials_uploaded' => $this->materials_uploaded,
            'homework_assigned' => $this->homework_assigned,
            'google_meet_link' => $this->google_meet_link,
            'can_start' => $this->canStart(),
            'can_cancel' => $this->canCancel(),
        ];
    }
}
