<?php

namespace App\Models;

use App\Traits\ScopedToAcademy;
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
 */
abstract class BaseSessionReport extends Model
{
    use HasFactory, ScopedToAcademy;

    /**
     * Shared fillable fields across all report types
     */
    protected $fillable = [
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
    ];

    /**
     * Shared casts across all report types
     */
    protected $casts = [
        'meeting_enter_time' => 'datetime',
        'meeting_leave_time' => 'datetime',
        'actual_attendance_minutes' => 'integer',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'attendance_percentage' => 'decimal:2',
        'meeting_events' => 'array',
        'evaluated_at' => 'datetime',
        'is_calculated' => 'boolean',
        'manually_evaluated' => 'boolean',
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
     * Can be overridden by child classes for different session types
     */
    protected function getGracePeriodMinutes(): int
    {
        return 15; // Default 15 minutes grace period
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
        return $query->where('attendance_status', 'attended');
    }

    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', 'absent');
    }

    public function scopeLate($query)
    {
        return $query->where('attendance_status', 'late');
    }

    public function scopePartial($query)
    {
        return $query->where('attendance_status', 'leaved');
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
        if (!$this->attendance_status) {
            return 'غير محدد';
        }

        try {
            return \App\Enums\AttendanceStatus::from($this->attendance_status)->label();
        } catch (\ValueError $e) {
            return 'غير محدد';
        }
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
     * Get performance level in Arabic
     */
    public function getPerformanceLevelAttribute(): string
    {
        $performance = $this->overall_performance;

        if ($performance === null) {
            return 'غير مقيم';
        }

        return match (true) {
            $performance >= 9 => 'ممتاز',
            $performance >= 8 => 'جيد جداً',
            $performance >= 7 => 'جيد',
            $performance >= 6 => 'مقبول',
            default => 'يحتاج تحسين'
        };
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

            $this->update([
                'meeting_enter_time' => $meetingAttendance->first_join_time,
                'meeting_leave_time' => $meetingAttendance->last_leave_time,
                'actual_attendance_minutes' => $actualMinutes,
                'attendance_status' => $this->calculateRealtimeAttendanceStatus($meetingAttendance),
                'attendance_percentage' => $this->calculateAttendancePercentage($actualMinutes),
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
     * Calculate real-time attendance status based on grace time rules
     * CRITICAL: Handle edge cases for students who join early/stay long
     * FIXED: Use pre-calculated status from MeetingAttendance when available
     */
    protected function calculateRealtimeAttendanceStatus(MeetingAttendance $meetingAttendance): string
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
            return 'absent';
        }

        $session = $this->session;
        $graceMinutes = $this->getGracePeriodMinutes();
        $sessionDuration = $session->duration_minutes ?? 60;

        // Check if first join was within grace period (persistent rule)
        $sessionStartTime = $session->scheduled_at;
        $graceThresholdTime = $sessionStartTime->copy()->addMinutes($graceMinutes);
        $joinedWithinGrace = $meetingAttendance->first_join_time->lte($graceThresholdTime);

        // Calculate current attendance percentage
        $currentDuration = $meetingAttendance->isCurrentlyInMeeting()
            ? $meetingAttendance->getCurrentSessionDuration()
            : $meetingAttendance->total_duration_minutes;

        $attendancePercentage = $sessionDuration > 0 ? ($currentDuration / $sessionDuration) * 100 : 0;

        // CRITICAL: 100% attendance override - if student attended 100%+, always mark as attended
        if ($attendancePercentage >= 100) {
            return 'attended'; // Anyone who attended full session should be marked attended
        }

        // If first join was after grace time, check if they made up for it with attendance
        if (! $joinedWithinGrace) {
            // If late but attended 95%+, mark as 'late' not 'absent'
            if ($attendancePercentage >= 95) {
                return 'late'; // Late arrival but excellent attendance
            } elseif ($attendancePercentage >= 80) {
                return 'leaved'; // Late and decent attendance
            } else {
                return 'absent'; // Late and poor attendance
            }
        }

        // Joined on time - standard percentage rules
        if ($attendancePercentage >= 80) {
            return 'attended';
        } elseif ($attendancePercentage >= 30) {
            return 'leaved';
        } else {
            return 'absent';
        }
    }

    /**
     * Calculate attendance percentage based on current minutes
     */
    protected function calculateAttendancePercentage(int $actualMinutes): float
    {
        if (! $this->session) {
            return 0.0;
        }

        $sessionDuration = $this->session->duration_minutes ?? 60;

        return $sessionDuration > 0 ? min(100, ($actualMinutes / $sessionDuration) * 100) : 0.0;
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

        // Determine attendance status
        $this->attendance_status = $this->determineAttendanceStatus($sessionStartTime, $sessionDuration, $lateThreshold);

        // Mark as calculated
        $this->is_calculated = true;

        $this->save();
    }

    /**
     * Determine attendance status based on attendance percentage and timing
     */
    protected function determineAttendanceStatus(Carbon $sessionStartTime, int $sessionDuration, int $graceMinutes): string
    {
        // If never joined, definitely absent
        if (! $this->meeting_enter_time) {
            return 'absent';
        }

        // CRITICAL: 100% attendance override - always mark as attended
        if ($this->attendance_percentage >= 100) {
            return 'attended'; // Anyone who attended full session should be marked attended
        }

        // Determine status based on attendance percentage
        if ($this->attendance_percentage < 30) {
            return 'absent';
        } elseif ($this->attendance_percentage < 80) {
            return 'leaved';
        }

        // If attended 80%+, check if they were late
        // If 95%+ attendance but late, still mark as 'attended' (they made up for it)
        if ($this->attendance_percentage >= 95) {
            return 'attended'; // Excellent attendance overrides lateness
        }

        return $this->is_late ? 'late' : 'attended';
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
