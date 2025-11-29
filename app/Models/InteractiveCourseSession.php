<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InteractiveCourseSession extends BaseSession
{

    /**
     * Interactive-specific fillable fields (merged with parent in constructor)
     * NOTE: Parent BaseSession fields are auto-merged in constructor to avoid duplication
     */
    protected $fillable = [
        // Interactive-specific fields
        'course_id',
        'academy_id',  // Now a real column with FK
        'session_number',
        'scheduled_at',  // Consolidated from scheduled_date + scheduled_time
        'attendance_count',

        // Lesson content
        'lesson_content',

        // Homework
        'homework_assigned',
        'homework_description',
        'homework_file',
    ];

    protected $attributes = [
        'status' => 'scheduled',
        'attendance_count' => 0,
        'homework_assigned' => false,
    ];

    /**
     * Constructor - Merge parent fillable with child-specific fields
     * This approach avoids duplicating 37 BaseSession fields while maintaining consistency
     */
    public function __construct(array $attributes = [])
    {
        // Merge parent fillable fields with child-specific fields BEFORE parent constructor
        $this->fillable = array_merge(parent::$fillable ?? [], $this->fillable);

        parent::__construct($attributes);
    }

    /**
     * Get the casts array - merges parent BaseSession casts with Interactive-specific casts
     * This ensures Laravel properly casts attributes like status (enum) and scheduled_at (datetime)
     *
     * NOTE: We don't use protected $casts property because it would override parent's casts.
     * Instead, we merge parent casts with Interactive-specific casts at runtime.
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            // Interactive-specific casts
            'attendance_count' => 'integer',
            'homework_assigned' => 'boolean',
        ]);
    }

    /**
     * Note: academy() relationship is inherited from BaseSession
     * It uses belongsTo(Academy::class) with the academy_id column
     */

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
     * تقارير الطلاب لهذه الجلسة
     */
    public function studentReports(): HasMany
    {
        return $this->hasMany(InteractiveSessionReport::class, 'session_id');
    }

    /**
     * Alias for studentReports for consistency with other session types
     */
    public function reports(): HasMany
    {
        return $this->studentReports();
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
                    ->where('attendance_status', 'attended');
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
        return $query->whereBetween('scheduled_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
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
     * Alias for scheduled_at for backward compatibility
     */
    public function getScheduledDateTimeAttribute(): ?Carbon
    {
        return $this->scheduled_at;
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

    // ========================================
    // STATUS MANAGEMENT METHODS (Aligned with AcademicSession/QuranSession)
    // ========================================

    /**
     * Mark session as ongoing
     * Called when teacher starts the session
     * Alias: start() for backward compatibility
     */
    public function markAsOngoing(): bool
    {
        if (!in_array($this->status, [\App\Enums\SessionStatus::SCHEDULED, \App\Enums\SessionStatus::READY])) {
            return false;
        }

        $this->update([
            'status' => \App\Enums\SessionStatus::ONGOING,
            'started_at' => now(),
        ]);

        return true;
    }

    /**
     * Backward compatibility alias
     */
    public function start(): bool
    {
        return $this->markAsOngoing();
    }

    /**
     * Mark session as completed
     * Updates attendance counts and session records
     * Alias: complete() for backward compatibility
     */
    public function markAsCompleted(array $additionalData = []): bool
    {
        return \DB::transaction(function () use ($additionalData) {
            // Lock for update to prevent race conditions
            $session = self::lockForUpdate()->find($this->id);

            if (!$session) {
                return false;
            }

            if (!in_array($session->status, [\App\Enums\SessionStatus::ONGOING, \App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::SCHEDULED])) {
                return false;
            }

            $updateData = array_merge([
                'status' => \App\Enums\SessionStatus::COMPLETED,
                'ended_at' => now(),
                'attendance_status' => 'attended',
            ], $additionalData);

            $session->update($updateData);

            // Update attendance count cache
            $session->updateAttendanceCount();

            // Refresh the model
            $this->refresh();

            return true;
        });
    }

    /**
     * Backward compatibility alias
     */
    public function complete(): bool
    {
        return $this->markAsCompleted();
    }

    /**
     * Mark session as cancelled
     * Does not affect course completion
     * Alias: cancel() for backward compatibility
     */
    public function markAsCancelled(?string $reason = null, ?int $cancelledBy = null): bool
    {
        if (!in_array($this->status, [\App\Enums\SessionStatus::SCHEDULED, \App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING])) {
            return false;
        }

        $this->update([
            'status' => \App\Enums\SessionStatus::CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
        ]);

        return true;
    }

    /**
     * Backward compatibility alias
     */
    public function cancel(): bool
    {
        return $this->markAsCancelled();
    }

    /**
     * تحديث عدد الحضور
     */
    public function updateAttendanceCount(): void
    {
        $this->update([
            'attendance_count' => $this->attendances()->where('attendance_status', 'attended')->count()
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
     * الحصول على تفاصيل الجلسة
     */
    public function getSessionDetailsAttribute(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'session_number' => $this->session_number,
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d H:i'),
            'scheduled_date' => $this->scheduled_at?->format('Y-m-d'), // Backward compatibility
            'scheduled_time' => $this->scheduled_at?->format('H:i'), // Backward compatibility
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'status_in_arabic' => $this->status_in_arabic,
            'attendance_count' => $this->attendance_count,
            'attendance_rate' => $this->attendance_rate,
            'average_participation_score' => $this->average_participation_score,
            'homework_assigned' => $this->homework_assigned,
            'meeting_link' => $this->meeting_link, // Now using LiveKit
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
     * Recording setting is controlled at course level
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
            'recording_enabled' => $this->course?->recording_enabled ?? true,
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
        if ($user->user_type === 'academic_teacher' && $this->course && $this->course->assignedTeacher && $this->course->assignedTeacher->user_id === $user->id) {
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
        if ($this->course && $this->course->assignedTeacher && $this->course->assignedTeacher->user_id === $user->id) {
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
