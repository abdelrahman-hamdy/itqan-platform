<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Base Session Attendance Model
 *
 * Abstract base class for all session attendance models (Quran, Academic, Interactive)
 * Contains shared attendance tracking logic, auto-tracking from LiveKit meetings,
 * and manual override capabilities.
 *
 * This follows the same pattern as BaseSession (from Phase 5) to eliminate code duplication.
 */
abstract class BaseSessionAttendance extends Model
{
    use HasFactory;

    /**
     * Base fillable fields - child classes merge these via constructor
     * This pattern prevents duplication of ~16 fields in each child class.
     */
    protected static array $baseFillable = [
        'session_id',
        'student_id',
        'attendance_status',
        'join_time',
        'leave_time',
        'auto_join_time',
        'auto_leave_time',
        'auto_duration_minutes',
        'auto_tracked',
        'manually_overridden',
        'overridden_by',
        'overridden_at',
        'override_reason',
        'meeting_events',
        'participation_score',
        'notes',
    ];

    /**
     * Base casts - child classes merge via getCasts() override
     * IMPORTANT: Child classes should NOT define protected $casts as it would override this.
     * Instead, they should override getCasts() and call parent::getCasts().
     */
    protected static array $baseCasts = [
        'join_time' => 'datetime',
        'leave_time' => 'datetime',
        'auto_join_time' => 'datetime',
        'auto_leave_time' => 'datetime',
        'overridden_at' => 'datetime',
        'auto_tracked' => 'boolean',
        'manually_overridden' => 'boolean',
        'meeting_events' => 'array',
        'participation_score' => 'decimal:1',
        'auto_duration_minutes' => 'integer',
    ];

    /**
     * Get the casts array - child classes should override this and merge with parent
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), static::$baseCasts);
    }

    /**
     * Default attributes
     */
    protected $attributes = [
        'attendance_status' => 'absent',
    ];

    // ========================================
    // Abstract Methods (Must be implemented by child classes)
    // ========================================

    /**
     * Get the session this attendance belongs to
     * Each child class must implement this with their specific session type
     */
    abstract public function session(): BelongsTo;

    /**
     * Get session-specific fields for attendance details
     * Used by getAttendanceDetailsAttribute()
     */
    abstract protected function getSessionSpecificDetails(): array;

    /**
     * Get the late threshold in minutes for this session type
     * Can be overridden by child classes for different session types
     */
    protected function getLateThresholdMinutes(): int
    {
        return 10; // Default 10 minutes
    }

    // ========================================
    // Shared Relationships
    // ========================================

    /**
     * Get the student who attended
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the teacher who overrode the attendance
     */
    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
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

