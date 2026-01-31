<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\Traits\CountsTowardsSubscription;
use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

/**
 * QuranSession Model
 *
 * Represents a Quran memorization/recitation session (individual or group).
 *
 * ARCHITECTURE PATTERNS:
 * - Inherits from BaseSession (polymorphic base class)
 * - Uses CountsTowardsSubscription trait for subscription logic
 * - Constructor merges parent fillable/casts to avoid duplication
 *
 * SESSION TYPES:
 * - 'individual': 1-on-1 sessions with QuranIndividualCircle
 * - 'circle'/'group': Group sessions with QuranCircle
 *
 * KEY RELATIONSHIPS:
 * - quranTeacher: The teacher conducting the session
 * - subscription: Through individualCircle for individual sessions
 * - circle/individualCircle: The learning circle this session belongs to
 * - student: Direct student relationship for individual sessions
 *
 * SUBSCRIPTION COUNTING:
 * - Individual sessions count against QuranSubscription when completed/absent
 * - Uses trait's updateSubscriptionUsage() with transaction locking
 * - Prevents double-counting via subscription_counted flag
 *
 * QURAN PROGRESS TRACKING:
 * - Quality metrics tracked via sessionHomework relationship
 * - Uses QuranSessionHomework model for memorization, review, comprehensive review
 *
 * @property int $quran_teacher_id
 * @property int|null $quran_subscription_id
 * @property int|null $circle_id
 * @property int|null $individual_circle_id
 * @property int|null $student_id
 * @property string $session_type 'individual', 'group', or 'trial'
 * @property bool $subscription_counted Flag to prevent double-counting
 * @property string|null $lesson_content
 * @property bool|null $homework_assigned
 * @property string|null $homework_details
 * @property string|null $cancellation_type
 * @property string|null $rescheduling_note
 * @property float|null $recitation_quality Legacy quality metric
 * @property float|null $tajweed_accuracy Legacy quality metric
 * @property int|null $mistakes_count Legacy quality metric
 * @property float|null $overall_rating Legacy quality metric
 * @property array|null $lesson_objectives Legacy field
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MeetingAttendance> $meetingAttendances
 *
 * @method \Illuminate\Database\Eloquent\Relations\HasMany meetingAttendances()
 *
 * @see BaseSession Parent class with common session fields
 * @see CountsTowardsSubscription Trait for subscription logic
 */
class QuranSession extends BaseSession
{
    use CountsTowardsSubscription;

