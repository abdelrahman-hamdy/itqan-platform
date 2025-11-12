<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InteractiveCourseSession extends BaseSession
{

    // Interactive-specific fillable fields
    // Common fields are inherited from BaseSession
    // Note: Uses scheduled_date + scheduled_time instead of scheduled_at
    protected $fillable = [
        'course_id',
        'session_number',
        'scheduled_date',
        'scheduled_time',
        'google_meet_link', // Maps to meeting_link via accessor
        'attendance_count',
        'materials_uploaded',
        'homework_assigned',
        'homework_description',
        'homework_due_date',
        'homework_max_score',
        'allow_late_submissions',
    ];

    // Interactive-specific casts
    // Common casts are inherited from BaseSession
    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime:H:i',
        'homework_due_date' => 'datetime',
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

    // Accessors/Mutators for BaseSession compatibility
    // scheduled_at is computed from scheduled_date + scheduled_time
    public function getScheduledAtAttribute(): ?Carbon
    {
        if ($this->scheduled_date && $this->scheduled_time) {
            return Carbon::parse($this->scheduled_date . ' ' . $this->scheduled_time);
        }
        return null;
    }

    public function setScheduledAtAttribute($value): void
    {
        if ($value) {
            $date = Carbon::parse($value);
            $this->attributes['scheduled_date'] = $date->toDateString();
            $this->attributes['scheduled_time'] = $date->format('H:i');
        }
    }

    // meeting_link maps to google_meet_link
    public function getMeetingLinkAttribute(): ?string
    {
        return $this->attributes['google_meet_link'] ?? null;
    }

    public function setMeetingLinkAttribute($value): void
    {
        $this->attributes['google_meet_link'] = $value;
    }

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
     * Unified homework submission system (polymorphic)
     */
    public function homeworkSubmissions()
    {
        return $this->morphMany(HomeworkSubmission::class, 'submitable');
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

    // Common scopes (scheduled, completed, cancelled, ongoing, today, upcoming, past)
    // are inherited from BaseSession

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

    // ========================================
    // ABSTRACT METHOD IMPLEMENTATIONS (Required by BaseSession)
    // ========================================

    /**
     * Get the meeting type identifier (abstract method implementation)
     */
    public function getMeetingType(): string
    {
        return 'interactive';
    }

    /**
     * Get all participants for this session (abstract method implementation)
     */
    public function getParticipants(): array
    {
        $participants = [];

        // Add the course teacher
        if ($this->course && $this->course->academicTeacher && $this->course->academicTeacher->user) {
            $participants[] = [
                'id' => $this->course->academicTeacher->user->id,
                'name' => trim($this->course->academicTeacher->user->first_name . ' ' . $this->course->academicTeacher->user->last_name),
                'email' => $this->course->academicTeacher->user->email,
                'role' => 'academic_teacher',
                'is_teacher' => true,
                'user' => $this->course->academicTeacher->user,
            ];
        }

        // Add all enrolled students
        if ($this->course) {
            $enrolledStudents = $this->course->enrollments()->with('student')->get();
            foreach ($enrolledStudents as $enrollment) {
                if ($enrollment->student) {
                    $participants[] = [
                        'id' => $enrollment->student->id,
                        'name' => trim($enrollment->student->first_name . ' ' . $enrollment->student->last_name),
                        'email' => $enrollment->student->email,
                        'role' => 'student',
                        'is_teacher' => false,
                        'user' => $enrollment->student,
                    ];
                }
            }
        }

        return $participants;
    }

    /**
     * Get meeting-specific configuration (abstract method implementation)
     */
    public function getMeetingConfiguration(): array
    {
        return [
            'session_type' => 'interactive',
            'session_id' => $this->id,
            'session_number' => $this->session_number,
            'course_id' => $this->course_id,
            'duration_minutes' => $this->duration_minutes ?? 90,
            'max_participants' => 30,
            'recording_enabled' => true,
            'chat_enabled' => true,
            'screen_sharing_enabled' => true,
            'whiteboard_enabled' => true,
            'breakout_rooms_enabled' => true,
            'waiting_room_enabled' => true,
            'mute_on_join' => true,
            'camera_on_join' => true,
        ];
    }

    /**
     * Check if a user can manage the meeting (abstract method implementation)
     */
    public function canUserManageMeeting(User $user): bool
    {
        // Super admin can manage any meeting
        if ($user->user_type === 'super_admin') {
            return true;
        }

        // Academy admin can manage meetings in their academy
        if ($user->user_type === 'academy_admin' && $this->course && $user->academy_id === $this->course->academy_id) {
            return true;
        }

        // Course teacher can manage their sessions
        if ($user->user_type === 'academic_teacher' && $this->course && $this->course->academic_teacher_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is a participant in this session (abstract method implementation)
     */
    public function isUserParticipant(User $user): bool
    {
        // Teacher is a participant
        if ($this->course && $this->course->academic_teacher_id === $user->id) {
            return true;
        }

        // Enrolled students are participants
        if ($this->course && $user->user_type === 'student') {
            return $this->course->enrollments()->where('student_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Get all participants who should have access to this meeting (abstract method implementation)
     */
    public function getMeetingParticipants(): \Illuminate\Database\Eloquent\Collection
    {
        $participants = collect();

        // Add course teacher
        if ($this->course && $this->course->academicTeacher && $this->course->academicTeacher->user) {
            $participants->push($this->course->academicTeacher->user);
        }

        // Add all enrolled students
        if ($this->course) {
            $enrolledStudents = $this->course->enrollments()->with('student')->get()->pluck('student');
            $participants = $participants->merge($enrolledStudents);
        }

        // Remove duplicates and null values
        return $participants->filter()->unique('id');
    }

    // ========================================
    // OVERRIDE BASESESSION TIMING METHODS TO USE ACADEMY SETTINGS
    // ========================================

    /**
     * Get preparation minutes before session from academy settings
     * Overrides BaseSession hardcoded value
     */
    protected function getPreparationMinutes(): int
    {
        if ($this->academy && $this->academy->settings) {
            return $this->academy->settings->default_preparation_minutes ?? 10;
        }

        return 10; // Fallback default
    }

    /**
     * Get ending buffer minutes after session from academy settings
     * Overrides BaseSession hardcoded value
     */
    protected function getEndingBufferMinutes(): int
    {
        if ($this->academy && $this->academy->settings) {
            return $this->academy->settings->default_buffer_minutes ?? 5;
        }

        return 5; // Fallback default
    }

    /**
     * Get grace period minutes for late joins from academy settings
     * Overrides BaseSession hardcoded value
     */
    protected function getGracePeriodMinutes(): int
    {
        if ($this->academy && $this->academy->settings) {
            return $this->academy->settings->default_late_tolerance_minutes ?? 15;
        }

        return 15; // Fallback default
    }
}
