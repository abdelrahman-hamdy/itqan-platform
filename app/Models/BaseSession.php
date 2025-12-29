<?php

namespace App\Models;

use App\Contracts\MeetingCapable;
use App\Enums\SessionStatus;
use App\Models\Traits\HasAttendanceTracking;
use App\Models\Traits\HasMeetingData;
use App\Models\Traits\HasMeetings;
use App\Models\Traits\HasSessionFeedback;
use App\Models\Traits\HasSessionScheduling;
use App\Models\Traits\HasSessionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Base Session Model
 *
 * This abstract class contains all common functionality shared across:
 * - QuranSession
 * - AcademicSession
 * - InteractiveCourseSession
 *
 * Purpose: Eliminate code duplication and provide consistent
 * session behavior across all session types.
 *
 * Refactored: Extracted functionality into focused traits:
 * - HasMeetingData: Meeting link generation, LiveKit integration
 * - HasAttendanceTracking: Attendance records, duration calculations
 * - HasSessionStatus: Status transitions, validation, display
 * - HasSessionScheduling: Scheduling, rescheduling, timing checks
 * - HasSessionFeedback: Teacher feedback, session notes
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
 * @property string|null $cancellation_type
 * @property int|null $cancelled_by
 * @property Carbon|null $cancelled_at
 * @property string|null $reschedule_reason
 * @property Carbon|null $rescheduled_from
 * @property Carbon|null $rescheduled_to
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $scheduled_by
 * @property object|null $meeting Virtual meeting accessor (data stored on session)
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
abstract class BaseSession extends Model implements MeetingCapable
{
    use HasFactory;
    use SoftDeletes;
    use HasMeetingData {
        HasMeetingData::generateMeetingLink as traitGenerateMeetingLink;
        HasMeetingData::generateParticipantToken as traitGenerateParticipantToken;
        HasMeetingData::isMeetingActive as traitIsMeetingActive;
        HasMeetingData::endMeeting as traitEndMeeting;
        HasMeetingData::getMeetingConfiguration insteadof HasMeetings;
    }
    use HasMeetings {
        HasMeetings::generateMeetingLink insteadof HasMeetingData;
        HasMeetings::generateParticipantToken insteadof HasMeetingData;
        HasMeetings::isMeetingActive insteadof HasMeetingData;
        HasMeetings::endMeeting insteadof HasMeetingData;
        HasMeetings::canJoinBasedOnTiming insteadof HasSessionScheduling;
        HasMeetings::meetingAttendances insteadof HasAttendanceTracking;
    }
    use HasAttendanceTracking {
        HasAttendanceTracking::meetingAttendances as traitMeetingAttendances;
    }
    use HasSessionStatus;
    use HasSessionScheduling {
        HasSessionScheduling::canJoinBasedOnTiming as traitCanJoinBasedOnTiming;
    }
    use HasSessionFeedback;

    /**
     * Common fillable fields across all session types
     * Child classes should merge their specific fields with this static property
     * IMPORTANT: Made static to allow child classes to access via parent::$baseFillable
     */
    protected static $baseFillable = [
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

        // Cancellation fields (includes cancellation_type used by CountsTowardsSubscription trait)
        'cancellation_reason',
        'cancellation_type',
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
     * Instance-level fillable (automatically set from $baseFillable)
     * This ensures Laravel's mass assignment protection works correctly
     */
    protected $fillable = [];

    /**
     * Constructor to initialize fillable from static $baseFillable
     */
    public function __construct(array $attributes = [])
    {
        // Initialize fillable from static base fillable
        $this->fillable = static::$baseFillable;
        parent::__construct($attributes);
    }

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
     * Stub relationship for larastan compatibility
     * Meeting data is stored directly on sessions (no separate model)
     * Use getMeetingAttribute() accessor instead
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function meeting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        // This is a stub relationship for larastan - meeting data is on the session itself
        // Return a HasOne that always returns null
        return $this->hasOne(self::class, 'id', 'id')->whereRaw('0 = 1');
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

    /**
     * Get all recordings for this session
     */
    public function recordings(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(SessionRecording::class, 'recordable');
    }

    /**
     * Get all attendance records for this session
     * Note: This is a base relationship - child classes may override with specific attendance models
     */
    public function attendanceRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        // This method should be overridden in child classes to return the correct attendance model
        // For example: AcademicSession returns AcademicSessionAttendance
        // This base implementation serves as a fallback and documentation
        throw new \BadMethodCallException(
            'attendanceRecords() must be implemented in child class: ' . static::class
        );
    }

    // ========================================
    // MEETINGCAPABLE INTERFACE IMPLEMENTATION
    // ========================================

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
        // Try to get academy settings through proper relationship
        $academy = $this->academy ?? Academy::find($this->academy_id);

        if ($academy && $academy->settings) {
            return $academy->settings->default_late_tolerance_minutes ?? 15;
        }

        // Fallback to default
        return 15;
    }
}
