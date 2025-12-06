<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\Traits\CountsTowardsSubscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
 * - Simplified pages system: current_surah, current_page
 * - Quality metrics tracked via homework system
 *
 * @property int $quran_teacher_id
 * @property int|null $quran_subscription_id
 * @property int|null $circle_id
 * @property int|null $individual_circle_id
 * @property int|null $student_id
 * @property string $session_type 'individual', 'group', or 'trial'
 * @property bool $subscription_counted Flag to prevent double-counting
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

        // Quran progress tracking (pages-only system)
        'current_surah',
        'current_page',

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
            // Quran progress (pages-only system)
            'current_surah' => 'integer',
            'current_page' => 'integer',

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

    public function generatedFromSchedule(): BelongsTo
    {
        return $this->belongsTo(QuranCircleSchedule::class, 'generated_from_schedule_id');
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
     * العلاقة مع تقارير الطلاب الجديدة
     */
    public function studentReports(): HasMany
    {
        return $this->hasMany(StudentSessionReport::class, 'session_id');
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

    /**
     * Unified homework submission system (polymorphic)
     */
    public function homeworkSubmissions()
    {
        return $this->morphMany(HomeworkSubmission::class, 'submitable');
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
        return $query->where('status', 'missed');
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
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
     */
    public function isReadyToStart(): bool
    {
        if (! $this->scheduled_at || $this->status !== SessionStatus::SCHEDULED) {
            return false;
        }

        $minutesUntilSession = now()->diffInMinutes($this->scheduled_at, false);

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

            if (!$session) {
                throw new \Exception("Session {$this->id} not found");
            }

            if (! $session->status->canComplete()) {
                return false;
            }

            $updateData = array_merge([
                'status' => SessionStatus::COMPLETED,
                'ended_at' => now(),
                'attendance_status' => 'attended',
            ], $additionalData);

            $session->update($updateData);

            // Update circle progress if applicable
            if ($session->individualCircle) {
                $session->individualCircle->updateProgress();
            }

            // Record attendance for students
            $session->recordSessionAttendance('attended');

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
    public function markAsCancelled(?string $reason = null, ?string $cancelledBy = null): bool
    {
        if (! $this->status->canCancel()) {
            return false;
        }

        $this->update([
            'status' => SessionStatus::CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
        ]);

        // Record attendance as cancelled (doesn't count towards subscription)
        $this->recordSessionAttendance('cancelled');

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
            'attendance_status' => 'absent',
            'attendance_notes' => $reason,
        ]);

        // Record attendance as absent (counts towards subscription)
        $this->recordSessionAttendance('absent');

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
                'attendance_status' => 'absent', // Default to absent until meeting data is available
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
                    'attendance_status' => 'absent', // Default to absent until meeting data is available
                    'is_calculated' => true,
                    'evaluated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Get the subscription instance for counting (required by CountsTowardsSubscription trait)
     *
     * For Quran sessions:
     * - Individual sessions: subscription comes from individual circle
     * - Group sessions: don't count against individual subscriptions
     *
     * @return \App\Models\QuranSubscription|null
     */
    protected function getSubscriptionForCounting()
    {
        // Only individual sessions with individual circle have subscriptions
        if ($this->session_type === 'individual' && $this->individualCircle) {
            return $this->individualCircle->subscription;
        }

        // Group sessions don't count against individual subscriptions
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
        return $query->where('attendance_status', 'attended');
    }

    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', 'absent');
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
            'circle' => 'حلقة جماعية',
            'makeup' => 'جلسة تعويضية',
            'trial' => 'جلسة تجريبية',
            'assessment' => 'جلسة تقييم',
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
            'leaved' => 'غادر مبكراً',
        ];

        return $statuses[$this->attendance_status] ?? $this->attendance_status;
    }

    public function getLocationTypeTextAttribute(): string
    {
        $types = [
            'online' => 'عبر الإنترنت',
            'physical' => 'حضوري',
            'hybrid' => 'مختلط',
        ];

        return $types[$this->location_type] ?? $this->location_type;
    }

    public function getFormattedScheduledTimeAttribute(): string
    {
        return $this->scheduled_at ? $this->scheduled_at->format('Y-m-d H:i') : 'غير محدد';
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->scheduled_at ? $this->scheduled_at->format('Y-m-d') : 'غير محدد';
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->scheduled_at ? $this->scheduled_at->format('H:i') : 'غير محدد';
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

    public function getCanStartAttribute(): bool
    {
        return $this->status === SessionStatus::SCHEDULED &&
               $this->scheduled_at &&
               $this->scheduled_at->diffInMinutes(now()) <= 15;
    }

    public function getCanCancelAttribute(): bool
    {
        return in_array($this->status, [SessionStatus::SCHEDULED, SessionStatus::ONGOING]) &&
               $this->scheduled_at &&
               $this->scheduled_at->diffInHours(now()) >= 2;
    }

    public function getCanRescheduleAttribute(): bool
    {
        return $this->status === SessionStatus::SCHEDULED &&
               $this->scheduled_at &&
               $this->scheduled_at->diffInHours(now()) >= 24;
    }

    public function getProgressSummaryAttribute(): string
    {
        // Use paper-based progress if available
        if ($this->current_page && $this->current_face) {
            $faceName = $this->current_face == 1 ? 'الوجه الأول' : 'الوجه الثاني';
            $papersMemorized = $this->papers_memorized_today ?? 0;

            $summary = "الصفحة {$this->current_page} - {$faceName}";

            if ($papersMemorized > 0) {
                $summary .= " (حُفظ {$papersMemorized} وجه)";
            }

            return $summary;
        }

        // Fallback to verse-based progress
        if (! $this->current_surah || ! $this->current_verse) {
            return 'لم يتم تحديد التقدم';
        }

        $surahName = $this->getSurahName($this->current_surah);
        $versesMemorized = $this->verses_memorized_today ?? 0;

        $summary = "سورة {$surahName} - آية {$this->current_verse}";

        if ($versesMemorized > 0) {
            $summary .= " (حُفظت {$versesMemorized} آيات)";
        }

        return $summary;
    }

    /**
     * Convert verses to papers (وجه)
     * Each paper (وجه) = approximately 15 verses
     */
    public function convertVersesToPapers(int $verses): float
    {
        return round($verses / 15, 2);
    }

    /**
     * Convert papers to verses
     * Each paper (وجه) = approximately 15 verses
     */
    public function convertPapersToVerses(float $papers): int
    {
        return (int) round($papers * 15);
    }

    /**
     * Update session progress using papers
     */
    public function updateProgressByPapers(int $page, int $face, float $papersMemorized, float $papersCovered = 0): void
    {
        $this->update([
            'current_page' => $page,
            'current_face' => $face,
            'papers_memorized_today' => $papersMemorized,
            'papers_covered_today' => $papersCovered,
            'verses_memorized_today' => $this->convertPapersToVerses($papersMemorized),
        ]);
    }

    public function getPerformanceSummaryAttribute(): array
    {
        return [
            'recitation_quality' => $this->recitation_quality,
            'tajweed_accuracy' => $this->tajweed_accuracy,
            'mistakes_count' => $this->mistakes_count,
            'overall_rating' => $this->overall_rating,
            'verses_memorized' => $this->verses_memorized_today,
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

        // Update subscription session count
        if ($this->subscription) {
            $this->subscription->useSession();
        }

        // Update circle session count
        if ($this->circle) {
            $this->circle->increment('sessions_completed');
        }

        // Create progress record if progress data is provided
        if (isset($sessionData['verses_memorized_today']) && $sessionData['verses_memorized_today'] > 0) {
            $this->recordProgress($sessionData);
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

    public function reschedule(\Carbon\Carbon $newDateTime, ?string $reason = null): self
    {
        if (! $this->can_reschedule) {
            throw new \Exception('لا يمكن إعادة جدولة الجلسة في هذا الوقت');
        }

        $this->update([
            'rescheduled_from' => $this->scheduled_at,
            'scheduled_at' => $newDateTime,
            'reschedule_reason' => $reason,
            'status' => SessionStatus::SCHEDULED, // Reset to scheduled after rescheduling
        ]);

        return $this;
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
        $makeupData = array_merge([
            'academy_id' => $this->academy_id,
            'quran_teacher_id' => $this->quran_teacher_id,
            'quran_subscription_id' => $this->quran_subscription_id,
            'circle_id' => $this->circle_id,
            'student_id' => $this->student_id,
            'session_code' => self::generateSessionCode($this->academy_id),
            'session_type' => $this->session_type,
            'status' => SessionStatus::SCHEDULED,
            'title' => 'جلسة تعويضية - '.$this->title,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $this->duration_minutes,
            'location_type' => $this->location_type,
            'is_makeup_session' => true,
            'makeup_session_for' => $this->id,
        ], $additionalData);

        return self::create($makeupData);
    }

    // Note: recordProgress() method removed - Progress is now calculated
    // dynamically from session reports using the QuranReportService

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
        return self::create(array_merge($data, [
            'session_code' => self::generateSessionCode($data['academy_id']),
            'status' => 'scheduled',
            'is_makeup_session' => false,
        ]));
    }

    private static function generateSessionCode(int $academyId): string
    {
        return \DB::transaction(function () use ($academyId) {
            // Get the maximum sequence number for this academy (including soft deleted)
            $maxNumber = static::withTrashed()
                ->where('academy_id', $academyId)
                ->where('session_code', 'LIKE', "QSE-{$academyId}-%")
                ->lockForUpdate()
                ->get()
                ->map(function ($session) {
                    // Extract the sequence number from session_code format: QSE-{academyId}-{sequence}
                    $parts = explode('-', $session->session_code);
                    return isset($parts[2]) ? (int) $parts[2] : 0;
                })
                ->max();

            $nextNumber = ($maxNumber ?? 0) + 1;
            $sessionCode = 'QSE-'.$academyId.'-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Double-check uniqueness (should not be needed with proper locking, but adds safety)
            $attempt = 0;
            while (static::withTrashed()->where('session_code', $sessionCode)->exists() && $attempt < 100) {
                $nextNumber++;
                $sessionCode = 'QSE-'.$academyId.'-'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
                $attempt++;
            }

            return $sessionCode;
        });
    }

    // Boot method to handle model events
    protected static function booted()
    {
        // Handle session deletion - check if group circle needs re-scheduling
        static::deleted(function ($session) {
            if ($session->circle_id && $session->generatedFromSchedule) {
                // This was a group circle session generated from a schedule
                $schedule = $session->generatedFromSchedule;

                if ($schedule && $schedule->is_active) {
                    // Check if we need to generate more sessions for this month
                    $currentMonth = now()->format('Y-m');
                    $currentMonthSessions = self::where('circle_id', $session->circle_id)
                        ->whereRaw("DATE_FORMAT(scheduled_at, '%Y-%m') = ?", [$currentMonth])
                        ->count();

                    $monthlyLimit = $schedule->circle->monthly_sessions_count ?? 4;

                    if ($currentMonthSessions < $monthlyLimit) {
                        // Try to generate replacement sessions
                        $schedule->generateUpcomingSessions();
                    }
                }
            }

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
            if (!$student) {
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
            ->with(['quranTeacher.user', 'student', 'subscription', 'circle']);

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
        return self::where('quran_teacher_id', $teacherId)
            ->upcoming()
            ->whereBetween('scheduled_at', [now(), now()->addDays($days)])
            ->with(['student', 'subscription', 'circle'])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    public static function getSessionsNeedingFollowUp(int $academyId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('academy_id', $academyId)
            ->completed()
            ->where('follow_up_required', true)
            ->with(['quranTeacher.user', 'student'])
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
        $studentsWithGrades = $reports->filter(function($report) {
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
            'present_count' => $attendances->where('attendance_status', 'attended')->count(),
            'late_count' => $attendances->where('attendance_status', 'late')->count(),
            'absent_count' => $attendances->where('attendance_status', 'absent')->count(),
            'left_early_count' => $attendances->where('attendance_status', 'leaved')->count(),
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
            'current_surah' => $this->current_surah,
            'current_verse' => $this->current_verse,
            'lesson_objectives' => $this->lesson_objectives,
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

        return false;
    }

    // ========================================
    // ABSTRACT METHOD IMPLEMENTATIONS (Required by BaseSession)
    // ========================================

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

        // Add the teacher
        if ($this->teacher) {
            $participants[] = [
                'id' => $this->teacher->id,
                'name' => trim($this->teacher->first_name.' '.$this->teacher->last_name),
                'email' => $this->teacher->email,
                'role' => 'quran_teacher',
                'is_teacher' => true,
                'user' => $this->teacher,
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
