<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcademicSession extends BaseSession
{

    // Academic-specific fillable fields
    // NOTE: Must explicitly include parent fields as Laravel doesn't auto-merge
    protected $fillable = [
        // Core session fields from BaseSession
        'academy_id',
        'session_code',
        'status',
        'title',
        'description',
        'scheduled_at',
        'started_at',
        'ended_at',
        'duration_minutes',
        'actual_duration_minutes',
        'meeting_link',
        'meeting_id',
        'meeting_password',
        'meeting_source',
        'meeting_platform',
        'meeting_data',
        'meeting_room_name',
        'meeting_auto_generated',
        'meeting_expires_at',
        'attendance_status',
        'participants_count',
        'attendance_notes',
        'session_notes',
        'teacher_feedback',
        'student_feedback',
        'parent_feedback',
        'overall_rating',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'reschedule_reason',
        'rescheduled_from',
        'rescheduled_to',
        'created_by',
        'updated_by',
        'scheduled_by',

        // Academic-specific fields
        'academic_teacher_id',
        'academic_subscription_id',
        'academic_individual_lesson_id',
        'interactive_course_session_id',
        'student_id',
        'session_sequence',
        'session_type',
        'is_template',
        'is_generated',
        'is_scheduled',
        'teacher_scheduled_at',
        'lesson_objectives',
        'location_type',
        'location_details',
        'google_event_id',
        'google_calendar_id',
        'google_meet_url',
        'google_meet_id',
        'google_attendees',
        'attendance_log',
        'attendance_marked_at',
        'attendance_marked_by',
        'session_topics_covered',
        'lesson_content',
        'learning_outcomes',
        'homework_description',
        'homework_file',
        'session_grade',
        'technical_issues',
        'makeup_session_for',
        'is_makeup_session',
        'is_auto_generated',
        'cancellation_type',
        'rescheduling_note',
        'materials_used',
        'assessment_results',
        'follow_up_required',
        'follow_up_notes',
        'notification_log',
        'reminder_sent_at',
        'meeting_creation_error',
        'last_error_at',
        'retry_count',
    ];

    // Academic-specific casts
    // NOTE: Must explicitly include parent casts as Laravel doesn't auto-merge
    protected $casts = [
        // Core datetime casts from BaseSession
        'status' => \App\Enums\SessionStatus::class,
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rescheduled_from' => 'datetime',
        'rescheduled_to' => 'datetime',
        'meeting_expires_at' => 'datetime',
        'duration_minutes' => 'integer',
        'actual_duration_minutes' => 'integer',
        'participants_count' => 'integer',
        'overall_rating' => 'integer',
        'meeting_data' => 'array',
        'meeting_auto_generated' => 'boolean',

        // Academic-specific casts
        'lesson_objectives' => 'array',
        'teacher_scheduled_at' => 'datetime',
        'google_attendees' => 'array',
        'attendance_log' => 'array',
        'attendance_marked_at' => 'datetime',
        'learning_outcomes' => 'array',
        'session_grade' => 'decimal:1',
        'is_makeup_session' => 'boolean',
        'is_auto_generated' => 'boolean',
        'is_template' => 'boolean',
        'is_generated' => 'boolean',
        'is_scheduled' => 'boolean',
        'materials_used' => 'array',
        'assessment_results' => 'array',
        'follow_up_required' => 'boolean',
        'notification_log' => 'array',
        'reminder_sent_at' => 'datetime',
        'last_error_at' => 'datetime',
        'retry_count' => 'integer',
        'session_sequence' => 'integer',
    ];

    protected $attributes = [
        'session_type' => 'individual',
        'status' => 'scheduled',
        'is_template' => false,
        'is_generated' => false,
        'is_scheduled' => false,
        'duration_minutes' => 60,
        'location_type' => 'online',
        'meeting_auto_generated' => true,
        'attendance_status' => 'scheduled',
        'participants_count' => 0,
        'is_makeup_session' => false,
        'is_auto_generated' => false,
        'follow_up_required' => false,
        'retry_count' => 0,
        'session_sequence' => 0,
        'meeting_source' => 'auto',
    ];

    /**
     * Boot method to auto-generate session code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->session_code)) {
                $academyId = $model->academy_id ?? 2;
                $model->session_code = static::generateUniqueSessionCode($academyId);
            }
        });
    }

    /**
     * Generate unique session code with proper locking to prevent race conditions
     */
    private static function generateUniqueSessionCode(int $academyId): string
    {
        return \DB::transaction(function () use ($academyId) {
            // Get the maximum sequence number for this academy (including soft deleted)
            $prefix = 'AS-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-';

            $maxNumber = static::withTrashed()
                ->where('academy_id', $academyId)
                ->where('session_code', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->get()
                ->map(function ($session) {
                    // Extract the sequence number from session_code format: AS-{academyId}-{sequence}
                    $parts = explode('-', $session->session_code);
                    return isset($parts[2]) ? (int) $parts[2] : 0;
                })
                ->max();

            $nextNumber = ($maxNumber ?? 0) + 1;
            $sessionCode = $prefix.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Double-check uniqueness (should not be needed with proper locking, but adds safety)
            $attempt = 0;
            while (static::withTrashed()->where('session_code', $sessionCode)->exists() && $attempt < 100) {
                $nextNumber++;
                $sessionCode = $prefix.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
                $attempt++;
            }

            return $sessionCode;
        });
    }

    /**
     * Academic-specific relationships
     * Common relationships (academy, meeting, meetingAttendances, cancelledBy,
     * createdBy, updatedBy, scheduledBy) are inherited from BaseSession
     */
    public function academicTeacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'academic_teacher_id');
    }

    public function academicSubscription(): BelongsTo
    {
        return $this->belongsTo(AcademicSubscription::class);
    }

    /**
     * Alias for academicSubscription relationship for easier access
     */
    public function subscription(): BelongsTo
    {
        return $this->academicSubscription();
    }

    public function academicIndividualLesson(): BelongsTo
    {
        return $this->belongsTo(AcademicIndividualLesson::class);
    }

    public function interactiveCourseSession(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourseSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function sessionReports(): HasMany
    {
        return $this->hasMany(AcademicSessionReport::class, 'session_id');
    }

    public function studentReports(): HasMany
    {
        return $this->hasMany(AcademicSessionReport::class, 'session_id');
    }

    public function attendanceMarkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendance_marked_by');
    }

    public function makeupSessionFor(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'makeup_session_for');
    }

    /**
     * Unified homework submission system (polymorphic)
     */
    public function homeworkSubmissions()
    {
        return $this->morphMany(HomeworkSubmission::class, 'submitable');
    }

    /**
     * Academic-specific scopes
     * Common scopes (scheduled, completed, cancelled, ongoing, today, upcoming, past)
     * are inherited from BaseSession
     */
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('academic_teacher_id', $teacherId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeIndividual($query)
    {
        return $query->where('session_type', 'individual');
    }

    public function scopeInteractiveCourse($query)
    {
        return $query->where('session_type', 'interactive_course');
    }

    // Common meeting methods (generateMeetingLink, getMeetingInfo, isMeetingValid,
    // getMeetingJoinUrl, generateParticipantToken, getRoomInfo, endMeeting,
    // isUserInMeeting, getMeetingStats) are inherited from BaseSession

    // Override to provide Academic-specific defaults
    protected function getDefaultRecordingEnabled(): bool
    {
        return false; // Academic sessions typically don't need recording
    }

    protected function getDefaultMaxParticipants(): int
    {
        return $this->session_type === 'interactive_course' ? 25 : 2;
    }

    // Common status helper methods (isScheduled, isCompleted, isCancelled,
    // isOngoing) are inherited from BaseSession

    public function isIndividual(): bool
    {
        return $this->session_type === 'individual';
    }

    public function isInteractiveCourse(): bool
    {
        return $this->session_type === 'interactive_course';
    }

    public function hasHomework(): bool
    {
        return ! empty($this->homework_description) || ! empty($this->homework_file);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->title.' ('.$this->session_code.')';
    }

    public function getFormattedDurationAttribute(): string
    {
        $hours = intval($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return $hours.'h '.$minutes.'m';
        }

        return $minutes.'m';
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'blue',
            'ongoing' => 'green',
            'completed' => 'gray',
            'cancelled' => 'red',
            'rescheduled' => 'yellow',
            default => 'gray'
        };
    }

    // getStatusDisplayData() is inherited from BaseSession

    // ========================================
    // ABSTRACT METHOD IMPLEMENTATIONS (Required by BaseSession)
    // ========================================

    /**
     * Get the meeting type identifier (abstract method implementation)
     */
    public function getMeetingType(): string
    {
        return 'academic';
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

        // Academy admin can manage any meeting in their academy
        if ($user->user_type === 'academy_admin' && $user->academy_id === $this->academy_id) {
            return true;
        }

        // Academic teacher can manage if they are the teacher for this session
        if ($user->user_type === 'academic_teacher' && $user->id === $this->academic_teacher_id) {
            return true;
        }

        return false;
    }

    /**
     * Get all participants for this session (abstract method implementation)
     */
    public function getParticipants(): array
    {
        $participants = [];

        // Add the academic teacher
        if ($this->academicTeacher && $this->academicTeacher->user) {
            $participants[] = [
                'id' => $this->academicTeacher->user->id,
                'name' => trim($this->academicTeacher->user->first_name.' '.$this->academicTeacher->user->last_name),
                'email' => $this->academicTeacher->user->email,
                'role' => 'academic_teacher',
                'is_teacher' => true,
                'user' => $this->academicTeacher->user,
            ];
        }

        // Add the student
        if ($this->student) {
            $participants[] = [
                'id' => $this->student->id,
                'name' => trim($this->student->first_name.' '.$this->student->last_name),
                'email' => $this->student->email,
                'role' => 'student',
                'is_teacher' => false,
                'user' => $this->student,
            ];
        }

        // For interactive courses, add all enrolled students
        if ($this->session_type === 'interactive_course' && $this->interactiveCourseSession) {
            $course = $this->interactiveCourseSession->interactiveCourse;
            if ($course) {
                $enrolledStudents = $course->enrollments()->with('student')->get();
                foreach ($enrolledStudents as $enrollment) {
                    if ($enrollment->student && $enrollment->student->id !== $this->student_id) {
                        $participants[] = [
                            'id' => $enrollment->student->id,
                            'name' => trim($enrollment->student->first_name.' '.$enrollment->student->last_name),
                            'email' => $enrollment->student->email,
                            'role' => 'student',
                            'is_teacher' => false,
                            'user' => $enrollment->student,
                        ];
                    }
                }
            }
        }

        return $participants;
    }

    /**
     * Get meeting-specific configuration
     */
    public function getMeetingConfiguration(): array
    {
        $config = [
            'session_type' => $this->session_type,
            'session_id' => $this->id,
            'session_code' => $this->session_code,
            'academy_id' => $this->academy_id,
            'duration_minutes' => $this->duration_minutes ?? 60,
            'max_participants' => $this->session_type === 'interactive_course' ? 25 : 2,
            'recording_enabled' => false, // Academic sessions typically don't need recording
            'chat_enabled' => true,
            'screen_sharing_enabled' => true,
            'whiteboard_enabled' => true,
            'breakout_rooms_enabled' => $this->session_type === 'interactive_course',
            'waiting_room_enabled' => false,
            'mute_on_join' => false,
            'camera_on_join' => true,
        ];

        // Add session-specific settings based on type
        if ($this->session_type === 'individual') {
            $config['max_participants'] = 2;
            $config['breakout_rooms_enabled'] = false;
            $config['waiting_room_enabled'] = false;
        } elseif ($this->session_type === 'interactive_course') {
            $config['max_participants'] = 25;
            $config['breakout_rooms_enabled'] = true;
            $config['waiting_room_enabled'] = true;
            $config['mute_on_join'] = true;
        }

        return $config;
    }

    /**
     * Get all participants who should have access to this meeting (abstract method implementation)
     */
    public function getMeetingParticipants(): \Illuminate\Database\Eloquent\Collection
    {
        $participants = collect();

        // Add the academic teacher
        if ($this->academicTeacher && $this->academicTeacher->user) {
            $participants->push($this->academicTeacher->user);
        }

        // Add the student
        if ($this->student) {
            $participants->push($this->student);
        }

        // For interactive courses, add all enrolled students
        if ($this->session_type === 'interactive_course' && $this->interactiveCourseSession) {
            $course = $this->interactiveCourseSession->interactiveCourse;
            if ($course) {
                $enrolledStudents = $course->enrollments()->with('student')->get()->pluck('student');
                $participants = $participants->merge($enrolledStudents);
            }
        }

        // Remove duplicates and null values
        return $participants->filter()->unique('id');
    }

    /**
     * Check if user is a participant in this session (abstract method implementation)
     */
    public function isUserParticipant(User $user): bool
    {
        // Teacher is always a participant in their sessions
        if ($user->user_type === 'academic_teacher' && $this->academic_teacher_id === $user->id) {
            return true;
        }

        // Student is a participant if they're enrolled
        if ($this->student_id === $user->id) {
            return true;
        }

        // For interactive courses, check enrollment
        if ($this->session_type === 'interactive_course' && $this->interactiveCourseSession) {
            $course = $this->interactiveCourseSession->interactiveCourse;
            if ($course && $user->user_type === 'student') {
                return $course->enrollments()->where('student_id', $user->id)->exists();
            }
        }

        return false;
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
