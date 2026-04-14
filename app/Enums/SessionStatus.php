<?php

namespace App\Enums;

/**
 * Session Status Enum
 *
 * Defines the lifecycle states for all session types:
 * - QuranSession (individual and group circles)
 * - AcademicSession (private tutoring lessons)
 * - InteractiveCourseSession (live course sessions)
 *
 * Sessions transition through states from scheduling to completion.
 * Financial impact (subscription counting, teacher earnings) is controlled
 * by independent counting flags, not by session status.
 *
 * @see \App\Models\QuranSession
 * @see \App\Models\AcademicSession
 * @see \App\Models\InteractiveCourseSession
 */
enum SessionStatus: string
{
    case UNSCHEDULED = 'unscheduled';      // Created but not scheduled
    case SCHEDULED = 'scheduled';          // Teacher has set date/time
    case READY = 'ready';                  // Meeting created, ready to start
    case ONGOING = 'ongoing';              // Currently happening
    case COMPLETED = 'completed';          // Time passed / session finished
    case CANCELLED = 'cancelled';          // Cancelled by admin (only admin can cancel)
    case SUSPENDED = 'suspended';          // Held due to subscription expiry/pause (recoverable)

    /**
     * Get the localized label for the status
     */
    public function label(): string
    {
        return __('enums.session_status.'.$this->value);
    }

    /**
     * Get the icon for the status
     */
    public function icon(): string
    {
        return match ($this) {
            self::UNSCHEDULED => 'ri-draft-line',
            self::SCHEDULED => 'ri-calendar-line',
            self::READY => 'ri-video-line',
            self::ONGOING => 'ri-live-line',
            self::COMPLETED => 'ri-check-circle-line',
            self::CANCELLED => 'ri-close-circle-line',
            self::SUSPENDED => 'ri-pause-circle-line',
        };
    }

    /**
     * Get the Filament color class for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::UNSCHEDULED => 'gray',
            self::SCHEDULED => 'info',
            self::READY => 'success',
            self::ONGOING => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::SUSPENDED => 'warning',
        };
    }

    /**
     * Get the hex color for calendar display
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::UNSCHEDULED => '#6B7280',  // gray-500
            self::SCHEDULED => '#3B82F6',    // blue-500
            self::READY => '#8B5CF6',        // violet-500
            self::ONGOING => '#06B6D4',      // cyan-500
            self::COMPLETED => '#22c55e',    // green-500
            self::CANCELLED => '#ef4444',    // red-500
            self::SUSPENDED => '#f97316',    // orange-500
        };
    }

    /**
     * Check if session can be started
     */
    public function canStart(): bool
    {
        return $this === self::SCHEDULED || $this === self::READY;
    }

    /**
     * Check if session can be completed.
     * Only READY and ONGOING sessions can transition to COMPLETED.
     * SCHEDULED sessions must go through READY first.
     */
    public function canComplete(): bool
    {
        return $this === self::READY || $this === self::ONGOING;
    }

    /**
     * Check if session can be cancelled (admin only)
     */
    public function canCancel(): bool
    {
        return $this === self::SCHEDULED || $this === self::READY || $this === self::ONGOING;
    }

    /**
     * Check if session can be rescheduled
     */
    public function canReschedule(): bool
    {
        return $this === self::SCHEDULED || $this === self::READY;
    }

    /**
     * Check if session is currently active/ongoing
     */
    public function isActive(): bool
    {
        return $this === self::ONGOING;
    }

    /**
     * Check if session is in a final state
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::SUSPENDED,
        ]);
    }

    /**
     * Get valid statuses for individual circles
     */
    public static function individualCircleStatuses(): array
    {
        return [
            self::UNSCHEDULED,
            self::SCHEDULED,
            self::READY,
            self::ONGOING,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }

    /**
     * Get valid statuses for group circles
     */
    public static function groupCircleStatuses(): array
    {
        return [
            self::UNSCHEDULED,
            self::SCHEDULED,
            self::READY,
            self::ONGOING,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }

    /**
     * Statuses for sessions that are in-progress or about to start.
     */
    public static function activeStatuses(): array
    {
        return [self::SCHEDULED, self::READY, self::ONGOING];
    }

    /**
     * Statuses for sessions awaiting their scheduled time.
     */
    public static function upcomingStatuses(): array
    {
        return [self::SCHEDULED, self::READY];
    }

    /**
     * Statuses for non-cancelled sessions.
     */
    public static function nonCancelledStatuses(): array
    {
        return [self::SCHEDULED, self::ONGOING, self::COMPLETED];
    }

    /**
     * Statuses for sessions that have ended.
     */
    public static function finishedStatuses(): array
    {
        return [self::COMPLETED, self::CANCELLED];
    }

    /**
     * Statuses for sessions that completed (attended or not — check attendance separately).
     */
    public static function resolvedStatuses(): array
    {
        return [self::COMPLETED];
    }

    /**
     * Statuses for sessions that were missed/cancelled.
     */
    public static function missedStatuses(): array
    {
        return [self::CANCELLED];
    }

    /**
     * Check if this status counts towards subscription usage.
     * Convenience method so callers can safely call $session->status->countsTowardsSubscription()
     * without hitting undefined method errors in queued contexts.
     */
    public function countsTowardsSubscription(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if a session with this status can be forgiven (excused absence).
     * Only completed sessions can be forgiven since they represent actual attended time.
     */
    public function canForgive(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($status) => $status->label(), self::cases())
        );
    }

    /**
     * Get status options for teachers (limited — teachers cannot cancel)
     */
    public static function teacherOptions(): array
    {
        return [
            self::COMPLETED->value => self::COMPLETED->label(),
        ];
    }

    /**
     * Get color options for badge columns (color => value)
     */
    public static function colorOptions(): array
    {
        $colors = [];
        foreach (self::cases() as $status) {
            $colors[$status->color()] = $status->value;
        }

        return $colors;
    }
}
