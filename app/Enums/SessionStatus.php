<?php

namespace App\Enums;

enum SessionStatus: string
{
    case UNSCHEDULED = 'unscheduled';      // Created but not scheduled
    case SCHEDULED = 'scheduled';          // Teacher has set date/time
    case READY = 'ready';                  // Meeting created, ready to start
    case ONGOING = 'ongoing';              // Currently happening
    case COMPLETED = 'completed';          // Finished successfully
    case CANCELLED = 'cancelled';          // Cancelled by teacher/admin
    case ABSENT = 'absent';                // Student didn't attend (individual only)
    case MISSED = 'missed';                // System marked as missed due to time
    case RESCHEDULED = 'rescheduled';      // Moved to different time

    /**
     * Get the Arabic label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::UNSCHEDULED => 'غير مجدولة',
            self::SCHEDULED => 'مجدولة',
            self::READY => 'جاهزة للبدء',
            self::ONGOING => 'جارية الآن',
            self::COMPLETED => 'مكتملة',
            self::CANCELLED => 'ملغية',
            self::ABSENT => 'غياب الطالب',
            self::MISSED => 'فائتة',
            self::RESCHEDULED => 'مؤجلة',
        };
    }

    /**
     * Get the icon for the status
     */
    public function icon(): string
    {
        return match($this) {
            self::UNSCHEDULED => 'ri-draft-line',
            self::SCHEDULED => 'ri-calendar-line',
            self::READY => 'ri-video-line',
            self::ONGOING => 'ri-live-line',
            self::COMPLETED => 'ri-check-circle-line',
            self::CANCELLED => 'ri-close-circle-line',
            self::ABSENT => 'ri-user-x-line',
            self::MISSED => 'ri-time-line',
            self::RESCHEDULED => 'ri-calendar-schedule-line',
        };
    }

    /**
     * Get the color class for the status
     */
    public function color(): string
    {
        return match($this) {
            self::UNSCHEDULED => 'gray',
            self::SCHEDULED => 'blue',
            self::READY => 'green',
            self::ONGOING => 'orange',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
            self::ABSENT => 'red',
            self::MISSED => 'red',
            self::RESCHEDULED => 'purple',
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
        return $this === self::SCHEDULED || $this === self::READY;
    }

    /**
     * Check if session can be rescheduled
     */
    public function canReschedule(): bool
    {
        return $this === self::SCHEDULED || $this === self::READY;
    }

    /**
     * Check if session counts towards subscription
     */
    public function countsTowardsSubscription(): bool
    {
        return match($this) {
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
            self::MISSED,
            self::RESCHEDULED,
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
            self::MISSED,
            self::RESCHEDULED,
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
            array_map(fn($status) => $status->label(), self::cases())
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
}
