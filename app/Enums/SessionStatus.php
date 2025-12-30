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
 * The ABSENT status is only applicable to individual sessions.
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
    case COMPLETED = 'completed';          // Finished successfully
    case CANCELLED = 'cancelled';          // Cancelled by teacher/admin
    case ABSENT = 'absent';                // Student didn't attend (individual only)

    /**
     * Get the localized label for the status
     */
    public function label(): string
    {
        return __('enums.session_status.' . $this->value);
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
            self::ABSENT => 'ri-user-x-line',
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
            self::ABSENT => 'warning',
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
            self::ABSENT => '#f59e0b',       // amber-500
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
     * Check if session can be completed
     */
    public function canComplete(): bool
    {
        return $this === self::SCHEDULED || $this === self::READY || $this === self::ONGOING;
    }

    /**
     * Check if session can be cancelled
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
     * Check if session is in a final state (completed, cancelled, or absent)
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::ABSENT,
        ]);
    }

    /**
     * Check if session counts towards subscription
     */
    public function countsTowardsSubscription(): bool
    {
        return match ($this) {
            self::COMPLETED, self::ABSENT => true,
            default => false,
        };
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
            self::ABSENT,
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
     * Get individual circle status options for teachers
     */
    public static function teacherIndividualOptions(): array
    {
        return [
            self::COMPLETED->value => self::COMPLETED->label(),
            self::CANCELLED->value => self::CANCELLED->label(),
            self::ABSENT->value => self::ABSENT->label(),
        ];
    }

    /**
     * Get group circle status options for teachers
     */
    public static function teacherGroupOptions(): array
    {
        return [
            self::COMPLETED->value => self::COMPLETED->label(),
            self::CANCELLED->value => self::CANCELLED->label(),
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
