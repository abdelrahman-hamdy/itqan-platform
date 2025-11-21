<?php

namespace App\Models;

use App\Contracts\MeetingCapable;
use App\Enums\SessionStatus;
use App\Models\AcademySettings;
use App\Traits\HasMeetings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Base Session Model
 *
 * This abstract class contains all common functionality shared across:
 * - QuranSession
 * - AcademicSession
 * - InteractiveCourseSession
 *
 * Purpose: Eliminate code duplication (~800 lines) and provide consistent
 * session behavior across all session types.
 *
 * @property int $id
 * @property int $academy_id
 * @property string $session_code
 * @property SessionStatus $status
 * @property string|null $title
 * @property string|null $description
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property int|null $duration_minutes
 * @property int|null $actual_duration_minutes
 * @property string|null $meeting_link
 * @property string|null $meeting_id
 * @property string|null $meeting_password
 * @property string|null $meeting_platform
 * @property array|null $meeting_data
 * @property string|null $meeting_room_name
 * @property bool $meeting_auto_generated
 * @property Carbon|null $meeting_expires_at
 * @property string|null $attendance_status
 * @property int $participants_count
 * @property string|null $session_notes
 * @property string|null $teacher_feedback
 * @property string|null $cancellation_reason
 * @property int|null $cancelled_by
 * @property Carbon|null $cancelled_at
 * @property string|null $reschedule_reason
 * @property Carbon|null $rescheduled_from
 * @property Carbon|null $rescheduled_to
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $scheduled_by
 */
abstract class BaseSession extends Model implements MeetingCapable
{
    use HasFactory, HasMeetings, SoftDeletes;

    /**
     * Common fillable fields across all session types
     * Child classes should merge their specific fields with parent::$fillable
     */
    protected $fillable = [
        // Core session fields
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

        // Meeting fields
        'meeting_link',
        'meeting_id',
        'meeting_password',
        'meeting_platform',
        'meeting_data',
        'meeting_room_name',
        'meeting_auto_generated',
        'meeting_expires_at',

        // Attendance fields
        'attendance_status',
        'participants_count',

        // Feedback fields
        'session_notes',
        'teacher_feedback',

        // Cancellation fields
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',

        // Rescheduling fields
        'reschedule_reason',
        'rescheduled_from',
        'rescheduled_to',

        // Tracking fields
        'created_by',
        'updated_by',
        'scheduled_by',
    ];

    /**
     * Common casts across all session types
     * Child classes should merge their specific casts with parent::$casts
     */
    protected $casts = [
        'status' => SessionStatus::class,
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
        'meeting_data' => 'array',
        'meeting_auto_generated' => 'boolean',
    ];

    // ========================================
    // RELATIONSHIPS (Common to all sessions)
    // ========================================

    /**
     * Get the academy this session belongs to
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get meeting attendance records for this session
     */
    public function meetingAttendances(): HasMany
    {
        return $this->hasMany(MeetingAttendance::class, 'session_id');
    }

    /**
     * Get the user who cancelled this session
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the user who created this session
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this session
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who scheduled this session
     */
    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    // ========================================
    // SCOPES (Common to all sessions)
    // ========================================

    /**
     * Scope: Get scheduled sessions
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', SessionStatus::SCHEDULED);
    }

    /**
     * Scope: Get completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', SessionStatus::COMPLETED);
    }

    /**
     * Scope: Get cancelled sessions
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', SessionStatus::CANCELLED);
    }

    /**
     * Scope: Get ongoing sessions
     */
    public function scopeOngoing($query)
    {
        return $query->where('status', SessionStatus::ONGOING);
    }

    /**
     * Scope: Get today's sessions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    /**
     * Scope: Get upcoming sessions
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
            ->where('status', SessionStatus::SCHEDULED);
    }

    /**
     * Scope: Get past sessions
     */
    public function scopePast($query)
    {
        return $query->where('scheduled_at', '<', now());
    }

    // ========================================
    // STATUS HELPER METHODS
    // ========================================

    /**
     * Check if session is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === SessionStatus::SCHEDULED;
    }

    /**
     * Check if session is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === SessionStatus::COMPLETED;
    }

    /**
     * Check if session is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === SessionStatus::CANCELLED;
    }

    /**
     * Check if session is ongoing
     */
    public function isOngoing(): bool
    {
        return $this->status === SessionStatus::ONGOING;
    }

    // ========================================
    // MEETING MANAGEMENT METHODS
    // ========================================

