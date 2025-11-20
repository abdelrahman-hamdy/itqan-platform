<?php

namespace App\Models;

use App\Enums\SessionStatus;
use App\Models\Traits\CountsTowardsSubscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * AcademicSession Model
 *
 * Represents an academic tutoring session (private lesson or interactive course session).
 *
 * ARCHITECTURE PATTERNS:
 * - Inherits from BaseSession (polymorphic base class)
 * - Uses CountsTowardsSubscription trait for subscription logic
 * - Constructor merges parent fillable/casts to avoid duplication
 * - Auto-generates unique session codes with transaction locking
 *
 * SESSION TYPES:
 * - 'individual': 1-on-1 tutoring sessions with AcademicIndividualLesson
 * - 'group': Group lessons (not yet fully implemented)
 *
 * KEY RELATIONSHIPS:
 * - academicTeacher: The teacher conducting the session
 * - academicSubscription: The subscription this session belongs to
 * - academicIndividualLesson: Individual lesson details
 * - student: The student attending (for individual sessions)
 *
 * SUBSCRIPTION COUNTING:
 * - Individual sessions count against AcademicSubscription when completed/absent
 * - Uses trait's updateSubscriptionUsage() with transaction locking
 * - Prevents double-counting via subscription_counted flag
 *
 * ACADEMIC FEATURES:
 * - Homework management: homework_description, homework_file
 * - Session topics: session_topics_covered, lesson_content
 * - Learning outcomes tracking
 * - Materials management
 *
 * SESSION CODE FORMAT:
 * - AS-{academyId:02d}-{sequence:06d}
 * - Example: AS-01-000001
 * - Generated automatically via boot() method with row locking
 *
 * @property int $academic_teacher_id
 * @property int|null $academic_subscription_id
 * @property int|null $academic_individual_lesson_id
 * @property int|null $student_id
 * @property string $session_type 'individual' or 'group'
 * @property bool $subscription_counted Flag to prevent double-counting
 * @property string|null $session_topics_covered
 * @property string|null $lesson_content
 *
 * @see BaseSession Parent class with common session fields
 * @see CountsTowardsSubscription Trait for subscription logic
 */
class AcademicSession extends BaseSession
{
    use CountsTowardsSubscription;

    /**
     * Academic-specific fillable fields (merged with parent in constructor)
     * NOTE: Parent BaseSession fields are auto-merged in constructor to avoid duplication
     */
    protected $fillable = [
        // Academic-specific fields
        'academic_teacher_id',
        'academic_subscription_id',
        'academic_individual_lesson_id',
        'student_id',
        'session_type',
        'teacher_scheduled_at',
        'lesson_objectives',
        'location_type',
        'location_details',
        'session_topics_covered',
        'lesson_content',
        'learning_outcomes',
        'homework_description',
        'homework_file',
        'technical_issues',
        'makeup_session_for',
        'is_makeup_session',
        'materials_used',
        'assessment_results',
        'follow_up_required',
        'follow_up_notes',

        // Fields aligned with QuranSession
        'subscription_counted',
        'recording_url',
        'recording_enabled',
    ];

