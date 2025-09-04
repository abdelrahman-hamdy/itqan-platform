<?php

namespace App\Models;

use App\Traits\ScopedToAcademy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicSessionReport extends Model
{
    use HasFactory, ScopedToAcademy;

    protected $fillable = [
        'session_id',
        'student_id',
        'teacher_id',
        'academy_id',
        'academic_grade',
        'lesson_understanding_degree',
        'participation_degree',
        'homework_completion_degree',
        'notes',
        'homework_description',
        'homework_file',
        'homework_submitted_at',
        'homework_feedback',
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
        'academic_grade' => 'decimal:1',
        'lesson_understanding_degree' => 'decimal:1',
        'participation_degree' => 'decimal:1',
        'homework_completion_degree' => 'decimal:1',
        'meeting_enter_time' => 'datetime',
        'meeting_leave_time' => 'datetime',
        'homework_submitted_at' => 'datetime',
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
     * Relationship with AcademicSession
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
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
     * Create or update report for academic session
     */
    public static function createOrUpdateReport(
        int $sessionId,
        int $studentId,
        int $teacherId,
        int $academyId
    ): self {
        return static::updateOrCreate([
            'session_id' => $sessionId,
            'student_id' => $studentId,
        ], [
            'teacher_id' => $teacherId,
            'academy_id' => $academyId,
            'is_auto_calculated' => true,
            'evaluated_at' => now(),
        ]);
    }

    /**
     * Sync attendance data from MeetingAttendance record
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
                'is_auto_calculated' => $meetingAttendance->is_calculated,
            ]);

            // Recalculate lateness based on session timing
            $this->calculateLateness();
        }
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
        $subscription = $session->academicSubscription;
        $lateThreshold = 15; // Default 15 minutes grace period for academic sessions

        $sessionStartTime = $session->scheduled_at;
        $lateThresholdTime = $sessionStartTime->copy()->addMinutes($lateThreshold);

        $this->is_late = $this->meeting_enter_time->isAfter($lateThresholdTime);
        $this->late_minutes = $this->is_late
            ? $this->meeting_enter_time->diffInMinutes($sessionStartTime)
            : 0;
    }

    /**
     * Calculate real-time attendance status for academic sessions
     */
    private function calculateRealtimeAttendanceStatus(MeetingAttendance $meetingAttendance): string
    {
        if (! $meetingAttendance->first_join_time || ! $this->session) {
            return 'absent';
        }

        $session = $this->session;
        $graceMinutes = 15; // Default grace period for academic sessions
        $sessionDuration = $session->duration_minutes ?? 60;

        // Check if first join was within grace period
        $sessionStartTime = $session->scheduled_at;
        $graceThresholdTime = $sessionStartTime->copy()->addMinutes($graceMinutes);
        $joinedWithinGrace = $meetingAttendance->first_join_time->lte($graceThresholdTime);

        // Calculate current attendance percentage
        $currentDuration = $meetingAttendance->isCurrentlyInMeeting()
            ? $meetingAttendance->getCurrentSessionDuration()
            : $meetingAttendance->total_duration_minutes;

        $attendancePercentage = $sessionDuration > 0 ? ($currentDuration / $sessionDuration) * 100 : 0;

        // 100% attendance override - if student attended 100%+, always mark as present
        if ($attendancePercentage >= 100) {
            return 'present';
        }

        // If first join was after grace time
        if (! $joinedWithinGrace) {
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
     * Check if homework has been submitted
     */
    public function hasSubmittedHomework(): bool
    {
        return !empty($this->homework_file) || !empty($this->homework_description);
    }

    /**
     * Get overall session performance score (0-10)
     */
    public function getOverallPerformanceAttribute(): float
    {
        $scores = array_filter([
            $this->academic_grade,
            $this->lesson_understanding_degree,
            $this->participation_degree,
            $this->homework_completion_degree,
        ]);

        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0.0;
    }

    /**
     * Get attendance status in Arabic
     */
    public function getAttendanceStatusInArabicAttribute(): string
    {
        return match ($this->attendance_status) {
            'present' => 'حاضر',
            'late' => 'متأخر',
            'partial' => 'حضور جزئي',
            'absent' => 'غائب',
            default => $this->attendance_status,
        };
    }

    /**
     * Scopes
     */
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

    public function scopeEvaluated($query)
    {
        return $query->whereNotNull('evaluated_at');
    }

    public function scopeWithHomework($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('homework_file')
              ->orWhereNotNull('homework_description');
        });
    }
}