    /**
     * Generate meeting link for this session
     */
    public function generateMeetingLink(array $options = []): string
    {
        // If meeting already exists and is valid, return existing link
        if ($this->meeting_room_name && $this->isMeetingValid()) {
            return $this->meeting_link;
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        // Set default options
        $defaultOptions = [
            'recording_enabled' => $this->getDefaultRecordingEnabled(),
            'max_participants' => $this->getDefaultMaxParticipants(),
            'max_duration' => $this->duration_minutes ?? 120,
            'session_type' => $this->getMeetingType(),
        ];

        $mergedOptions = array_merge($defaultOptions, $options);

        // Generate meeting using LiveKit service
        $meetingInfo = $livekitService->createMeeting(
            $this->academy,
            $this->getMeetingType(),
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
     * Get meeting information
     */
    public function getMeetingInfo(): ?array
    {
        if (!$this->meeting_data) {
            return null;
        }

        return $this->meeting_data;
    }

    /**
     * Check if meeting is still valid
     */
    public function isMeetingValid(): bool
    {
        if (!$this->meeting_room_name) {
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
        if (!$this->isMeetingValid()) {
            return null;
        }

        return $this->meeting_link;
    }

    /**
     * Generate participant access token for LiveKit room
     */
    public function generateParticipantToken(User $user, array $permissions = []): string
    {
        if (!$this->meeting_room_name) {
            throw new \Exception('Meeting room not created yet');
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        // Set permissions based on user role
        $defaultPermissions = [
            'can_publish' => true,
            'can_subscribe' => true,
            'can_update_metadata' => $this->canUserManageMeeting($user),
        ];

        $mergedPermissions = array_merge($defaultPermissions, $permissions);

        return $livekitService->generateParticipantToken(
            $this->meeting_room_name,
            $user,
            $mergedPermissions
        );
    }

    /**
     * Get room information from LiveKit server
     */
    public function getRoomInfo(): ?array
    {
        if (!$this->meeting_room_name) {
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
        if (!$this->meeting_room_name) {
            return false;
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        $success = $livekitService->endMeeting($this->meeting_room_name);

        if ($success) {
            $this->update([
                'ended_at' => now(),
                'status' => SessionStatus::COMPLETED,
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

        if (!$roomInfo || !isset($roomInfo['participants'])) {
            return false;
        }

        $userIdentity = $user->id . '_' . Str::slug($user->first_name . '_' . $user->last_name);

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
        $meetingData = $this->meeting_data ?? [];

        return [
            'is_active' => $roomInfo ? ($roomInfo['is_active'] ?? false) : false,
            'participant_count' => $roomInfo ? ($roomInfo['participant_count'] ?? 0) : 0,
            'participants' => $roomInfo ? ($roomInfo['participants'] ?? []) : [],
            'duration_so_far' => $this->started_at ? now()->diffInMinutes($this->started_at) : 0,
            'scheduled_duration' => $this->duration_minutes,
            'room_created_at' => $roomInfo ? ($roomInfo['created_at'] ?? null) : null,
        ];
    }

    /**
     * Get session status display data
     */
    public function getStatusDisplayData(): array
    {
        // Convert string status to enum if needed
        $status = is_string($this->status) ? SessionStatus::from($this->status) : $this->status;

        return [
            'status' => $status->value,
            'label' => $status->label(),
            'icon' => $status->icon(),
            'color' => $status->color(),
            'can_join' => in_array($status, [
                SessionStatus::READY,
                SessionStatus::ONGOING,
            ]),
            'can_complete' => in_array($status, [
                SessionStatus::READY,
                SessionStatus::ONGOING,
            ]),
            'can_cancel' => in_array($status, [
                SessionStatus::SCHEDULED,
                SessionStatus::READY,
            ]),
            'can_reschedule' => in_array($status, [
                SessionStatus::SCHEDULED,
                SessionStatus::READY,
            ]),
            'is_upcoming' => $status === SessionStatus::SCHEDULED && $this->scheduled_at && $this->scheduled_at->isFuture(),
            'is_active' => in_array($status, [SessionStatus::READY, SessionStatus::ONGOING]),
            'preparation_minutes' => $this->getPreparationMinutes(),
            'ending_buffer_minutes' => $this->getEndingBufferMinutes(),
            'grace_period_minutes' => $this->getGracePeriodMinutes(),
        ];
    }

    // ========================================
    // MEETINGCAPABLE INTERFACE IMPLEMENTATION
    // ========================================

    /**
     * Check if a user can join this meeting
     * Can be overridden by child classes for specific logic
     */
    public function canUserJoinMeeting(User $user): bool
    {
        // Check basic permissions first
        if (!$this->canUserManageMeeting($user) && !$this->isUserParticipant($user)) {
            return false;
        }

        // Check timing constraints
        return $this->canJoinBasedOnTiming($user);
    }

    /**
     * Check if user can join based on timing constraints
     */
    protected function canJoinBasedOnTiming(User $user): bool
    {
        // If no scheduled time, allow join (for manual sessions)
        if (!$this->scheduled_at) {
            return true;
        }

        $now = now();
        $sessionStart = $this->scheduled_at;
        $sessionEnd = $sessionStart->copy()->addMinutes($this->duration_minutes ?? 60);

        // Teachers and admins can join anytime within a wider window
        if ($this->canUserManageMeeting($user)) {
            // Allow teachers to join 30 minutes before and up to 2 hours after session end
            $teacherStartWindow = $sessionStart->copy()->subMinutes(30);
            $teacherEndWindow = $sessionEnd->copy()->addHours(2);

            return $now->between($teacherStartWindow, $teacherEndWindow);
        }

        // Students can join 15 minutes before session and up to 30 minutes after session end
        $studentStartWindow = $sessionStart->copy()->subMinutes(15);
        $studentEndWindow = $sessionEnd->copy()->addMinutes(30);

        return $now->between($studentStartWindow, $studentEndWindow);
    }

    /**
     * Get the academy this session belongs to (MeetingCapable interface)
     */
    public function getAcademy(): ?Academy
    {
        return $this->academy;
    }

    /**
     * Get the meeting start time (MeetingCapable interface)
     */
    public function getMeetingStartTime(): ?Carbon
    {
        return $this->scheduled_at;
    }

    /**
     * Get the meeting end time (MeetingCapable interface)
     */
    public function getMeetingEndTime(): ?Carbon
    {
        if ($this->scheduled_at && $this->duration_minutes) {
            return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
        }

        return $this->ended_at;
    }

    /**
     * Get the expected duration of the meeting in minutes (MeetingCapable interface)
     */
    public function getMeetingDurationMinutes(): int
    {
        return $this->duration_minutes ?? 60;
    }

    /**
     * Check if the meeting is currently active (MeetingCapable interface)
     */
    public function isMeetingActive(): bool
    {
        return in_array($this->status, [SessionStatus::READY, SessionStatus::ONGOING]);
    }

    /**
     * Get the meeting session type identifier (MeetingCapable interface)
     * Alias for getMeetingType() for backward compatibility
     */
    public function getMeetingSessionType(): string
    {
        return $this->getMeetingType();
    }

    /**
     * Get all participants who should have access to this meeting (MeetingCapable interface)
     * Must be implemented by child classes
     */
    abstract public function getMeetingParticipants(): \Illuminate\Database\Eloquent\Collection;

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Get the meeting type identifier (e.g., 'quran', 'academic', 'interactive')
     * Must be implemented by each child class
     */
    abstract public function getMeetingType(): string;

    /**
     * Get all participants for this session
     * Must be implemented by each child class
     */
    abstract public function getParticipants(): array;

    /**
     * Get meeting-specific configuration
     * Must be implemented by each child class
     */
    abstract public function getMeetingConfiguration(): array;

    /**
     * Check if a user can manage this meeting (create, end, control participants)
     * Must be implemented by each child class
     */
    abstract public function canUserManageMeeting(User $user): bool;

    /**
     * Check if user is a participant in this session
     * Must be implemented by each child class
     */
    abstract public function isUserParticipant(User $user): bool;

    // ========================================
    // PROTECTED HELPER METHODS (Can be overridden)
    // ========================================

    /**
     * Get default recording enabled setting
     * Can be overridden by child classes
     */
    protected function getDefaultRecordingEnabled(): bool
    {
        // Get from academy settings
        $academySettings = AcademySettings::where('academy_id', $this->academy_id)->first();
        $settingsJson = $academySettings?->settings ?? [];

        return $settingsJson['meeting_recording_enabled'] ?? false;
    }

    /**
     * Get default max participants
     * Can be overridden by child classes
     */
    protected function getDefaultMaxParticipants(): int
    {
        // Get from academy settings
        $academySettings = AcademySettings::where('academy_id', $this->academy_id)->first();
        $settingsJson = $academySettings?->settings ?? [];

        return $settingsJson['meeting_max_participants'] ?? 10;
    }

    /**
     * Get preparation minutes before session
     * Can be overridden by child classes
     */
    protected function getPreparationMinutes(): int
    {
        // Try to get academy settings
        $academy = $this->academy ?? Academy::find($this->academy_id);

        if ($academy && isset($academy->academic_settings['meeting_settings']['default_preparation_minutes'])) {
            return (int) $academy->academic_settings['meeting_settings']['default_preparation_minutes'];
        }

        // Fallback to default
        return 10;
    }

    /**
     * Get ending buffer minutes after session
     * Can be overridden by child classes
     */
    protected function getEndingBufferMinutes(): int
    {
        // Try to get academy settings
        $academy = $this->academy ?? Academy::find($this->academy_id);

        if ($academy && isset($academy->academic_settings['meeting_settings']['default_buffer_minutes'])) {
            return (int) $academy->academic_settings['meeting_settings']['default_buffer_minutes'];
        }

        // Fallback to default
        return 5;
    }

    /**
     * Get grace period minutes for late joins
     * Can be overridden by child classes
     */
    protected function getGracePeriodMinutes(): int
    {
        // Try to get academy settings
        $academy = $this->academy ?? Academy::find($this->academy_id);

        if ($academy && isset($academy->academic_settings['meeting_settings']['default_late_tolerance_minutes'])) {
            return (int) $academy->academic_settings['meeting_settings']['default_late_tolerance_minutes'];
        }

        // Fallback to default
        return 15;
    }
}
