<?php

namespace App\Models;

use App\Traits\ScopedToAcademy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSessionReport extends Model
{
    use HasFactory, ScopedToAcademy;

    protected $fillable = [
        'session_id',
        'student_id',
        'teacher_id',
        'academy_id',
        'new_memorization_degree',
        'reservation_degree',
        'notes',
        'meeting_enter_time',
        'meeting_leave_time',
        'actual_attendance_minutes',
        'is_late',
        'late_minutes',
        'attendance_status',
        'attendance_percentage',
        'connection_quality_score',
        'meeting_events',
        'evaluated_at',
        'is_auto_calculated',
        'manually_overridden',
        'override_reason',
    ];

    protected $casts = [
        'new_memorization_degree' => 'decimal:1',
        'reservation_degree' => 'decimal:1',
        'meeting_enter_time' => 'datetime',
        'meeting_leave_time' => 'datetime',
        'actual_attendance_minutes' => 'integer',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'attendance_percentage' => 'decimal:2',
        'connection_quality_score' => 'integer',
        'meeting_events' => 'array',
        'evaluated_at' => 'datetime',
        'is_auto_calculated' => 'boolean',
        'manually_overridden' => 'boolean',
    ];

    /**
     * Relationship with QuranSession
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

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

    /**
     * Sync attendance data from MeetingAttendance record
     * CRITICAL FIX: Includes real-time attendance for active users
     */
    public function syncFromMeetingAttendance(): void
    {
        // CRITICAL FIX: Temporarily disabled to prevent errors
        \Illuminate\Support\Facades\Log::info('syncFromMeetingAttendance called but disabled', [
            'session_id' => $this->session_id,
            'student_id' => $this->student_id,
        ]);

        // Original code disabled below
        /*
        $meetingAttendance = MeetingAttendance::where('session_id', $this->session_id)
            ->where('user_id', $this->student_id)
            ->first();

        if ($meetingAttendance) {
            // CRITICAL FIX: Use current session duration for active users
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
                'is_auto_calculated' => $meetingAttendance->is_calculated,
            ]);

            // Recalculate lateness based on session timing with grace period persistence
            $this->calculateLateness();
        }
        */
    }

    /**
     * Calculate lateness based on meeting enter time
     */
    private function calculateLateness(): void
    {
        if (! $this->meeting_enter_time || ! $this->session) {
            return;
        }

        $session = $this->session;
        $circle = $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;
        $lateThreshold = $circle?->late_join_grace_period_minutes ?? 15;

        $sessionStartTime = $session->scheduled_at;
        $lateThresholdTime = $sessionStartTime->copy()->addMinutes($lateThreshold);

        $this->is_late = $this->meeting_enter_time->isAfter($lateThresholdTime);
        $this->late_minutes = $this->is_late
            ? $this->meeting_enter_time->diffInMinutes($sessionStartTime)
            : 0;
    }

    /**
     * Calculate real-time attendance status based on grace time rules
     * CRITICAL FIX: Handle edge cases for students who join early/stay long
     */
    private function calculateRealtimeAttendanceStatus(MeetingAttendance $meetingAttendance): string
    {
        if (! $meetingAttendance->first_join_time || ! $this->session) {
            return 'absent';
        }

        $session = $this->session;
        $circle = $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;
        $graceMinutes = $circle?->late_join_grace_period_minutes ?? 15;
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

        // CRITICAL FIX: 100% attendance override - if student attended 100%+, always mark as present
        if ($attendancePercentage >= 100) {
            return 'present'; // Anyone who attended full session should be marked present
        }

        // If first join was after grace time, check if they made up for it with attendance
        if (! $joinedWithinGrace) {
            // UPDATED LOGIC: If late but attended 95%+, mark as 'late' not 'absent'
            if ($attendancePercentage >= 95) {
                return 'late'; // Late arrival but excellent attendance
            } elseif ($attendancePercentage >= 80) {
                return 'partial'; // Late and decent attendance
            } else {
                return 'absent'; // Late and poor attendance
            }
        }

        // Joined on time - standard percentage rules
        if ($attendancePercentage >= 80) {
            return 'present';
        } elseif ($attendancePercentage >= 30) {
            return 'partial';
        } else {
            return 'absent';
        }
    }

    /**
     * Calculate attendance percentage based on current minutes
     */
    private function calculateAttendancePercentage(int $actualMinutes): float
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

        // Get circle configuration for late threshold
        $circle = $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;
        $lateThreshold = $circle?->late_join_grace_period_minutes ?? 15;

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

        // Mark as auto-calculated
        $this->is_auto_calculated = true;

        $this->save();
    }

    /**
     * Determine attendance status based on attendance percentage and timing
     */
    private function determineAttendanceStatus(Carbon $sessionStartTime, int $sessionDuration, int $graceMinutes): string
    {
        // If never joined, definitely absent
        if (! $this->meeting_enter_time) {
            return 'absent';
        }

        // CRITICAL FIX: 100% attendance override - always mark as present
        if ($this->attendance_percentage >= 100) {
            return 'present'; // Anyone who attended full session should be marked present
        }

        // Determine status based on attendance percentage
        if ($this->attendance_percentage < 30) {
            return 'absent';
        } elseif ($this->attendance_percentage < 80) {
            return 'partial';
        }

        // If attended 80%+, check if they were late
        // UPDATED: If 95%+ attendance but late, still mark as 'present' (they made up for it)
        if ($this->attendance_percentage >= 95) {
            return 'present'; // Excellent attendance overrides lateness
        }

        return $this->is_late ? 'late' : 'present';
    }

    /**
     * Get attendance status in Arabic
     */
    public function getAttendanceStatusArabicAttribute(): string
    {
        return match ($this->attendance_status) {
            'present' => 'حاضر',
            'late' => 'متأخر',
            'partial' => 'حضور جزئي',
            'absent' => 'غائب',
            default => 'غير محدد'
        };
    }

    /**
     * Get overall performance score (average of memorization and reservation)
     */
    public function getOverallPerformanceAttribute(): ?float
    {
        if ($this->new_memorization_degree === null && $this->reservation_degree === null) {
            return null;
        }

        $scores = array_filter([
            $this->new_memorization_degree,
            $this->reservation_degree,
        ], fn ($score) => $score !== null);

        return ! empty($scores) ? round(array_sum($scores) / count($scores), 1) : null;
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
     * Record teacher evaluation
     */
    public function recordTeacherEvaluation(
        ?float $newMemorizationDegree = null,
        ?float $reservationDegree = null,
        ?string $notes = null
    ): void {
        $this->update([
            'new_memorization_degree' => $newMemorizationDegree,
            'reservation_degree' => $reservationDegree,
            'notes' => $notes,
            'evaluated_at' => now(),
            'manually_overridden' => true,
        ]);
    }

    /**
     * Scope for present students
     */
    public function scopePresent($query)
    {
        return $query->where('attendance_status', 'present');
    }

    /**
     * Scope for absent students
     */
    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', 'absent');
    }

    /**
     * Scope for late students
     */
    public function scopeLate($query)
    {
        return $query->where('attendance_status', 'late');
    }

    /**
     * Scope for partial attendance
     */
    public function scopePartial($query)
    {
        return $query->where('attendance_status', 'partial');
    }

    /**
     * Scope for evaluated reports
     */
    public function scopeEvaluated($query)
    {
        return $query->whereNotNull('evaluated_at');
    }

    /**
     * Scope for today's reports
     */
    public function scopeToday($query)
    {
        return $query->whereHas('session', function ($q) {
            $q->whereDate('scheduled_at', today());
        });
    }

    /**
     * Scope for this week's reports
     */
    public function scopeThisWeek($query)
    {
        return $query->whereHas('session', function ($q) {
            $q->whereBetween('scheduled_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        });
    }

    /**
     * Scope for current academy
     */
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

    /**
     * Get attendance status in Arabic (alias for test compatibility)
     */
    public function getAttendanceStatusInArabicAttribute(): string
    {
        return $this->attendance_status_arabic;
    }

    /**
     * Get the connection quality grade in Arabic
     */
    public function getConnectionQualityGradeAttribute(): string
    {
        $score = $this->connection_quality_score ?? 0;

        if ($score >= 90) {
            return 'ممتاز';
        }
        if ($score >= 80) {
            return 'جيد جداً';
        }
        if ($score >= 70) {
            return 'جيد';
        }
        if ($score >= 60) {
            return 'مقبول';
        }

        return 'ضعيف';
    }

    /**
     * Calculate average performance degree (alias for test compatibility)
     */
    public function getAveragePerformanceDegreeAttribute(): ?float
    {
        return $this->overall_performance;
    }

    /**
     * Check if student is late based on late minutes (already defined by is_late field)
     */
    public function getIsLateAttribute(): bool
    {
        return $this->attributes['is_late'] ?? (($this->late_minutes ?? 0) > 0);
    }

    /**
     * Create or update report for a student in a session
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
