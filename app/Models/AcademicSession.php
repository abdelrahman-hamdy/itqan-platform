<?php

namespace App\Models;

use App\Contracts\MeetingCapable;
use App\Enums\SessionStatus;
use App\Traits\HasMeetings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AcademicSession extends Model implements MeetingCapable
{
    use HasFactory, HasMeetings, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'academic_teacher_id',
        'academic_subscription_id',
        'academic_individual_lesson_id',
        'interactive_course_session_id',
        'student_id',
        'session_code',
        'session_sequence',
        'session_type',
        'is_template',
        'is_generated',
        'status',
        'is_scheduled',
        'teacher_scheduled_at',
        'title',
        'description',
        'lesson_objectives',
        'scheduled_at',
        'started_at',
        'ended_at',
        'duration_minutes',
        'actual_duration_minutes',
        'location_type',
        'location_details',
        'meeting_link',
        'meeting_id',
        'meeting_password',
        'google_event_id',
        'google_calendar_id',
        'google_meet_url',
        'google_meet_id',
        'google_attendees',
        'meeting_source',
        'meeting_platform',
        'meeting_data',
        'meeting_room_name',
        'meeting_auto_generated',
        'meeting_expires_at',
        'attendance_status',
        'participants_count',
        'attendance_notes',
        'attendance_log',
        'attendance_marked_at',
        'attendance_marked_by',
        'session_topics_covered',
        'lesson_content',
        'learning_outcomes',
        'homework_description',
        'homework_file',
        'session_grade',
        'session_notes',
        'teacher_feedback',
        'student_feedback',
        'parent_feedback',
        'overall_rating',
        'technical_issues',
        'makeup_session_for',
        'is_makeup_session',
        'is_auto_generated',
        'cancellation_reason',
        'cancellation_type',
        'cancelled_by',
        'cancelled_at',
        'reschedule_reason',
        'rescheduled_from',
        'rescheduled_to',
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
        'created_by',
        'updated_by',
        'scheduled_by',
    ];

    protected $casts = [
        'status' => SessionStatus::class,
        'lesson_objectives' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'teacher_scheduled_at' => 'datetime',
        'google_attendees' => 'array',
        'meeting_data' => 'array',
        'meeting_auto_generated' => 'boolean',
        'meeting_expires_at' => 'datetime',
        'attendance_log' => 'array',
        'attendance_marked_at' => 'datetime',
        'learning_outcomes' => 'array',
        'session_grade' => 'decimal:1',
        'is_makeup_session' => 'boolean',
        'is_auto_generated' => 'boolean',
        'is_template' => 'boolean',
        'is_generated' => 'boolean',
        'is_scheduled' => 'boolean',
        'cancelled_at' => 'datetime',
        'rescheduled_from' => 'datetime',
        'rescheduled_to' => 'datetime',
        'materials_used' => 'array',
        'assessment_results' => 'array',
        'follow_up_required' => 'boolean',
        'notification_log' => 'array',
        'reminder_sent_at' => 'datetime',
        'last_error_at' => 'datetime',
        'duration_minutes' => 'integer',
        'actual_duration_minutes' => 'integer',
        'participants_count' => 'integer',
        'overall_rating' => 'integer',
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
                $count = static::where('academy_id', $academyId)->count() + 1;
                $model->session_code = 'AS-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-'.str_pad($count, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicTeacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'academic_teacher_id');
    }

    public function academicSubscription(): BelongsTo
    {
        return $this->belongsTo(AcademicSubscription::class);
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

    public function meetingAttendances(): HasMany
    {
        return $this->hasMany(MeetingAttendance::class, 'session_id');
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

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    /**
     * Scopes
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

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

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now());
    }

    /**
     * Meeting management methods
     */
    public function generateMeetingLink(array $options = []): string
    {
        // If meeting already exists and is valid, return existing link
        if ($this->meeting_room_name && $this->isMeetingValid()) {
            return $this->meeting_link;
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        // Set default options for academic sessions
        $defaultOptions = [
            'recording_enabled' => false, // Academic sessions typically don't need recording
            'max_participants' => $options['max_participants'] ?? 2, // Usually 1-on-1
            'max_duration' => $this->duration_minutes ?? 60,
            'session_type' => 'academic',
        ];

        $mergedOptions = array_merge($defaultOptions, $options);

        // Generate meeting using LiveKit service
        $meetingInfo = $livekitService->createMeeting(
            $this->academy,
            'academic',
            $this->id,
            $this->scheduled_at ?? now(),
            $mergedOptions
        );

        // Update session with meeting info
        $this->update([
            'meeting_link' => $meetingInfo['meeting_url'],
            'meeting_id' => $meetingInfo['meeting_id'],
            'meeting_platform' => $meetingInfo['platform'],
            'meeting_source' => $meetingInfo['platform'],
            'meeting_data' => $meetingInfo,
            'meeting_room_name' => $meetingInfo['room_name'],
            'meeting_auto_generated' => true,
            'meeting_expires_at' => $meetingInfo['expires_at'],
        ]);

        return $meetingInfo['meeting_url'];
    }

    /**
     * Get meeting join information
     */
    public function getMeetingInfo(): ?array
    {
        if (! $this->meeting_data) {
            return null;
        }

        return $this->meeting_data;
    }

    /**
     * Check if meeting is still valid
     */
    public function isMeetingValid(): bool
    {
        if (! $this->meeting_room_name) {
            return false;
        }

        if ($this->meeting_expires_at && $this->meeting_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get meeting join URL for display
     */
    public function getMeetingJoinUrl(): ?string
    {
        if (! $this->isMeetingValid()) {
            return null;
        }

        return $this->meeting_link;
    }

    /**
     * Generate participant access token for LiveKit room
     */
    public function generateParticipantToken(User $user, array $permissions = []): string
    {
        if (! $this->meeting_room_name) {
            throw new \Exception('Meeting room not created yet');
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        // Set permissions based on user role
        $defaultPermissions = [
            'can_publish' => true,
            'can_subscribe' => true,
            'can_update_metadata' => in_array($user->user_type, ['academic_teacher']),
        ];

        $mergedPermissions = array_merge($defaultPermissions, $permissions);

        return $livekitService->generateParticipantToken(
            $this->meeting_room_name,
            $user,
            $mergedPermissions
        );
    }

    /**
     * Get room info from LiveKit server
     */
    public function getRoomInfo(): ?array
    {
        if (! $this->meeting_room_name) {
            return null;
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        return $livekitService->getRoomInfo($this->meeting_room_name);
    }

    /**
     * End the meeting and clean up room
     */
    public function endMeeting(): bool
    {
        if (! $this->meeting_room_name) {
            return false;
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        $success = $livekitService->endMeeting($this->meeting_room_name);

        if ($success) {
            $this->update([
                'ended_at' => now(),
                'status' => 'completed',
            ]);
        }

        return $success;
    }

    /**
     * Check if user is currently in the meeting room
     */
    public function isUserInMeeting(User $user): bool
    {
        $roomInfo = $this->getRoomInfo();

        if (! $roomInfo || ! isset($roomInfo['participants'])) {
            return false;
        }

        $userIdentity = $user->id.'_'.Str::slug($user->first_name.'_'.$user->last_name);

        foreach ($roomInfo['participants'] as $participant) {
            if ($participant['id'] === $userIdentity) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get meeting statistics
     */
    public function getMeetingStats(): array
    {
        $roomInfo = $this->getRoomInfo();

        if (! $roomInfo) {
            return [
                'participant_count' => 0,
                'is_active' => false,
                'duration_minutes' => 0,
            ];
        }

        return [
            'participant_count' => count($roomInfo['participants'] ?? []),
            'is_active' => $roomInfo['is_active'] ?? false,
            'duration_minutes' => $roomInfo['duration_minutes'] ?? 0,
        ];
    }

    /**
     * Helper methods
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }

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

    /**
     * Get session status display data (DRY principle - copied from QuranSession)
     */
    public function getStatusDisplayData(): array
    {
        // Convert string status to enum if needed
        $status = is_string($this->status) ? \App\Enums\SessionStatus::from($this->status) : $this->status;

        return [
            'status' => $status->value,
            'label' => $status->label(),
            'icon' => $status->icon(),
            'color' => $status->color(),
            'can_join' => in_array($status, [
                \App\Enums\SessionStatus::READY,
                \App\Enums\SessionStatus::ONGOING,
            ]),
            'can_complete' => in_array($status, [
                \App\Enums\SessionStatus::READY,
                \App\Enums\SessionStatus::ONGOING,
            ]),
            'can_cancel' => in_array($status, [
                \App\Enums\SessionStatus::SCHEDULED,
                \App\Enums\SessionStatus::READY,
            ]),
            'can_reschedule' => in_array($status, [
                \App\Enums\SessionStatus::SCHEDULED,
                \App\Enums\SessionStatus::READY,
            ]),
            'is_upcoming' => $status === \App\Enums\SessionStatus::SCHEDULED && $this->scheduled_at && $this->scheduled_at->isFuture(),
            'is_active' => in_array($status, [\App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING]),
            'preparation_minutes' => 15, // Default for academic sessions
            'ending_buffer_minutes' => 5, // Default for academic sessions
            'grace_period_minutes' => 15, // Default for academic sessions
        ];
    }

    // ========================================
    // MeetingCapable Interface Implementation
    // ========================================

    /**
     * Check if a user can join the meeting
     */
    public function canUserJoinMeeting(User $user): bool
    {
        // Super admin can join any meeting
        if ($user->user_type === 'super_admin') {
            return true;
        }

        // Academy admin can join any meeting in their academy
        if ($user->user_type === 'academy_admin' && $user->academy_id === $this->academy_id) {
            return true;
        }

        // Academic teacher can join if they are the teacher for this session
        if ($user->user_type === 'academic_teacher' && $user->id === $this->academic_teacher_id) {
            return true;
        }

        // Student can join if they are the student for this session
        if ($user->user_type === 'student' && $user->id === $this->student_id) {
            return true;
        }

        // For interactive courses, check if user is enrolled
        if ($this->session_type === 'interactive_course' && $this->interactiveCourseSession) {
            $course = $this->interactiveCourseSession->interactiveCourse;
            if ($course) {
                // Check if student is enrolled in the course
                if ($user->user_type === 'student') {
                    return $course->enrollments()->where('student_id', $user->id)->exists();
                }
                // Check if user is the course teacher
                if ($user->user_type === 'academic_teacher') {
                    return $course->academic_teacher_id === $user->id;
                }
            }
        }

        return false;
    }

    /**
     * Check if a user can manage the meeting (create, end, etc.)
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
     * Get the meeting type identifier
     */
    public function getMeetingType(): string
    {
        return 'academic';
    }

    /**
     * Get the academy for this session
     */
    public function getAcademy(): Academy
    {
        return $this->academy;
    }

    /**
     * Get the meeting start time
     */
    public function getMeetingStartTime(): ?Carbon
    {
        return $this->scheduled_at;
    }

    /**
     * Get the meeting end time
     */
    public function getMeetingEndTime(): ?Carbon
    {
        if ($this->scheduled_at && $this->duration_minutes) {
            return $this->scheduled_at->addMinutes($this->duration_minutes);
        }

        return $this->ended_at;
    }

    /**
     * Get all participants for this session
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
     * Get the session type identifier for meeting purposes (MeetingCapable interface)
     */
    public function getMeetingSessionType(): string
    {
        return 'academic';
    }

    /**
     * Get the expected duration of the meeting in minutes (MeetingCapable interface)
     */
    public function getMeetingDurationMinutes(): int
    {
        return $this->duration_minutes ?? 60;
    }

    /**
     * Get all participants who should have access to this meeting (MeetingCapable interface)
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
}
