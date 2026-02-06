<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\Traits\CountsTowardsSubscription;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

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
 * - Homework management: homework_description, homework_file, homework_assigned
 * - Lesson documentation: lesson_content
 * - Recording support: recording_url, recording_enabled
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
 * @property string|null $lesson_content
 * @property string|null $homework_description
 * @property string|null $homework_file
 * @property bool $homework_assigned
 * @property string|null $recording_url
 * @property bool $recording_enabled
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MeetingAttendance> $meetingAttendances
 *
 * @method \Illuminate\Database\Eloquent\Relations\HasMany meetingAttendances()
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

        // Content
        'lesson_content',

        // Homework
        'homework_description',
        'homework_file',
        'homework_assigned',

        // Subscription counting
        'subscription_counted',

        // Recording (aligned with QuranSession)
        'recording_url',
        'recording_enabled',
    ];

    protected $attributes = [
        'session_type' => 'individual',
        'status' => SessionStatus::SCHEDULED->value,
        'duration_minutes' => 60,
        'meeting_auto_generated' => true,
        'attendance_status' => AttendanceStatus::ABSENT->value,  // Fixed: 'scheduled' is not a valid attendance status
        'participants_count' => 0,
        'subscription_counted' => false,
        'recording_enabled' => false,
    ];

    /**
     * Constructor - Merge parent fillable with child-specific fields
     * This approach avoids duplicating 37 BaseSession fields while maintaining consistency
     */
    public function __construct(array $attributes = [])
    {
        // Merge parent's static base fillable fields with child-specific fields FIRST
        $this->fillable = array_merge(parent::$baseFillable, $this->fillable);

        // Call grandparent (Model) constructor directly to avoid BaseSession overwriting fillable
        \Illuminate\Database\Eloquent\Model::__construct($attributes);
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
            'subscription_counted' => 'boolean',
            'recording_enabled' => 'boolean',
            'homework_assigned' => 'boolean',
        ]);
    }

    /**
     * Boot method to auto-generate session code and handle homework notifications
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

        // Handle homework assignment notifications
        static::updated(function ($session) {
            // Check if homework_assigned was just set to true
            if ($session->isDirty('homework_assigned') && $session->homework_assigned) {
                $session->notifyHomeworkAssigned();
            }
        });
    }

    /**
     * Send homework assigned notification to student
     * Called when homework is assigned to this session
     *
     * Note: Notification failures are reported but don't prevent homework assignment.
     * The homework assignment is more important than the notification delivery.
     */
    public function notifyHomeworkAssigned(): void
    {
        $student = $this->student;
        if (! $student) {
            return;
        }

        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendHomeworkAssignedNotification(
                $this,
                $student,
                null  // No specific homework ID for Academic homework
            );
        } catch (\Exception $e) {
            // Report to monitoring services (Sentry, Bugsnag, etc.) but don't fail
            report($e);
            \Log::warning('Failed to send homework notification to student', [
                'session_id' => $this->id,
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Notify parent separately to avoid one failure affecting the other
        try {
            if ($student->studentProfile && $student->studentProfile->parent) {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->sendHomeworkAssignedNotification(
                    $this,
                    $student->studentProfile->parent->user,
                    null
                );
            }
        } catch (\Exception $e) {
            // Report to monitoring services but don't fail
            report($e);
            \Log::warning('Failed to send homework notification to parent', [
                'session_id' => $this->id,
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate a unique session code for Academic sessions.
     *
     * Uses SERIALIZABLE isolation with explicit row locking to ensure atomic
     * sequence generation even under high concurrency.
     *
     * Format: AP-{YYMM}-{SEQ} (e.g., AP-2601-0042)
     *
     * @param  int  $academyId  The academy ID (unused in new format, kept for compatibility)
     */
    private static function generateUniqueSessionCode(int $academyId): string
    {
        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return \DB::transaction(function () {
                    // Get prefix from config
                    $prefix = config('session-naming.type_prefixes.academic_private', 'AP');
                    $yearMonth = now()->format('ym');
                    $codePrefix = "{$prefix}-{$yearMonth}-";

                    // Lock the sessions for update to prevent concurrent reads
                    // This ensures only one transaction can generate a code at a time
                    $lastSession = static::withTrashed()
                        ->where('session_code', 'LIKE', $codePrefix.'%')
                        ->lockForUpdate()
                        ->orderByRaw('CAST(SUBSTRING(session_code, -4) AS UNSIGNED) DESC')
                        ->first(['session_code']);

                    $nextSequence = 1;
                    if ($lastSession && preg_match('/(\d{4})$/', $lastSession->session_code, $matches)) {
                        $nextSequence = (int) $matches[1] + 1;
                    }

                    return $codePrefix.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                $attempt++;
                // Retry on deadlock or lock timeout
                if ($attempt >= $maxRetries || ! static::isRetryableException($e)) {
                    throw $e;
                }
                // Exponential backoff
                usleep(100000 * pow(2, $attempt)); // 200ms, 400ms, 800ms...
            }
        }

        // Fallback: Generate a cryptographically secure random code if all retries fail
        $prefix = config('session-naming.type_prefixes.academic_private', 'AP');
        $yearMonth = now()->format('ym');

        return "{$prefix}-{$yearMonth}-".strtoupper(bin2hex(random_bytes(2)));
    }

    /**
     * Check if the exception is retryable (deadlock or lock timeout)
     */
    private static function isRetryableException(\Illuminate\Database\QueryException $e): bool
    {
        $errorCode = $e->errorInfo[1] ?? 0;

        // MySQL error codes: 1205 = Lock wait timeout, 1213 = Deadlock
        return in_array($errorCode, [1205, 1213]);
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

    /**
     * Get all session reports (student evaluations/feedback)
     * Primary relationship name for clarity
     */
    public function sessionReports(): HasMany
    {
        return $this->hasMany(AcademicSessionReport::class, 'session_id');
    }

    /**
     * Alias for sessionReports (legacy/compatibility)
     *
     * @deprecated Use sessionReports() instead
     */
    public function studentReports(): HasMany
    {
        return $this->sessionReports();
    }

    /**
     * Alias for sessionReports (API compatibility)
     */
    public function reports(): HasMany
    {
        return $this->sessionReports();
    }

    /**
     * Get all attendance records for this academic session
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AcademicSessionAttendance::class, 'session_id');
    }

    /**
     * Get the academic homework assignments for this session
     */
    public function homeworkAssignments(): HasMany
    {
        return $this->hasMany(AcademicHomework::class, 'academic_session_id');
    }

    /**
     * Get all homework submissions for this session (through homework assignments)
     * This is a convenience method that fetches submissions via the homework relationship
     */
    public function homeworkSubmissions(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            AcademicHomeworkSubmission::class,
            AcademicHomework::class,
            'academic_session_id', // Foreign key on AcademicHomework
            'academic_homework_id', // Foreign key on AcademicHomeworkSubmission
            'id', // Local key on AcademicSession
            'id' // Local key on AcademicHomework
        );
    }

    /**
     * Alias for academicTeacher (for API compatibility)
     * Returns the teacher profile, not the User
     */
    public function teacher(): BelongsTo
    {
        return $this->academicTeacher();
    }

    /**
     * Get the subject through the subscription or individual lesson
     */
    public function subject(): BelongsTo
    {
        // Subject comes through the academic subscription
        return $this->belongsTo(AcademicSubject::class, 'subject_id')
            ->withDefault(function () {
                // Fallback: try to get from subscription if direct FK doesn't exist
                return $this->academicSubscription?->academicSubject;
            });
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
        // Handle both enum and string values for status
        $status = $this->status instanceof SessionStatus ? $this->status->value : $this->status;

        return match ($status) {
            SessionStatus::SCHEDULED->value => 'blue',
            SessionStatus::ONGOING->value => 'green',
            SessionStatus::COMPLETED->value => 'gray',
            SessionStatus::CANCELLED->value => 'red',
            SessionStatus::ABSENT->value => 'amber',
            default => 'gray'
        };
    }

    // getStatusDisplayData() is inherited from BaseSession

    // ========================================
    // ABSTRACT METHOD IMPLEMENTATIONS (Required by BaseSession)
    // ========================================

    /**
     * Get the session type key for naming service.
     *
     * Returns 'academic_private' for private academic lessons.
     * (Future: could return 'academic_group' for group sessions if implemented)
     */
    public function getSessionTypeKey(): string
    {
        return 'academic_private';
    }

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
        if ($user->user_type === 'admin' && $user->academy_id === $this->academy_id) {
            return true;
        }

        // Academic teacher can manage if they are the teacher for this session
        // Note: academic_teacher_id references AcademicTeacherProfile.id, not User.id
        if ($user->user_type === 'academic_teacher') {
            $profile = $user->academicTeacherProfile;
            if ($profile && $profile->id === $this->academic_teacher_id) {
                return true;
            }
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
        // Note: academic_teacher_id references AcademicTeacherProfile.id, not User.id
        if ($user->user_type === 'academic_teacher') {
            $profile = $user->academicTeacherProfile;
            if ($profile && $profile->id === $this->academic_teacher_id) {
                return true;
            }
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
                'attendance_status' => AttendanceStatus::ABSENT->value, // Default to absent until meeting data is available
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
        // Use enum's canStart() method for consistent status validation
        if (! $this->status->canStart()) {
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

            if (! $session) {
                return false;
            }

            if (! in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY, SessionStatus::SCHEDULED])) {
                return false;
            }

            // Validate that we're not completing before start time
            if ($session->started_at && now()->lt($session->started_at)) {
                return false; // Cannot complete before start
            }

            $updateData = array_merge([
                'status' => SessionStatus::COMPLETED,
                'ended_at' => now(),
                'attendance_status' => AttendanceStatus::ATTENDED->value,
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
    public function markAsCancelled(?string $reason = null, ?User $cancelledBy = null, ?string $cancellationType = null): bool
    {
        $allowedStatuses = [SessionStatus::SCHEDULED, SessionStatus::READY, SessionStatus::ONGOING];

        if (! in_array($this->status, $allowedStatuses)) {
            Log::warning('AcademicSession cancellation blocked - status not allowed', [
                'session_id' => $this->id,
                'session_code' => $this->session_code ?? null,
                'current_status' => $this->status instanceof SessionStatus ? $this->status->value : $this->status,
                'allowed_statuses' => array_map(fn ($s) => $s->value, $allowedStatuses),
            ]);

            return false;
        }

        $this->update([
            'status' => SessionStatus::CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy?->id,
            'cancelled_at' => now(),
            'cancellation_type' => $cancellationType,
        ]);

        Log::info('AcademicSession cancelled successfully', [
            'session_id' => $this->id,
            'session_code' => $this->session_code ?? null,
            'reason' => $reason,
            'cancelled_by' => $cancelledBy?->id,
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

        if (! in_array($this->status, [SessionStatus::ONGOING, SessionStatus::READY, SessionStatus::SCHEDULED])) {
            return false;
        }

        if ($this->scheduled_at && $this->scheduled_at->isFuture()) {
            return false;
        }

        $this->update([
            'status' => SessionStatus::ABSENT,
            'ended_at' => now(),
            'attendance_status' => AttendanceStatus::ABSENT->value,
            'cancellation_reason' => $reason, // Store absence reason in cancellation_reason field
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
}
