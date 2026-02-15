<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\PerformanceLevel;
use App\Models\Traits\ScopedToAcademy;
use App\Services\Traits\AttendanceCalculatorTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Base Session Report Model
 *
 * Abstract base class for all session report models (Quran, Academic, Interactive)
 * Contains shared attendance tracking, evaluation, and reporting logic.
 *
 * This follows the same pattern as BaseSession (Phase 5) and BaseSessionAttendance (Phase 7).
 *
 * @property int $id
 * @property int $session_id
 * @property int $student_id
 * @property int|null $teacher_id
 * @property int|null $academy_id
 * @property string|null $notes
 * @property \Carbon\Carbon|null $meeting_enter_time
 * @property \Carbon\Carbon|null $meeting_leave_time
 * @property int|null $actual_attendance_minutes
 * @property bool $is_late
 * @property int|null $late_minutes
 * @property string $attendance_status
 * @property float|null $attendance_percentage
 * @property array|null $meeting_events
 * @property \Carbon\Carbon|null $evaluated_at
 * @property bool $is_calculated
 * @property bool $manually_evaluated
 * @property string|null $override_reason
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
abstract class BaseSessionReport extends Model
{
    use AttendanceCalculatorTrait;
    use HasFactory;
    use ScopedToAcademy;

    /**
     * Shared fillable fields across all report types
     *
     * IMPORTANT: Made static to allow child classes to access via parent::$baseFillable
     * Same pattern as BaseSession.
     */
    protected static $baseFillable = [
        'session_id',
        'student_id',
        'teacher_id',
        'academy_id',
        'notes',
        'meeting_enter_time',
        'meeting_leave_time',
        'actual_attendance_minutes',
        'is_late',
        'late_minutes',
        'attendance_status',
        'attendance_percentage',
        'meeting_events',
        'evaluated_at',
        'is_calculated',
        'manually_evaluated',
        'override_reason',
        // Homework tracking fields (added for unified homework system)
        'homework_submitted_at',
        'homework_submission_id',
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
        $this->fillable = static::$baseFillable;
        parent::__construct($attributes);
    }

    /**
     * Shared casts across all report types
     */
    protected $casts = [
        'meeting_enter_time' => 'datetime',
        'meeting_leave_time' => 'datetime',
        'actual_attendance_minutes' => 'integer',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'attendance_status' => AttendanceStatus::class,
        'attendance_percentage' => 'decimal:2',
        'meeting_events' => 'array',
        'evaluated_at' => 'datetime',
        'is_calculated' => 'boolean',
        'manually_evaluated' => 'boolean',
        // Homework tracking casts
        'homework_submitted_at' => 'datetime',
    ];

    // ========================================
    // Abstract Methods (Must be implemented by child classes)
    // ========================================

    /**
     * Get the session this report belongs to
     * Each child class must implement with their specific session type
     */
    abstract public function session(): BelongsTo;

    /**
     * Get session-specific performance data
     * Used by performance calculations and attributes
     */
    abstract protected function getSessionSpecificPerformanceData(): ?float;

    /**
     * Get the grace period threshold for late arrivals in minutes
     * Uses academy settings, can be overridden by child classes
     */
    protected function getGracePeriodMinutes(): int
    {
        // Try to get academy from session relationship
        $academy = $this->session?->academy;

        return $academy?->settings?->default_late_tolerance_minutes
            ?? config('business.attendance.grace_period_minutes', 15);
    }

    // ========================================
    // Shared Relationships
    // ========================================

    /**
     * Relationship with Student (User)
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Relationship with Teacher (User)
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Relationship with Academy
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    // ========================================
    // Shared Scopes
    // ========================================

    public function scopePresent($query)
    {
        return $query->where('attendance_status', AttendanceStatus::ATTENDED);
    }

    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', AttendanceStatus::ABSENT);
    }

    public function scopeLate($query)
    {
        return $query->where('attendance_status', AttendanceStatus::LATE);
    }

    public function scopePartial($query)
    {
        return $query->where('attendance_status', AttendanceStatus::LEFT);
    }

    public function scopeEvaluated($query)
    {
        return $query->whereNotNull('evaluated_at');
    }

    public function scopeToday($query)
    {
        return $query->whereHas('session', function ($q) {
            $q->whereDate('scheduled_at', today());
        });
    }

    public function scopeThisWeek($query)
    {
        return $query->whereHas('session', function ($q) {
            $q->whereBetween('scheduled_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        });
    }

    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForCurrentAcademy($query)
    {
        $currentAcademyId = config('app.current_academy_id') ??
                           session('current_academy_id') ??
                           auth()->user()?->academy_id;

        if ($currentAcademyId) {
            return $query->where('academy_id', $currentAcademyId);
        }

        return $query;
    }

    // ========================================
    // Shared Attributes
    // ========================================

    /**
     * Get attendance status in Arabic
     */
    public function getAttendanceStatusInArabicAttribute(): string
    {
        return $this->attendance_status?->label() ?? __('enums.attendance_status.unknown');
    }