    protected $attributes = [
        'session_type' => 'individual',
        'status' => 'scheduled',
        'duration_minutes' => 60,
        'location_type' => 'online',
        'meeting_auto_generated' => true,
        'attendance_status' => 'scheduled',
        'participants_count' => 0,
        'is_makeup_session' => false,
        'follow_up_required' => false,
        'subscription_counted' => false,
        'recording_enabled' => false,
        'meeting_source' => 'auto',
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
     * Get the casts array - merges parent BaseSession casts with Academic-specific casts
     * This ensures Laravel properly casts attributes like status (enum) and scheduled_at (datetime)
     *
     * NOTE: We don't use protected $casts property because it would override parent's casts.
     * Instead, we merge parent casts with Academic-specific casts at runtime.
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            // Academic-specific casts
            'lesson_objectives' => 'array',
            'teacher_scheduled_at' => 'datetime',
            'learning_outcomes' => 'array',
            'is_makeup_session' => 'boolean',
            'materials_used' => 'array',
            'assessment_results' => 'array',
            'follow_up_required' => 'boolean',

            // Fields aligned with QuranSession
            'subscription_counted' => 'boolean',
            'recording_enabled' => 'boolean',
        ]);
    }

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

    // Common meeting methods (generateMeetingLink, getMeetingInfo, isMeetingValid,
    // getMeetingJoinUrl, generateParticipantToken, getRoomInfo, endMeeting,
    // isUserInMeeting, getMeetingStats) are inherited from BaseSession

    // Override to provide Academic-specific defaults
    protected function getDefaultRecordingEnabled(): bool
    {
        return $this->recording_enabled ?? false;
    }

    protected function getDefaultMaxParticipants(): int
    {
        return 2; // Academic sessions are 1-on-1
    }

    // Common status helper methods (isScheduled, isCompleted, isCancelled,
    // isOngoing) are inherited from BaseSession

    public function isIndividual(): bool
    {
        return $this->session_type === 'individual';
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

        // Add the student (academic sessions are 1-on-1)
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

        return $participants;
    }

    /**
     * Get meeting-specific configuration
     */
    public function getMeetingConfiguration(): array
    {
        // Academic sessions are 1-on-1, so always use 2 participants
        return [
            'session_type' => $this->session_type,
            'session_id' => $this->id,
            'session_code' => $this->session_code,
            'academy_id' => $this->academy_id,
            'duration_minutes' => $this->duration_minutes ?? 60,
            'max_participants' => 2, // Academic sessions are 1-on-1
            'recording_enabled' => $this->recording_enabled ?? false,
            'chat_enabled' => true,
            'screen_sharing_enabled' => true,
            'whiteboard_enabled' => true,
            'breakout_rooms_enabled' => false, // Not needed for 1-on-1
            'waiting_room_enabled' => false,
            'mute_on_join' => false,
            'camera_on_join' => true,
        ];
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

        // Add the student (academic sessions are 1-on-1)
        if ($this->student) {
            $participants->push($this->student);
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

        // Student is a participant if they're enrolled in this session
        if ($this->student_id === $user->id) {
            return true;
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

    /**
     * Initialize student reports when meeting room is created
     * Creates empty report that will be filled with attendance data after session ends
     */
    protected function initializeStudentReports(): void
    {
        if ($this->student_id) {
            \App\Models\AcademicSessionReport::firstOrCreate([
                'session_id' => $this->id,
                'student_id' => $this->student_id,
            ], [
                'teacher_id' => $this->academic_teacher_id,
                'academy_id' => $this->academy_id,
                'attendance_status' => 'absent', // Default to absent until meeting data is available
                'is_calculated' => true,
                'evaluated_at' => now(),
            ]);
        }
    }

    // ========================================
    // STATUS MANAGEMENT METHODS (Aligned with QuranSession)
    // ========================================

    /**
     * Mark session as ongoing
     * Called when teacher starts the session
     */
    public function markAsOngoing(): bool
    {
        if (!in_array($this->status, [SessionStatus::SCHEDULED, SessionStatus::READY])) {
            return false;
        }

        $this->update([
            'status' => SessionStatus::ONGOING,
            'started_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark session as completed
     * Updates subscription usage and attendance records
     */
    public function markAsCompleted(array $additionalData = []): bool
    {
        return \DB::transaction(function () use ($additionalData) {
            // Lock for update to prevent race conditions
            $session = self::lockForUpdate()->find($this->id);

            if (!$session) {
                return false;
            }

            if (!in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY, SessionStatus::SCHEDULED])) {
                return false;
            }

            $updateData = array_merge([
                'status' => SessionStatus::COMPLETED,
                'ended_at' => now(),
                'attendance_status' => 'attended',
            ], $additionalData);

            $session->update($updateData);

            // Update subscription usage (deduct session count)
            $session->updateSubscriptionUsage();

            // Refresh the model
            $this->refresh();

            return true;
        });
    }

    /**
     * Mark session as cancelled
     * Does not count towards subscription
     */
    public function markAsCancelled(?string $reason = null, ?int $cancelledBy = null): bool
    {
        if (!in_array($this->status, [SessionStatus::SCHEDULED, SessionStatus::READY, SessionStatus::ONGOING])) {
            return false;
        }

        $this->update([
            'status' => SessionStatus::CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark session as absent
     * Only for individual sessions where student didn't show up
     * Counts towards subscription usage
     */
    public function markAsAbsent(?string $reason = null): bool
    {
        // Can only mark as absent if:
        // 1. Session is individual (not group)
        // 2. Session is in a completable state
        // 3. Session time has passed
        if ($this->session_type !== 'individual') {
            return false;
        }

        if (!in_array($this->status, [SessionStatus::ONGOING, SessionStatus::READY, SessionStatus::SCHEDULED])) {
            return false;
        }

        if ($this->scheduled_at && $this->scheduled_at->isFuture()) {
            return false;
        }

        $this->update([
            'status' => SessionStatus::ABSENT,
            'ended_at' => now(),
            'attendance_status' => 'absent',
            'attendance_notes' => $reason,
        ]);

        // Absent sessions still count towards subscription
        $this->updateSubscriptionUsage();

        return true;
    }

    // ========================================
    // SUBSCRIPTION COUNTING LOGIC (Aligned with QuranSession)
    // ========================================
    // Note: countsTowardsSubscription() and updateSubscriptionUsage() are now provided by the CountsTowardsSubscription trait

    /**
     * Get the subscription instance for counting (required by CountsTowardsSubscription trait)
     *
     * For Academic sessions:
     * - Individual sessions: subscription comes from academic individual lesson
     * - Group sessions: not yet implemented
     *
     * @return \App\Models\AcademicSubscription|null
     */
    protected function getSubscriptionForCounting()
    {
        // Only individual sessions with academic individual lesson have subscriptions
        if ($this->session_type === 'individual' && $this->academicIndividualLesson) {
            return $this->academicIndividualLesson->subscription;
        }

        // Group sessions subscription logic not yet implemented
        return null;
    }

    /**
     * Check if this is a makeup session
     */
    public function isMakeupSession(): bool
    {
        return $this->is_makeup_session && $this->makeup_session_for !== null;
    }

    /**
     * Get makeup sessions for this session
     */
    public function makeupSessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class, 'makeup_session_for');
    }
}