    public function scopeToday($query)
    {
        return $query->whereHas('session', function ($q) {
            $q->whereDate('scheduled_at', now()->toDateString());
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

    public function scopeThisMonth($query)
    {
        return $query->whereHas('session', function ($q) {
            $q->whereMonth('scheduled_at', now()->month)
                ->whereYear('scheduled_at', now()->year);
        });
    }

    public function scopeAutoTracked($query)
    {
        return $query->where('auto_tracked', true);
    }

    public function scopeManuallyOverridden($query)
    {
        return $query->where('manually_overridden', true);
    }

    public function scopeNotManuallyOverridden($query)
    {
        return $query->where('manually_overridden', false);
    }

    // ========================================
    // Shared Attributes
    // ========================================

    /**
     * Get attendance status in Arabic
     */
    public function getAttendanceStatusInArabicAttribute(): string
    {
        return match ($this->attendance_status) {
            'attended' => 'حاضر',
            'absent' => 'غائب',
            'late' => 'متأخر',
            'leaved' => 'غادر مبكراً',
            default => 'غير معروفة'
        };
    }

    /**
     * Get attendance duration in minutes
     */
    public function getAttendanceDurationMinutesAttribute(): int
    {
        if (! $this->join_time || ! $this->leave_time) {
            return 0;
        }

        return $this->join_time->diffInMinutes($this->leave_time);
    }

    /**
     * Check if student attended full session
     */
    public function getAttendedFullSessionAttribute(): bool
    {
        if ($this->attendance_status !== 'attended') {
            return false;
        }

        $sessionDuration = $this->session->duration_minutes;
        $attendedDuration = $this->attendance_duration_minutes;

        // Consider full attendance if attended at least 80% of session
        return $attendedDuration >= ($sessionDuration * 0.8);
    }

    /**
     * Get auto-tracked attendance duration in minutes
     */
    public function getAutoAttendanceDurationMinutesAttribute(): int
    {
        if (! $this->auto_join_time || ! $this->auto_leave_time) {
            return $this->auto_duration_minutes ?? 0;
        }

        return $this->auto_join_time->diffInMinutes($this->auto_leave_time);
    }

    /**
     * Check if attendance was auto-tracked
     */
    public function getIsAutoTrackedAttribute(): bool
    {
        return $this->auto_tracked && ! empty($this->meeting_events);
    }

    /**
     * Get last meeting event
     */
    public function getLastMeetingEventAttribute(): ?array
    {
        if (! $this->meeting_events || empty($this->meeting_events)) {
            return null;
        }

        return end($this->meeting_events);
    }

    /**
     * Get performance rating
     */
    public function getPerformanceRatingAttribute(): string
    {
        if (! $this->participation_score) {
            return 'غير محدد';
        }

        return match (true) {
            $this->participation_score >= 9 => 'ممتاز',
            $this->participation_score >= 7 => 'جيد جداً',
            $this->participation_score >= 5 => 'جيد',
            $this->participation_score >= 3 => 'مقبول',
            default => 'ضعيف'
        };
    }

    /**
     * Get attendance details as array
     * Combines shared fields with session-specific details
     */
    public function getAttendanceDetailsAttribute(): array
    {
        $sharedDetails = [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student_name' => $this->student->full_name ?? 'غير محدد',
            'session_id' => $this->session_id,
            'session_title' => $this->session->title ?? 'غير محدد',
            'attendance_status' => $this->attendance_status,
            'attendance_status_in_arabic' => $this->attendance_status_in_arabic,
            'join_time' => $this->join_time?->format('Y-m-d H:i:s'),
            'leave_time' => $this->leave_time?->format('Y-m-d H:i:s'),
            'attendance_duration_minutes' => $this->attendance_duration_minutes,
            'participation_score' => $this->participation_score,
            'performance_rating' => $this->performance_rating,
            'attended_full_session' => $this->attended_full_session,
            'notes' => $this->notes,
        ];

        // Merge with session-specific details from child class
        return array_merge($sharedDetails, $this->getSessionSpecificDetails());
    }

    // ========================================
    // Shared Methods
    // ========================================

    /**
     * Record student joining the session
     */
    public function recordJoin(): bool
    {
        if ($this->attendance_status === 'attended' && $this->join_time) {
            return false; // Already joined
        }

        $now = now();
        $sessionStartTime = $this->session->scheduled_at;
        $lateThreshold = $this->getLateThresholdMinutes();
        $lateTime = $sessionStartTime->copy()->addMinutes($lateThreshold);

        $attendanceStatus = $now->isAfter($lateTime) ? 'late' : 'attended';

        $this->update([
            'attendance_status' => $attendanceStatus,
            'join_time' => $now,
        ]);

        return true;
    }

    /**
     * Record student leaving the session
     */
    public function recordLeave(): bool
    {
        if (! $this->join_time) {
            return false; // Never joined
        }

        $this->update([
            'leave_time' => now(),
        ]);

        return true;
    }

    /**
     * Record participation score
     */
    public function recordParticipationScore(float $score): bool
    {
        if ($score < 0 || $score > 10) {
            return false;
        }

        $this->update([
            'participation_score' => $score,
        ]);

        return true;
    }

    /**
     * Add teacher notes
     */
    public function addTeacherNotes(string $notes): bool
    {
        $currentNotes = $this->notes ?: '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $newNotes = "[{$timestamp}] {$notes}\n".$currentNotes;

        $this->update(['notes' => $newNotes]);

        return true;
    }

    /**
     * Calculate attendance status from meeting events
     */
    public function calculateAttendanceFromMeetingEvents(): string
    {
        if (! $this->meeting_events || empty($this->meeting_events)) {
            return 'absent';
        }

        $joinTime = $this->auto_join_time;
        $leaveTime = $this->auto_leave_time;
        $sessionStart = $this->session->scheduled_at;

        if (! $joinTime) {
            return 'absent';
        }

        // Check if late
        $lateThreshold = $this->getLateThresholdMinutes();
        $minutesLate = $joinTime->diffInMinutes($sessionStart, false);

        if ($minutesLate > $lateThreshold) {
            return 'late';
        }

        // Check if left early
        $expectedEnd = $sessionStart->copy()->addMinutes($this->session->duration_minutes);
        if ($leaveTime && $leaveTime->isBefore($expectedEnd->subMinutes(10))) {
            return 'leaved';
        }

        return 'attended';
    }

    /**
     * Record meeting event (join/leave)
     */
    public function recordMeetingEvent(string $eventType, array $eventData = []): void
    {
        $events = $this->meeting_events ?? [];
        $events[] = [
            'type' => $eventType,
            'timestamp' => now()->toISOString(),
            'data' => $eventData,
        ];

        $this->meeting_events = $events;
        $this->auto_tracked = true;

        // Update auto join/leave times
        if ($eventType === 'joined' && ! $this->auto_join_time) {
            $this->auto_join_time = now();
        }

        if ($eventType === 'left') {
            $this->auto_leave_time = now();
            $this->auto_duration_minutes = $this->auto_join_time
                ? $this->auto_join_time->diffInMinutes(now())
                : 0;
        }

        // Calculate attendance status if not manually overridden
        if (! $this->manually_overridden) {
            $this->attendance_status = $this->calculateAttendanceFromMeetingEvents();
        }

        $this->save();
    }

    /**
     * Manually override attendance
     */
    public function manuallyOverride(array $overrideData, ?string $reason = null, $teacherId = null): self
    {
        $this->update($overrideData);
        $this->manually_overridden = true;
        $this->overridden_by = $teacherId ?? auth()->id();
        $this->overridden_at = now();
        $this->override_reason = $reason;
        $this->save();

        return $this;
    }

    /**
     * Revert to auto-tracking
     */
    public function revertToAutoTracking(): self
    {
        if ($this->auto_tracked) {
            $this->attendance_status = $this->calculateAttendanceFromMeetingEvents();
        }

        $this->manually_overridden = false;
        $this->overridden_by = null;
        $this->overridden_at = null;
        $this->override_reason = null;
        $this->save();

        return $this;
    }

    /**
     * Check if student can join session
     */
    public function canJoinSession(): bool
    {
        return $this->session->status === 'ongoing' &&
               $this->attendance_status === 'absent';
    }

    /**
     * Check if student can leave session
     */
    public function canLeaveSession(): bool
    {
        return $this->attendance_status === 'attended' &&
               $this->join_time &&
               ! $this->leave_time;
    }
}