    /**
     * Get attendance status in Arabic (alias for compatibility)
     */
    public function getAttendanceStatusArabicAttribute(): string
    {
        return $this->attendance_status_in_arabic;
    }

    /**
     * Get overall performance score
     * Delegates to child class for session-specific calculation
     */
    public function getOverallPerformanceAttribute(): ?float
    {
        return $this->getSessionSpecificPerformanceData();
    }

    /**
     * Get performance level as localized string
     */
    public function getPerformanceLevelAttribute(): string
    {
        $level = PerformanceLevel::fromScore($this->overall_performance);

        return $level?->label() ?? __('enums.performance_level.not_evaluated');
    }

    /**
     * Get performance level enum instance
     */
    public function getPerformanceLevelEnumAttribute(): ?PerformanceLevel
    {
        return PerformanceLevel::fromScore($this->overall_performance);
    }

    /**
     * Check if student is late (accessor)
     */
    public function getIsLateAttribute(): bool
    {
        return $this->attributes['is_late'] ?? (($this->late_minutes ?? 0) > 0);
    }

    // ========================================
    // Shared Methods
    // ========================================

    /**
     * Sync attendance data from MeetingAttendance record
     * CRITICAL: Includes real-time attendance for active users
     */
    public function syncFromMeetingAttendance(): void
    {
        $meetingAttendance = MeetingAttendance::where('session_id', $this->session_id)
            ->where('user_id', $this->student_id)
            ->first();

        if ($meetingAttendance) {
            // Use current session duration for active users
            $actualMinutes = $meetingAttendance->isCurrentlyInMeeting()
                ? $meetingAttendance->getCurrentSessionDuration()  // Includes current active time
                : $meetingAttendance->total_duration_minutes;      // Completed cycles only

            $sessionDuration = $this->session->duration_minutes ?? 60;
            $this->update([
                'meeting_enter_time' => $meetingAttendance->first_join_time,
                'meeting_leave_time' => $meetingAttendance->last_leave_time,
                'actual_attendance_minutes' => $actualMinutes,
                'attendance_status' => $this->calculateRealtimeAttendanceStatusFromMeeting($meetingAttendance),
                'attendance_percentage' => $this->calculateAttendancePercentage($actualMinutes, $sessionDuration),
                'meeting_events' => $meetingAttendance->join_leave_cycles ?? [],
                'is_calculated' => $meetingAttendance->is_calculated,
            ]);

            // Recalculate lateness based on session timing
            $this->calculateLateness();
        }
    }

    /**
     * Calculate lateness based on meeting enter time
     */
    protected function calculateLateness(): void
    {
        if (! $this->meeting_enter_time || ! $this->session) {
            return;
        }

        $session = $this->session;
        $lateThreshold = $this->getGracePeriodMinutes();

        $sessionStartTime = $session->scheduled_at;
        $lateThresholdTime = $sessionStartTime->copy()->addMinutes($lateThreshold);

        $this->is_late = $this->meeting_enter_time->isAfter($lateThresholdTime);
        $this->late_minutes = $this->is_late
            ? $this->meeting_enter_time->diffInMinutes($sessionStartTime)
            : 0;
    }

    /**
     * Calculate real-time attendance status based on grace time rules.
     * Uses centralized AttendanceCalculatorTrait for calculation logic.
     *
     * CRITICAL: Uses pre-calculated status from MeetingAttendance when available.
     */
    protected function calculateRealtimeAttendanceStatusFromMeeting(MeetingAttendance $meetingAttendance): string
    {
        // CRITICAL FIX: If attendance is already calculated, use that status (most accurate)
        if ($meetingAttendance->is_calculated && $meetingAttendance->attendance_status) {
            \Log::info('Using pre-calculated attendance status from MeetingAttendance', [
                'session_id' => $this->session_id,
                'student_id' => $this->student_id,
                'status' => $meetingAttendance->attendance_status,
            ]);

            return $meetingAttendance->attendance_status;
        }

        // Fallback to real-time calculation if not yet finalized
        if (! $meetingAttendance->first_join_time || ! $this->session) {
            return AttendanceStatus::ABSENT->value;
        }

        $session = $this->session;
        $graceMinutes = $this->getGracePeriodMinutes();
        $sessionDuration = $session->duration_minutes ?? 60;

        // Calculate current attendance
        $currentDuration = $meetingAttendance->isCurrentlyInMeeting()
            ? $meetingAttendance->getCurrentSessionDuration()
            : $meetingAttendance->total_duration_minutes;

        // Delegate to centralized trait method
        return $this->calculateRealtimeAttendanceStatus(
            $meetingAttendance->first_join_time,
            $session->scheduled_at,
            $sessionDuration,
            $currentDuration,
            $graceMinutes,
            $meetingAttendance->isCurrentlyInMeeting()
        );
    }

