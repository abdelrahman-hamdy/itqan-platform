<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Collection;
use App\Services\SessionNamingService;
use App\Contracts\MeetingCapable;
use App\Enums\SessionStatus;
use App\Models\Traits\HasAttendanceTracking;
use App\Models\Traits\HasMeetingData;
use App\Models\Traits\HasMeetings;
use App\Models\Traits\HasSessionFeedback;
use App\Models\Traits\HasSessionScheduling;
use App\Models\Traits\HasSessionStatus;
use App\Models\Traits\ScopedToAcademyForWeb;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
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
 * @property string|null $meeting_platform
 * @property array|null $meeting_data
 * @property string|null $meeting_room_name
 * @property bool $meeting_auto_generated
 * @property Carbon|null $meeting_expires_at
 * @property string|null $attendance_status
 * @property int $participants_count
 * @property string|null $session_notes
 * @property string|null $supervisor_notes
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
    use HasAttendanceTracking {
        HasAttendanceTracking::meetingAttendances as traitMeetingAttendances;
    }
    use HasFactory;
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
    use HasSessionFeedback;
    use HasSessionScheduling {
        HasSessionScheduling::canJoinBasedOnTiming as traitCanJoinBasedOnTiming;
    }
    use HasSessionStatus;
    use ScopedToAcademyForWeb;
    use SoftDeletes;

    /**
     * Auto-populate created_by and updated_by audit fields from authenticated user.
     */
    protected static function booted(): void
    {
        static::creating(function (self $session) {
            if (auth()->check() && ! $session->created_by) {
                $session->created_by = auth()->id();
            }
        });

        static::updating(function (self $session) {
            if (auth()->check()) {
                $session->updated_by = auth()->id();
            }
        });
    }

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
        'supervisor_notes',
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
     * Constructor to initialize fillable from static $baseFillable.
     * Only sets fillable if child hasn't already merged parent fields.
     * Child classes pre-populate $this->fillable via array_merge() before calling parent.
     */
    public function __construct(array $attributes = [])
    {
        // Only initialize fillable from base if child hasn't already merged parent fields.
        // Child classes pre-populate $this->fillable via array_merge() before calling parent.
        if (empty($this->fillable)) {
            $this->fillable = static::$baseFillable;
        }
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
     * Child classes may override with HasOneThrough (e.g., InteractiveCourseSession)
     */
    public function academy(): BelongsTo|HasOneThrough
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Stub relationship for larastan compatibility
     * Meeting data is stored directly on sessions (no separate model)
     * Use getMeetingAttribute() accessor instead
     */
    public function meeting(): HasOne
    {
        // This is a stub relationship for larastan - meeting data is on the session itself
        // Return a HasOne that always returns null (using User as a concrete model placeholder)
        return $this->hasOne(User::class, 'id', 'id')->whereRaw('0 = 1');
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
    public function recordings(): MorphMany
    {
        return $this->morphMany(SessionRecording::class, 'recordable');
    }

    /**
     * Get all attendance records for this session
     * Note: This is a base relationship - child classes may override with specific attendance models
     */
    public function attendanceRecords(): HasMany
    {
        // This method should be overridden in child classes to return the correct attendance model
        // For example: AcademicSession returns AcademicSessionAttendance
        // This base implementation serves as a fallback and documentation
        throw new BadMethodCallException(
            'attendanceRecords() must be implemented in child class: '.static::class
        );
    }

    // ========================================
    // QUERY SCOPES
    // ========================================

    /**
     * Sessions that are scheduled or ongoing (in-progress or about to start).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', SessionStatus::activeStatuses());
    }

    /**
     * Sessions awaiting their scheduled time (scheduled or ready).
     */
    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', SessionStatus::upcomingStatuses());
    }

    /**
     * Non-cancelled sessions (scheduled, ongoing, or completed).
     * Useful for scheduling conflict checks and counting.
     */
    public function scopeNotCancelled($query)
    {
        return $query->whereIn('status', SessionStatus::nonCancelledStatuses());
    }

    /**
     * Sessions that have ended (completed or cancelled).
     */
    public function scopeFinished($query)
    {
        return $query->whereIn('status', SessionStatus::finishedStatuses());
    }

    /**
     * Sessions that counted towards attendance/subscription (completed or absent).
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('status', SessionStatus::resolvedStatuses());
    }

    // ========================================
    // MEETINGCAPABLE INTERFACE IMPLEMENTATION
    // ========================================

    /**
     * Get all participants who should have access to this meeting (MeetingCapable interface)
     * Must be implemented by child classes
     */
    abstract public function getMeetingParticipants(): Collection;

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Get the session type key for naming service
     *
     * Valid return values:
     * - 'quran_individual' - Individual Quran session
     * - 'quran_group' - Group circle session
     * - 'quran_trial' - Trial session
     * - 'academic_private' - Private academic lesson
     * - 'interactive_course' - Interactive course session
     *
     * Must be implemented by each child class
     */
    abstract public function getSessionTypeKey(): string;

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
     * Mark session as absent (student did not attend)
     * Must be implemented by each child class
     */
    abstract public function markAsAbsent(?string $reason = null): bool;

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
        $academy = $this->getAcademyForSettings();

        if ($academy && isset($academy->academic_settings['meeting_settings']['default_preparation_minutes'])) {
            return (int) $academy->academic_settings['meeting_settings']['default_preparation_minutes'];
        }

        return 10;
    }

    /**
     * Get ending buffer minutes after session
     * Can be overridden by child classes
     */
    protected function getEndingBufferMinutes(): int
    {
        $academy = $this->getAcademyForSettings();

        if ($academy && isset($academy->academic_settings['meeting_settings']['default_buffer_minutes'])) {
            return (int) $academy->academic_settings['meeting_settings']['default_buffer_minutes'];
        }

        return 5;
    }

    /**
     * Get grace period minutes for late joins
     * Can be overridden by child classes
     */
    protected function getGracePeriodMinutes(): int
    {
        $academy = $this->getAcademyForSettings();

        if ($academy && $academy->settings) {
            return $academy->settings->default_late_tolerance_minutes ?? 15;
        }

        return 15;
    }

    /**
     * Get academy with eager loading to prevent N+1 queries across helper methods.
     */
    protected function getAcademyForSettings(): ?Academy
    {
        if (! $this->relationLoaded('academy')) {
            $this->load('academy');
        }

        return $this->academy;
    }

    // ========================================
    // DYNAMIC TITLE ACCESSORS
    // ========================================
    // Titles are computed on-the-fly and automatically update when session data changes.
    // This enables reschedule to update titles automatically without manual intervention.

    /**
     * Get the display name for a specific audience.
     *
     * @param  string  $audience  One of: calendar, teacher, student, admin, notification
     */
    public function getDisplayNameForAudience(string $audience): string
    {
        return app(SessionNamingService::class)->getDisplayName($this, $audience);
    }

    /**
     * Get the calendar title (compact, scannable).
     * Example: "أحمد - فردي" or "حلقة الفجر (8)"
     */
    public function getCalendarTitleAttribute(): string
    {
        return $this->getDisplayNameForAudience('calendar');
    }

    /**
     * Get the teacher view title (student-focused).
     * Example: "أحمد محمود - تحفيظ" or "محمد علي - الرياضيات"
     */
    public function getTeacherTitleAttribute(): string
    {
        return $this->getDisplayNameForAudience('teacher');
    }

    /**
     * Get the student/parent view title (teacher-focused).
     * Example: "حلقة القرآن - أ/خالد" or "الرياضيات - أ/عمر"
     */
    public function getStudentTitleAttribute(): string
    {
        return $this->getDisplayNameForAudience('student');
    }

    /**
     * Get the admin view title (full context with code).
     * Example: "QI-2601-0042 | أحمد - خالد"
     */
    public function getAdminTitleAttribute(): string
    {
        return $this->getDisplayNameForAudience('admin');
    }

    /**
     * Get the notification title (action-focused).
     * Example: "جلسة تحفيظ مع أحمد" or "درس الرياضيات مع محمد"
     */
    public function getNotificationTitleAttribute(): string
    {
        return $this->getDisplayNameForAudience('notification');
    }
}