    /**
     * Quran-specific fillable fields (merged with parent in constructor)
     * NOTE: Parent BaseSession fields are auto-merged in constructor to avoid duplication
     */
    protected $fillable = [
        // Teacher and subscription (Quran-specific)
        'quran_teacher_id',
        'quran_subscription_id',
        'circle_id',
        'individual_circle_id',
        'student_id',
        'trial_request_id',

        // Session configuration
        'session_type',

        // Lesson content
        'lesson_content',

        // Homework
        'homework_assigned',
        'homework_details',

        // Subscription counting
        'subscription_counted',

        // Monthly tracking
        'monthly_session_number',
        'session_month',

        // Cancellation (merged with BaseSession)
        'cancellation_type',
        'rescheduling_note',
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
     * Get the casts array - merges parent BaseSession casts with Quran-specific casts
     * This ensures Laravel properly casts attributes like status (enum) and scheduled_at (datetime)
     *
     * NOTE: We don't use protected $casts property because it would override parent's casts.
     * Instead, we merge parent casts with Quran-specific casts at runtime.
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            // Homework - stored as simple text description (not array/boolean)
            // Note: Column is JSON type but used for plain text storage
            // No cast needed - Laravel handles JSON columns with string values

            // Subscription counting
            'subscription_counted' => 'boolean',
        ]);
    }

    // Quran-specific relationships
    // Common relationships (academy, meeting, meetingAttendances, cancelledBy,
    // createdBy, updatedBy, scheduledBy) are inherited from BaseSession

    public function quranTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quran_teacher_id');
    }

    /**
     * Get the Quran teacher profile for this session
     * Uses user_id as the foreign key match since quran_teacher_id stores user IDs
     */
    public function quranTeacherProfile(): BelongsTo
    {
        return $this->belongsTo(QuranTeacherProfile::class, 'quran_teacher_id', 'user_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(QuranSubscription::class, 'quran_subscription_id');
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class);
    }

    public function individualCircle(): BelongsTo
    {
        return $this->belongsTo(QuranIndividualCircle::class, 'individual_circle_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function trialRequest(): BelongsTo
    {
        return $this->belongsTo(QuranTrialRequest::class, 'trial_request_id');
    }

    public function makeupFor(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'makeup_session_for');
    }

    public function makeupSessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'makeup_session_for');
    }

    // Note: progress() relationship removed - Progress is now calculated
    // dynamically from session reports using the QuranReportService

    public function attendances(): HasMany
    {
        return $this->hasMany(QuranSessionAttendance::class, 'session_id');
    }

    /**
     * Get all attendance records for this Quran session
     * Overrides BaseSession abstract method to provide Quran-specific implementation
     * Alias for attendances() for consistency with BaseSession contract
     */
    public function attendanceRecords(): HasMany
    {
        return $this->attendances();
    }

    /**
     * العلاقة مع تقارير الطلاب الجديدة
     */
    public function studentReports(): HasMany
    {
        return $this->hasMany(StudentSessionReport::class, 'session_id');
    }

    /**
     * Alias for studentReports relationship (for API compatibility)
     */
    public function reports(): HasMany
    {
        return $this->studentReports();
    }

    /**
     * Get first student report (for individual sessions)
     */
    public function studentReport(): HasOne
    {
        return $this->hasOne(StudentSessionReport::class, 'session_id');
    }

    /**
     * Alias for quranTeacher (for API compatibility)
     */
    public function teacher(): BelongsTo
    {
        return $this->quranTeacher();
    }

    /**
     * New homework system relationship
     * Homework is assigned at session level and graded orally
     * via student_session_reports (new_memorization_degree, reservation_degree)
     */
    public function sessionHomework(): HasOne
    {
        return $this->hasOne(QuranSessionHomework::class, 'session_id');
    }

    public function autoTrackedAttendances(): HasMany
    {
        return $this->hasMany(QuranSessionAttendance::class, 'session_id')->where('auto_tracked', true);
    }

    // Quran-specific scopes
    // Common scopes (scheduled, completed, cancelled, ongoing, today, upcoming, past)
    // are inherited from BaseSession

    public function scopeMissed($query)
    {
        return $query->where('status', SessionStatus::ABSENT);
    }

    public function scopeThisWeek($query)
    {
        $now = AcademyContextService::nowInAcademyTimezone();

        return $query->whereBetween('scheduled_at', [
            $now->copy()->startOfWeek(),
            $now->copy()->endOfWeek(),
        ]);
    }

    public function scopeBySessionType($query, $type)
    {
        return $query->where('session_type', $type);
    }

    public function scopeIndividual($query)
    {
        return $query->where('session_type', 'individual');
    }

    /**
     * Scope for group sessions (circles)
     * Note: Standardized to 'group' value - 'circle' is deprecated
     */
    public function scopeCircle($query)
    {
        return $query->where('session_type', 'group');
    }

    /**
     * Alias for scopeCircle - preferred method name
     */
    public function scopeGroup($query)
    {
        return $query->where('session_type', 'group');
    }

    /**
     * Scope for trial sessions
     * Trial sessions are one-time 30-minute sessions for students to try before subscribing
     */
    public function scopeTrial($query)
    {
        return $query->where('session_type', 'trial');
    }

    /**
     * Alternative scope using trial_request_id relationship
     * Useful for finding all sessions linked to trial requests
     */
    public function scopeTrialSessions($query)
    {
        return $query->whereNotNull('trial_request_id');
    }

    public function scopeMakeupSessions($query)
    {
        return $query->where('is_makeup_session', true);
    }

    public function scopeRegularSessions($query)
    {
        return $query->where('is_makeup_session', false);
    }

    // Status Management Methods

    /**
     * Get the status enum instance
     */
    public function getStatusEnum(): SessionStatus
    {
        $statusValue = $this->status instanceof SessionStatus ? $this->status->value : $this->status;

        return SessionStatus::from($statusValue);
    }

    /**
     * Check if session is upcoming (scheduled in future)
     */
    public function isUpcoming(): bool
    {
        return $this->status === SessionStatus::SCHEDULED &&
               $this->scheduled_at &&
               $this->scheduled_at->isFuture();
    }

    /**
     * Check if session is ready to start (within 30 minutes)
     * Uses academy timezone for accurate comparison
     */
    public function isReadyToStart(): bool
    {
        if (! $this->scheduled_at || $this->status !== SessionStatus::SCHEDULED) {
            return false;
        }

        $now = AcademyContextService::nowInAcademyTimezone();
        $minutesUntilSession = $now->diffInMinutes($this->scheduled_at, false);

        return $minutesUntilSession <= 30 && $minutesUntilSession >= -10; // Can start 30 min before, 10 min after
    }

    /**
     * Mark session as ongoing
     */
    public function markAsOngoing(): bool
    {
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
     */
    public function markAsCompleted(array $additionalData = []): bool
    {
        return \DB::transaction(function () use ($additionalData) {
            // Lock the session row for update
            $session = self::lockForUpdate()->find($this->id);

            if (! $session) {
                throw new \Exception("Session {$this->id} not found");
            }

            if (! $session->status->canComplete()) {
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

            // Update circle progress if applicable
            if ($session->individualCircle) {
                $session->individualCircle->updateProgress();
            }

            // Record attendance for students
            $session->recordSessionAttendance(AttendanceStatus::ATTENDED->value);

            // Update subscription usage (this also uses transactions internally)
            $session->updateSubscriptionUsage();

            // Refresh the current instance
            $this->refresh();

            return true;
        });
    }

    /**
     * Mark session as cancelled
     */
    public function markAsCancelled(?string $reason = null, ?User $cancelledBy = null, ?string $cancellationType = null): bool
    {
        // Ensure status is properly cast
        $status = $this->status;
        if (is_string($status)) {
            $status = SessionStatus::tryFrom($status);
        }

        if (! $status || ! $status->canCancel()) {
            Log::warning('QuranSession cancellation blocked - status cannot be cancelled', [
                'session_id' => $this->id,
                'session_code' => $this->session_code ?? null,
                'raw_status' => $this->getRawOriginal('status'),
                'cast_status' => $status?->value ?? 'null',
                'can_cancel' => $status?->canCancel() ?? false,
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

        // Record attendance as absent for cancelled sessions (doesn't count towards subscription)
        $this->recordSessionAttendance(AttendanceStatus::ABSENT->value);

        Log::info('QuranSession cancelled successfully', [
            'session_id' => $this->id,
            'session_code' => $this->session_code ?? null,
            'reason' => $reason,
            'cancelled_by' => $cancelledBy?->id,
        ]);

        return true;
    }

    /**
     * Mark session as absent (individual circles only)
     */
    public function markAsAbsent(?string $reason = null): bool
    {
        // Prevent marking future sessions as absent
        if ($this->session_type !== 'individual' ||
            ! $this->status->canComplete() ||
            ($this->scheduled_at && $this->scheduled_at->isFuture())) {
            return false;
        }

        $this->update([
            'status' => SessionStatus::ABSENT,
            'ended_at' => now(),
            'attendance_status' => AttendanceStatus::ABSENT->value,
            'attendance_notes' => $reason,
        ]);

        // Record attendance as absent (counts towards subscription)
        $this->recordSessionAttendance(AttendanceStatus::ABSENT->value);

        // Update circle progress
        if ($this->individualCircle) {
            $this->individualCircle->updateProgress();
        }

        // Update subscription usage (absent sessions count towards subscription)
        $this->updateSubscriptionUsage();

        return true;
    }

    /**
     * Initialize student reports for session
     */
    protected function initializeStudentReports(): void
    {
        if ($this->session_type === 'individual' && $this->student) {
            // For individual sessions
            StudentSessionReport::firstOrCreate([
                'session_id' => $this->id,
                'student_id' => $this->student_id,
                'teacher_id' => $this->quran_teacher_id,
                'academy_id' => $this->academy_id,
            ], [
                'attendance_status' => AttendanceStatus::ABSENT->value, // Default to absent until meeting data is available
                'is_calculated' => true,
                'evaluated_at' => now(),
            ]);
        } elseif ($this->session_type === 'group' && $this->circle) {
            // For group sessions - create reports for all enrolled students
            $students = $this->circle->students;
            foreach ($students as $student) {
                StudentSessionReport::firstOrCreate([
                    'session_id' => $this->id,
                    'student_id' => $student->id,
                    'teacher_id' => $this->quran_teacher_id,
                    'academy_id' => $this->academy_id,
                ], [
                    'attendance_status' => AttendanceStatus::ABSENT->value, // Default to absent until meeting data is available
                    'is_calculated' => true,
                    'evaluated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Record session attendance for all students in this session
     *
     * This method updates attendance records when a session status changes.
     * For individual sessions: updates the single student's attendance
     * For group sessions: updates attendance for all enrolled students
     *
     * @param  string  $status  The attendance status ('attended', 'absent', 'cancelled', 'late', 'left')
     */
    protected function recordSessionAttendance(string $status): void
    {
        try {
            // Get students based on session type
            $students = $this->getStudentsForSession();

            if ($students->isEmpty()) {
                Log::warning('No students found for session attendance recording', [
                    'session_id' => $this->id,
                    'session_type' => $this->session_type,
                ]);

                return;
            }

            foreach ($students as $student) {
                // Update or create attendance record
                QuranSessionAttendance::updateOrCreate(
                    [
                        'session_id' => $this->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'academy_id' => $this->academy_id,
                        'attendance_status' => $status,
                        'recorded_at' => now(),
                        'auto_tracked' => false,
                        'manually_overridden' => true,
                    ]
                );

                // Also update student session report if exists
                StudentSessionReport::where('session_id', $this->id)
                    ->where('student_id', $student->id)
                    ->update(['attendance_status' => $status]);
            }

            Log::info('Session attendance recorded', [
                'session_id' => $this->id,
                'status' => $status,
                'students_count' => $students->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record session attendance', [
                'session_id' => $this->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the subscription instance for counting (required by CountsTowardsSubscription trait)
     *
     * For Quran sessions:
     * - Individual sessions: subscription comes from individual circle (via activeSubscription accessor)
     * - Group sessions: subscription comes from student's enrollment in the circle
     *
     * DECOUPLED ARCHITECTURE:
     * - Uses the new polymorphic relationship first, falls back to legacy
     * - For individual circles: checks both linkedSubscriptions() and subscription_id
     * - For group circles: checks the student's enrollment subscription_id
     *
     * @return \App\Models\QuranSubscription|null
     */
    protected function getSubscriptionForCounting()
    {
        // Individual sessions: get subscription from the individual circle
        if ($this->session_type === 'individual' && $this->individualCircle) {
            // Use the new activeSubscription accessor which handles both polymorphic and legacy
            return $this->individualCircle->activeSubscription;
        }

        // Group sessions: get subscription from the student's enrollment in the circle
        if ($this->session_type === 'group' && $this->circle_id && $this->student_id) {
            // Find the student's enrollment in this circle
            $enrollment = QuranCircleEnrollment::where('circle_id', $this->circle_id)
                ->where('student_id', $this->student_id)
                ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)
                ->first();

            if ($enrollment) {
                // Return the active subscription linked to this enrollment
                return $enrollment->activeSubscription;
            }
        }

        // Fallback: check if there's a direct subscription relationship
        if ($this->quran_subscription_id) {
            return $this->subscription;
        }

        return null;
    }

    // getStatusDisplayData() is inherited from BaseSession
    // Override protected helper methods to use academy settings instead of circle settings

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

    public function scopeAttended($query)
    {
        return $query->where('attendance_status', AttendanceStatus::ATTENDED->value);
    }

    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', AttendanceStatus::ABSENT->value);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('quran_teacher_id', $teacherId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where(function ($subQuery) use ($studentId) {
            // Individual sessions: direct student_id match
            $subQuery->where('student_id', $studentId)
                // OR group sessions: student enrolled in the circle (use correct session_type)
                ->orWhere(function ($groupQuery) use ($studentId) {
                    $groupQuery->where('session_type', 'group')
                        ->whereHas('circle.students', function ($circleQuery) use ($studentId) {
                            $circleQuery->where('student_id', $studentId);
                        });
                });
        });
    }

    public function scopeHighRated($query, $minRating = 4)
    {
        return $query->where('overall_rating', '>=', $minRating);
    }

    // Accessors
    public function getSessionTypeTextAttribute(): string
    {
        $types = [
            'individual' => 'جلسة فردية',
            'group' => 'حلقة جماعية',
            'trial' => 'جلسة تجريبية',
        ];

        return $types[$this->session_type] ?? $this->session_type;
    }

    public function getStatusTextAttribute(): string
    {
        return $this->status->label();
    }

    public function getAttendanceStatusTextAttribute(): string
    {
        $statuses = [
            'attended' => 'حاضر',
            'absent' => 'غائب',
            'late' => 'متأخر',
            'left' => 'غادر مبكراً',
        ];

        return $statuses[$this->attendance_status] ?? $this->attendance_status;
    }

    public function getFormattedScheduledTimeAttribute(): string
    {
        if (! $this->scheduled_at) {
            return 'غير محدد';
        }
        // Convert to academy timezone for display
        $timezone = AcademyContextService::getTimezone();

        return $this->scheduled_at->copy()->setTimezone($timezone)->format('Y-m-d h:i A');
    }

    public function getFormattedDateAttribute(): string
    {
        if (! $this->scheduled_at) {
            return 'غير محدد';
        }
        // Convert to academy timezone for display
        $timezone = AcademyContextService::getTimezone();

        return $this->scheduled_at->copy()->setTimezone($timezone)->format('Y-m-d');
    }

    public function getFormattedTimeAttribute(): string
    {
        if (! $this->scheduled_at) {
            return 'غير محدد';
        }
        // Convert to academy timezone for display
        $timezone = AcademyContextService::getTimezone();

        return $this->scheduled_at->copy()->setTimezone($timezone)->format('h:i A');
    }

    public function getDurationTextAttribute(): string
    {
        $duration = $this->actual_duration_minutes ?? $this->duration_minutes;

        if ($duration < 60) {
            return $duration.' دقيقة';
        }

        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        if ($minutes === 0) {
            return $hours.' ساعة';
        }

        return $hours.' ساعة و '.$minutes.' دقيقة';
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    public function getIsTodayAttribute(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isToday();
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === SessionStatus::COMPLETED;
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === SessionStatus::CANCELLED;
    }

    /**
     * Check if session can be started (within 15 minutes of scheduled time)
     * Uses academy timezone for accurate comparison
     */
    public function getCanStartAttribute(): bool
    {
        if ($this->status !== SessionStatus::SCHEDULED || ! $this->scheduled_at) {
            return false;
        }

        $now = AcademyContextService::nowInAcademyTimezone();
        $minutesUntilSession = $now->diffInMinutes($this->scheduled_at, false);

        // Can start if session is within 15 minutes (past or future)
        return abs($minutesUntilSession) <= 15;
    }

    /**
     * Check if session can be cancelled (at least 2 hours before)
     * Uses academy timezone for accurate comparison
     */
    public function getCanCancelAttribute(): bool
    {
        if (! in_array($this->status, [SessionStatus::SCHEDULED, SessionStatus::ONGOING]) || ! $this->scheduled_at) {
            return false;
        }

        $now = AcademyContextService::nowInAcademyTimezone();

        // Session must be in the future and at least 2 hours away
        return $this->scheduled_at->gt($now) && $now->diffInHours($this->scheduled_at, false) >= 2;
    }

    /**
     * Check if session can be rescheduled (at least 24 hours before)
     * Uses academy timezone for accurate comparison
     */
    public function getCanRescheduleAttribute(): bool
    {
        if ($this->status !== SessionStatus::SCHEDULED || ! $this->scheduled_at) {
            return false;
        }

        $now = AcademyContextService::nowInAcademyTimezone();

        // Session must be in the future and at least 24 hours away
        return $this->scheduled_at->gt($now) && $now->diffInHours($this->scheduled_at, false) >= 24;
    }

    public function getProgressSummaryAttribute(): string
    {
        // Use sessionHomework for progress summary
        $homework = $this->sessionHomework;

        if (! $homework) {
            return 'لم يتم تحديد التقدم';
        }

        $parts = [];

        if ($homework->has_new_memorization && $homework->new_memorization_surah) {
            $surahName = $this->getSurahName($homework->new_memorization_surah);
            $pages = $homework->new_memorization_pages ? " ({$homework->new_memorization_pages} وجه)" : '';
            $parts[] = "حفظ: سورة {$surahName}{$pages}";
        }

        if ($homework->has_review && $homework->review_surah) {
            $surahName = $this->getSurahName($homework->review_surah);
            $pages = $homework->review_pages ? " ({$homework->review_pages} وجه)" : '';
            $parts[] = "مراجعة: سورة {$surahName}{$pages}";
        }

        if ($homework->has_comprehensive_review && $homework->comprehensive_review_surahs) {
            $parts[] = 'مراجعة شاملة';
        }

        return ! empty($parts) ? implode(' | ', $parts) : 'لم يتم تحديد التقدم';
    }

    public function getPerformanceSummaryAttribute(): array
    {
        return [
            'recitation_quality' => $this->recitation_quality,
            'tajweed_accuracy' => $this->tajweed_accuracy,
            'mistakes_count' => $this->mistakes_count,
            'overall_rating' => $this->overall_rating,
        ];
    }

    public function getTimeDurationAttribute(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        $endTime = $this->ended_at ?? now();

        return $this->started_at->diffInMinutes($endTime);
    }

    // Methods
    public function start(): self
    {
        if ($this->status !== SessionStatus::SCHEDULED && $this->status !== SessionStatus::READY) {
            throw new \Exception('لا يمكن بدء الجلسة. الحالة الحالية: '.$this->status_text);
        }

        $this->update([
            'status' => SessionStatus::ONGOING,
            'started_at' => now(),
        ]);

        return $this;
    }

    public function complete(array $sessionData = []): self
    {
        if (! in_array($this->status, [SessionStatus::ONGOING, SessionStatus::SCHEDULED, SessionStatus::READY])) {
            throw new \Exception('لا يمكن إنهاء الجلسة. الحالة الحالية: '.$this->status_text);
        }

        $endTime = now();
        $actualDuration = $this->started_at ? $this->started_at->diffInMinutes($endTime) : $this->duration_minutes;

        $updateData = array_merge([
            'status' => SessionStatus::COMPLETED,
            'ended_at' => $endTime,
            'actual_duration_minutes' => $actualDuration,
            'attendance_status' => AttendanceStatus::ATTENDED,
        ], $sessionData);

        $this->update($updateData);

        // Update subscription session count using the new decoupled architecture
        $subscriptionForCounting = $this->getSubscriptionForCounting();
        if ($subscriptionForCounting) {
            $subscriptionForCounting->useSession();
        }

        // Update circle session count
        if ($this->circle) {
            $this->circle->increment('sessions_completed');
        }

        return $this;
    }

    public function cancel(string $reason, ?User $cancelledBy = null): self
    {
        if (! $this->can_cancel) {
            throw new \Exception('لا يمكن إلغاء الجلسة في هذا الوقت');
        }

        $this->update([
            'status' => SessionStatus::CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy?->id,
            'cancelled_at' => now(),
        ]);

        return $this;
    }

    public function reschedule(\Carbon\Carbon $newDateTime, ?string $reason = null): bool
    {
        if (! $this->can_reschedule) {
            return false;
        }

        return $this->update([
            'rescheduled_from' => $this->scheduled_at,
            'rescheduled_to' => $newDateTime,
            'scheduled_at' => $newDateTime,
            'reschedule_reason' => $reason,
            'status' => SessionStatus::SCHEDULED, // Reset to scheduled after rescheduling
        ]);
    }

    public function markAsNoShow(): self
    {
        $this->update([
            'status' => SessionStatus::ABSENT,
            'attendance_status' => AttendanceStatus::ABSENT,
            'ended_at' => $this->scheduled_at->addMinutes($this->duration_minutes),
        ]);

        return $this;
    }

    public function createMakeupSession(\Carbon\Carbon $scheduledAt, array $additionalData = []): self
    {
        // Get the session type key from the original session
        $sessionTypeKey = $this->getSessionTypeKey();

        $makeupData = array_merge([
            'academy_id' => $this->academy_id,
            'quran_teacher_id' => $this->quran_teacher_id,
            'quran_subscription_id' => $this->quran_subscription_id,
            'circle_id' => $this->circle_id,
            'student_id' => $this->student_id,
            'session_code' => self::generateSessionCode($sessionTypeKey),
            'session_type' => $this->session_type,
            'status' => SessionStatus::SCHEDULED,
            'title' => 'جلسة تعويضية - '.$this->title,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $this->duration_minutes,
            'is_makeup_session' => true,
            'makeup_session_for' => $this->id,
        ], $additionalData);

        return self::create($makeupData);
    }

    // Common meeting methods (generateMeetingLink, getMeetingInfo, isMeetingValid,
    // getMeetingJoinUrl, generateParticipantToken, getRoomInfo, endMeeting,
    // isUserInMeeting) are inherited from BaseSession

    // Override to provide Quran-specific recording settings
    protected function getDefaultRecordingEnabled(): bool
    {
        return $this->recording_enabled ?? true; // Quran sessions often need recording
    }

    protected function getDefaultMaxParticipants(): int
    {
        return $this->session_type === 'group' ? 50 : 2;
    }

    /**
     * Start recording for this session
     */
    public function startRecording(array $options = []): array
    {
        if (! $this->meeting_room_name) {
            throw new \Exception('Meeting room not created yet');
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        $recordingOptions = [
            'layout' => $options['layout'] ?? 'grid',
            'video_quality' => $options['video_quality'] ?? 'high',
            'audio_only' => $options['audio_only'] ?? false,
        ];

        $recordingInfo = $livekitService->startRecording($this->meeting_room_name, $recordingOptions);

        // Update session with recording info
        $meetingData = $this->meeting_data ?? [];
        $meetingData['recording'] = $recordingInfo;

        $this->update([
            'recording_url' => $recordingInfo['recording_id'], // Temporary until file is ready
            'meeting_data' => $meetingData,
        ]);

        return $recordingInfo;
    }

    /**
     * Stop recording for this session
     */
    public function stopRecording(): array
    {
        if (! $this->meeting_data || ! isset($this->meeting_data['recording'])) {
            throw new \Exception('No active recording found');
        }

        $livekitService = app(\App\Services\LiveKitService::class);
        $recordingId = $this->meeting_data['recording']['recording_id'];

        $result = $livekitService->stopRecording($recordingId);

        // Update session with final recording info
        $meetingData = $this->meeting_data;
        $meetingData['recording'] = array_merge($meetingData['recording'], $result);

        $this->update([
            'recording_url' => $result['file_info']['download_url'] ?? null,
            'meeting_data' => $meetingData,
        ]);

        return $result;
    }

    /**
     * Set meeting duration limit
     */
    public function setMeetingDuration(int $durationMinutes): bool
    {
        if (! $this->meeting_room_name) {
            return false;
        }

        $livekitService = app(\App\Services\LiveKitService::class);

        $success = $livekitService->setMeetingDuration($this->meeting_room_name, $durationMinutes);

        if ($success) {
            $this->update(['duration_minutes' => $durationMinutes]);
        }

        return $success;
    }

    public function addFeedback(string $feedbackType, string $feedback, ?User $feedbackBy = null): self
    {
        $feedbackField = $feedbackType.'_feedback';

        if (! in_array($feedbackField, ['teacher_feedback', 'student_feedback', 'parent_feedback'])) {
            throw new \Exception('نوع التعليق غير صحيح');
        }

        $this->update([
            $feedbackField => $feedback,
        ]);

        return $this;
    }

    public function rate(int $rating): self
    {
        if ($rating < 1 || $rating > 5) {
            throw new \Exception('التقييم يجب أن يكون بين 1 و 5');
        }

        $this->update(['overall_rating' => $rating]);

        return $this;
    }

    private function getSurahName(int $surahNumber): string
    {
        $surahNames = [
            1 => 'الفاتحة', 2 => 'البقرة', 3 => 'آل عمران', 4 => 'النساء',
            5 => 'المائدة', 6 => 'الأنعام', 7 => 'الأعراف', 8 => 'الأنفال',
            9 => 'التوبة', 10 => 'يونس', 11 => 'هود', 12 => 'يوسف',
            13 => 'الرعد', 14 => 'إبراهيم', 15 => 'الحجر', 16 => 'النحل',
            17 => 'الإسراء', 18 => 'الكهف', 19 => 'مريم', 20 => 'طه',
            // Add all 114 surahs as needed
        ];

        return $surahNames[$surahNumber] ?? "سورة رقم {$surahNumber}";
    }

    // Static methods
    public static function createSession(array $data): self
    {
        $sessionTypeKey = self::getSessionTypeKeyFromData($data);

        return self::create(array_merge($data, [
            'session_code' => self::generateSessionCode($sessionTypeKey),
            'status' => SessionStatus::SCHEDULED->value,
            'is_makeup_session' => false,
        ]));
    }

    /**
     * Generate a unique session code for Quran sessions.
     *
     * Format: {TYPE}-{YYMM}-{SEQ}
     * - QI-2601-0042 (Individual)
     * - QG-2601-0023 (Group)
     * - QT-2601-0015 (Trial)
     *
     * @param  string  $sessionTypeKey  One of: quran_individual, quran_group, quran_trial
     */
    private static function generateSessionCode(string $sessionTypeKey): string
    {
        return \DB::transaction(function () use ($sessionTypeKey) {
            // Get prefix from config
            $prefix = config('session-naming.type_prefixes.'.$sessionTypeKey, 'QI');
            $yearMonth = now()->format('ym');
            $codePrefix = "{$prefix}-{$yearMonth}-";

            // Get the maximum sequence number for this type and month (including soft deleted)
            $lastSession = static::withTrashed()
                ->where('session_code', 'LIKE', $codePrefix.'%')
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING(session_code, -4) AS UNSIGNED) DESC')
                ->first(['session_code']);

            $nextSequence = 1;
            if ($lastSession && preg_match('/(\d{4})$/', $lastSession->session_code, $matches)) {
                $nextSequence = (int) $matches[1] + 1;
            }

            $sessionCode = $codePrefix.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

            // Double-check uniqueness (should not be needed with proper locking, but adds safety)
            $attempt = 0;
            while (static::withTrashed()->where('session_code', $sessionCode)->exists() && $attempt < 100) {
                $nextSequence++;
                $sessionCode = $codePrefix.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
                $attempt++;
            }

            // Final fallback: Use cryptographically secure random if sequence fails
            if ($attempt >= 100) {
                $sessionCode = $codePrefix.strtoupper(bin2hex(random_bytes(2)));
            }

            return $sessionCode;
        }, 5); // 5 retries for deadlock handling
    }

    /**
     * Determine the session type key from session data.
     */
    private static function getSessionTypeKeyFromData(array $data): string
    {
        if (! empty($data['is_trial'])) {
            return 'quran_trial';
        }

        $sessionType = $data['session_type'] ?? 'individual';
        if ($sessionType === 'circle' || $sessionType === 'group') {
            return 'quran_group';
        }

        return 'quran_individual';
    }

    // Boot method to handle model events
    protected static function booted()
    {
        // Handle session deletion - update circle counts if needed
        static::deleted(function ($session) {
            // Update individual circle counts if needed
            if ($session->individual_circle_id && $session->individualCircle) {
                $session->individualCircle->updateSessionCounts();
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
     */
    public function notifyHomeworkAssigned(): void
    {
        try {
            $student = $this->student;
            if (! $student) {
                return;
            }

            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendHomeworkAssignedNotification(
                $this,
                $student,
                null  // No specific homework ID for Quran homework
            );

            // Also notify parent if exists
            if ($student->studentProfile && $student->studentProfile->parent) {
                $notificationService->sendHomeworkAssignedNotification(
                    $this,
                    $student->studentProfile->parent->user,
                    null
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send homework notification for Quran session', [
                'session_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function getTodaysSessions(int $academyId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('academy_id', $academyId)
            ->today()
            ->with(['quranTeacher', 'student', 'subscription', 'circle']);

        if (isset($filters['teacher_id'])) {
            $query->where('quran_teacher_id', $filters['teacher_id']);
        }

        if (isset($filters['student_id'])) {
            $query->where(function ($subQuery) use ($filters) {
                // Individual sessions: direct student_id match
                $subQuery->where('student_id', $filters['student_id'])
                    // OR group sessions: student enrolled in the circle (use correct session_type)
                    ->orWhere(function ($groupQuery) use ($filters) {
                        $groupQuery->where('session_type', 'group')
                            ->whereHas('circle.students', function ($circleQuery) use ($filters) {
                                $circleQuery->where('student_id', $filters['student_id']);
                            });
                    });
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['session_type'])) {
            $query->where('session_type', $filters['session_type']);
        }

        return $query->orderBy('scheduled_at', 'asc')->get();
    }

    public static function getUpcomingSessions(int $teacherId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        $now = AcademyContextService::nowInAcademyTimezone();

        return self::where('quran_teacher_id', $teacherId)
            ->upcoming()
            ->whereBetween('scheduled_at', [$now, $now->copy()->addDays($days)])
            ->with(['student', 'subscription', 'circle'])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    public static function getSessionsNeedingFollowUp(int $academyId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('academy_id', $academyId)
            ->completed()
            ->where('follow_up_required', true)
            ->with(['quranTeacher', 'student'])
            ->get();
    }

    /**
     * Get students for this session based on session type
     */
    public function getStudentsForSession()
    {
        if ($this->session_type === 'group' && $this->circle) {
            return $this->circle->students;
        } elseif ($this->session_type === 'individual' && $this->student_id) {
            return collect([User::find($this->student_id)]);
        } elseif ($this->session_type === 'trial') {
            // For trial sessions, get student from direct relationship or trial request
            if ($this->student_id) {
                $student = User::find($this->student_id);

                return $student ? collect([$student]) : collect();
            }
            if ($this->trial_request_id && $this->trialRequest?->student_id) {
                $student = User::find($this->trialRequest->student_id);

                return $student ? collect([$student]) : collect();
            }
        }

        return collect();
    }

    /**
     * Accessor for students - dynamically returns students based on session type
     */
    public function getStudentsAttribute()
    {
        return $this->getStudentsForSession();
    }

    /**
     * Get homework statistics for this session
     * Homework completion is tracked via student_session_reports grading
     */
    public function getHomeworkStatsAttribute(): array
    {
        $homework = $this->sessionHomework;
        if (! $homework) {
            return ['has_homework' => false];
        }

        // Get session reports to check who completed homework
        $reports = $this->studentReports;
        $studentsWithGrades = $reports->filter(function ($report) {
            return ($report->new_memorization_degree > 0) || ($report->reservation_degree > 0);
        });

        return [
            'has_homework' => true,
            'total_pages' => $homework->total_pages,
            'new_memorization_pages' => $homework->new_memorization_pages,
            'review_pages' => $homework->review_pages,
            'total_students' => $reports->count(),
            'completed_count' => $studentsWithGrades->count(),
            'average_memorization_degree' => $reports->avg('new_memorization_degree') ?? 0,
            'average_reservation_degree' => $reports->avg('reservation_degree') ?? 0,
        ];
    }

    /**
     * Get attendance statistics for this session
     */
    public function getAttendanceStatsAttribute(): array
    {
        $attendances = $this->attendances()->with('student')->get();

        return [
            'total_students' => $attendances->count(),
            'present_count' => $attendances->where('attendance_status', AttendanceStatus::ATTENDED->value)->count(),
            'late_count' => $attendances->where('attendance_status', AttendanceStatus::LATE->value)->count(),
            'absent_count' => $attendances->where('attendance_status', AttendanceStatus::ABSENT->value)->count(),
            'left_early_count' => $attendances->where('attendance_status', AttendanceStatus::LEFT->value)->count(),
            'auto_tracked_count' => $attendances->where('auto_tracked', true)->count(),
            'manually_overridden_count' => $attendances->where('manually_overridden', true)->count(),
            'average_participation' => $attendances->whereNotNull('participation_score')->avg('participation_score') ?? 0,
            'attendances' => $attendances,
        ];
    }

    /**
     * Check if session has active homework
     */
    public function getHasActiveHomeworkAttribute(): bool
    {
        return $this->sessionHomework && $this->sessionHomework->is_active;
    }

    /**
     * Get the student for individual sessions
     */
    public function getStudentAttribute()
    {
        if ($this->student_id) {
            return User::find($this->student_id);
        }

        return null;
    }

    /**
     * Check if a user can manage this meeting (abstract method implementation)
     */
    public function canUserManageMeeting(User $user): bool
    {
        // Super admin can manage all meetings
        if (in_array($user->user_type, ['super_admin', 'admin'])) {
            return true;
        }

        // Teachers can manage their own sessions
        if ($user->user_type === 'quran_teacher' && $this->quran_teacher_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Get all participants who should have access to this meeting (abstract method implementation)
     */
    public function getMeetingParticipants(): \Illuminate\Database\Eloquent\Collection
    {
        $participants = collect();

        // Add teacher
        if ($this->quranTeacher) {
            $participants->push($this->quranTeacher);
        }

        // Add students based on session type
        if ($this->session_type === 'individual' && $this->student) {
            $participants->push($this->student);
        } elseif ($this->session_type === 'group' && $this->circle) {
            $participants = $participants->merge($this->circle->students);
        } elseif ($this->session_type === 'trial') {
            // For trial sessions, get student from direct relationship or trial request
            $students = $this->getStudentsForSession();
            $participants = $participants->merge($students);
        }

        return $participants;
    }

    /**
     * Get extended meeting configuration specific to Quran sessions
     */
    protected function getExtendedMeetingConfiguration(): array
    {
        return [
            'session_code' => $this->session_code,
            'session_type_detail' => $this->session_type,
            'preparation_minutes' => $this->getPreparationMinutes(),
            'ending_buffer_minutes' => $this->getEndingBufferMinutes(),
            'grace_period_minutes' => $this->getGracePeriodMinutes(),
            'lesson_objectives' => $this->lesson_objectives,
            'progress_summary' => $this->progress_summary,
            'teacher_id' => $this->quran_teacher_id,
            'student_id' => $this->student_id,
            'circle_id' => $this->circle_id,
            'individual_circle_id' => $this->individual_circle_id,
        ];
    }

    /**
     * Check if user is a participant in this session
     */
    public function isUserParticipant(User $user): bool
    {
        // Teacher is always a participant in their sessions
        if ($user->user_type === 'quran_teacher' && $this->quran_teacher_id === $user->id) {
            return true;
        }

        // For individual sessions, check if user is the enrolled student
        if ($this->session_type === 'individual') {
            return $this->student_id === $user->id;
        }

        // For group sessions, check if user is enrolled in the circle
        if ($this->session_type === 'group' && $this->circle) {
            return $this->circle->students()->where('users.id', $user->id)->exists();
        }

        // For trial sessions, check if user is linked via trial request
        if ($this->session_type === 'trial') {
            // First check direct student_id if set
            if ($this->student_id && $this->student_id === $user->id) {
                return true;
            }
            // Then check via trial request relationship
            if ($this->trial_request_id) {
                return $this->trialRequest?->student_id === $user->id;
            }
        }

        return false;
    }

    // ========================================
    // ABSTRACT METHOD IMPLEMENTATIONS (Required by BaseSession)
    // ========================================

    /**
     * Get the session type key for naming service.
     *
     * Returns one of:
     * - 'quran_trial' - Trial session
     * - 'quran_group' - Group circle session
     * - 'quran_individual' - Individual session
     */
    public function getSessionTypeKey(): string
    {
        // Check if it's a trial session (by session_type or trial_request_id)
        if ($this->session_type === 'trial' || $this->trial_request_id) {
            return 'quran_trial';
        }

        // Check if it's a group (circle) session
        if ($this->session_type === 'circle' || $this->session_type === 'group') {
            return 'quran_group';
        }

        // Default to individual session
        return 'quran_individual';
    }

    /**
     * Get the meeting type identifier (abstract method implementation)
     */
    public function getMeetingType(): string
    {
        return 'quran';
    }

    /**
     * Get all participants for this session (abstract method implementation)
     */
    public function getParticipants(): array
    {
        $participants = [];

        // Add the teacher (using quranTeacher relationship)
        if ($this->quranTeacher) {
            $participants[] = [
                'id' => $this->quranTeacher->id,
                'name' => trim($this->quranTeacher->first_name.' '.$this->quranTeacher->last_name),
                'email' => $this->quranTeacher->email,
                'role' => 'quran_teacher',
                'is_teacher' => true,
                'user' => $this->quranTeacher,
            ];
        }

        // For individual sessions, add the specific student
        if ($this->session_type === 'individual' && $this->student) {
            $participants[] = [
                'id' => $this->student->id,
                'name' => trim($this->student->first_name.' '.$this->student->last_name),
                'email' => $this->student->email,
                'role' => 'student',
                'is_teacher' => false,
                'user' => $this->student,
            ];
        }

        // For group sessions, add all enrolled students from the circle
        if ($this->session_type === 'group' && $this->circle) {
            $students = $this->circle->students()->get();
            foreach ($students as $student) {
                $participants[] = [
                    'id' => $student->id,
                    'name' => trim($student->first_name.' '.$student->last_name),
                    'email' => $student->email,
                    'role' => 'student',
                    'is_teacher' => false,
                    'user' => $student,
                ];
            }
        }

        // For trial sessions, add the student from trial request
        if ($this->session_type === 'trial') {
            $students = $this->getStudentsForSession();
            foreach ($students as $student) {
                $participants[] = [
                    'id' => $student->id,
                    'name' => trim($student->first_name.' '.$student->last_name),
                    'email' => $student->email,
                    'role' => 'student',
                    'is_teacher' => false,
                    'user' => $student,
                ];
            }
        }

        return $participants;
    }

    /**
     * Get meeting-specific configuration (MeetingCapable interface)
     */
    public function getMeetingConfiguration(): array
    {
        // Get academy settings for meeting configuration
        $academySettings = \App\Models\AcademySettings::where('academy_id', $this->academy_id)->first();
        $settingsJson = $academySettings?->settings ?? [];

        // Extract meeting settings from JSON settings or use defaults
        $defaultRecordingEnabled = $settingsJson['meeting_recording_enabled'] ?? true;
        $defaultMaxParticipants = $settingsJson['meeting_max_participants'] ?? 10;

        $config = [
            'session_type' => $this->session_type,
            'session_id' => $this->id,
            'session_code' => $this->session_code,
            'academy_id' => $this->academy_id,
            'duration_minutes' => $this->duration_minutes ?? 60,
            'max_participants' => $defaultMaxParticipants,
            'recording_enabled' => $defaultRecordingEnabled,
            'chat_enabled' => $settingsJson['meeting_chat_enabled'] ?? true,
            'screen_sharing_enabled' => $settingsJson['meeting_screen_sharing_enabled'] ?? true,
            'whiteboard_enabled' => $settingsJson['meeting_whiteboard_enabled'] ?? false,
            'breakout_rooms_enabled' => $settingsJson['meeting_breakout_rooms_enabled'] ?? false,
            'waiting_room_enabled' => $settingsJson['meeting_waiting_room_enabled'] ?? false,
            'mute_on_join' => $settingsJson['meeting_mute_on_join'] ?? false,
            'camera_on_join' => $settingsJson['meeting_camera_on_join'] ?? true,
        ];

        // Override with session-specific settings based on type
        if ($this->session_type === 'individual' || $this->session_type === 'trial') {
            // Individual and trial sessions: 1 teacher + 1 student
            $config['max_participants'] = 2;
            $config['waiting_room_enabled'] = false;
            $config['recording_enabled'] = $settingsJson['individual_recording_enabled'] ?? $defaultRecordingEnabled;
        } elseif ($this->session_type === 'group') {
            // Group sessions: 1 teacher + multiple students
            $config['max_participants'] = $settingsJson['circle_max_participants'] ?? 10;
            $config['recording_enabled'] = $settingsJson['circle_recording_enabled'] ?? $defaultRecordingEnabled;
            $config['waiting_room_enabled'] = $settingsJson['circle_waiting_room_enabled'] ?? true;
            $config['mute_on_join'] = true; // Always start muted in group sessions
        }

        return $config;
    }
}