    /**
     * Calculate attendance percentage based on current minutes.
     * Note: Uses trait method with same signature to avoid conflicts.
     */
    protected function calculateAttendancePercentageForReport(int $actualMinutes): float
    {
        if (! $this->session) {
            return 0.0;
        }

        $sessionDuration = $this->session->duration_minutes ?? 60;

        return $this->calculateAttendancePercentage($actualMinutes, $sessionDuration);
    }

    /**
     * Calculate attendance automatically based on meeting entry/exit times
     */
    public function calculateAttendance(array $meetingData = []): void
    {
        $session = $this->session;
        if (! $session || ! $session->scheduled_at) {
            return;
        }

        $sessionStartTime = $session->scheduled_at;
        $sessionDuration = $session->duration_minutes ?? 60;
        $sessionEndTime = $sessionStartTime->copy()->addMinutes($sessionDuration);
        $lateThreshold = $this->getGracePeriodMinutes();

        // Update meeting times if provided
        if (! empty($meetingData)) {
            $this->meeting_enter_time = $meetingData['enter_time'] ?? $this->meeting_enter_time;
            $this->meeting_leave_time = $meetingData['leave_time'] ?? $this->meeting_leave_time;
            $this->meeting_events = array_merge($this->meeting_events ?? [], $meetingData['events'] ?? []);
        }

        // Calculate if student was late
        if ($this->meeting_enter_time) {
            $lateThresholdTime = $sessionStartTime->copy()->addMinutes($lateThreshold);
            $this->is_late = $this->meeting_enter_time->isAfter($lateThresholdTime);
            $this->late_minutes = $this->is_late
                ? $this->meeting_enter_time->diffInMinutes($sessionStartTime)
                : 0;
        }

        // Calculate actual attendance duration
        if ($this->meeting_enter_time && $this->meeting_leave_time) {
            $this->actual_attendance_minutes = $this->meeting_enter_time->diffInMinutes($this->meeting_leave_time);
        } elseif ($this->meeting_enter_time && ! $this->meeting_leave_time) {
            // If student entered but didn't leave, assume they stayed until session end
            $this->actual_attendance_minutes = $this->meeting_enter_time->diffInMinutes($sessionEndTime);
        }

        // Calculate attendance percentage
        $this->attendance_percentage = $sessionDuration > 0
            ? min(100, ($this->actual_attendance_minutes / $sessionDuration) * 100)
            : 0;

        // Determine attendance status using centralized trait
        $this->attendance_status = $this->determineAttendanceStatusForReport($sessionStartTime, $sessionDuration, $lateThreshold);

        // Mark as calculated
        $this->is_calculated = true;

        $this->save();
    }

    /**
     * Determine attendance status based on attendance percentage and timing.
     * Uses centralized AttendanceCalculatorTrait for calculation logic.
     */
    protected function determineAttendanceStatusForReport(Carbon $sessionStartTime, int $sessionDuration, int $graceMinutes): string
    {
        // Delegate to centralized trait method
        return $this->calculateAttendanceStatus(
            $this->meeting_enter_time,
            $sessionStartTime,
            $sessionDuration,
            $this->actual_attendance_minutes ?? 0,
            $graceMinutes
        );
    }

    /**
     * Create or update report for a student in a session
     * Static factory method used by child classes
     */
    public static function createOrUpdateReport(
        int $sessionId,
        int $studentId,
        int $teacherId,
        int $academyId,
        array $data = []
    ): self {
        return static::updateOrCreate(
            [
                'session_id' => $sessionId,
                'student_id' => $studentId,
            ],
            array_merge([
                'teacher_id' => $teacherId,
                'academy_id' => $academyId,
            ], $data)
        );
    }
}
