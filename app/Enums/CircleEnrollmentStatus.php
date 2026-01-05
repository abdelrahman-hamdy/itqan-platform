<?php

namespace App\Enums;

/**
 * Circle Enrollment Status Enum
 *
 * Defines the enrollment availability states for Quran circles.
 * This is different from the circle's operational status (CircleStatus).
 *
 * States:
 * - OPEN: Circle is accepting new enrollments
 * - CLOSED: Circle is not accepting new enrollments
 * - FULL: Circle has reached maximum capacity
 * - WAITLIST: Circle is full but accepting waitlist entries
 *
 * @see \App\Models\QuranCircle
 */
enum CircleEnrollmentStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case FULL = 'full';
    case WAITLIST = 'waitlist';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.circle_enrollment_status.' . $this->value);
    }

    /**
     * Get Arabic label directly
     */
    public function arabicLabel(): string
    {
        return match ($this) {
            self::OPEN => 'مفتوح للتسجيل',
            self::CLOSED => 'مغلق',
            self::FULL => 'مكتمل العدد',
            self::WAITLIST => 'قائمة انتظار',
        };
    }

    /**
     * Get the icon for the status
     */
    public function icon(): string
    {
        return match ($this) {
            self::OPEN => 'ri-door-open-line',
            self::CLOSED => 'ri-door-closed-line',
            self::FULL => 'ri-user-forbid-line',
            self::WAITLIST => 'ri-time-line',
        };
    }

    /**
     * Get the Filament color class
     */
    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'success',
            self::CLOSED => 'gray',
            self::FULL => 'warning',
            self::WAITLIST => 'info',
        };
    }

    /**
     * Get hex color for display
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::OPEN => '#22C55E',     // green-500
            self::CLOSED => '#6B7280',   // gray-500
            self::FULL => '#F59E0B',     // amber-500
            self::WAITLIST => '#3B82F6', // blue-500
        };
    }

    /**
     * Get Tailwind badge classes
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::OPEN => 'bg-green-100 text-green-800',
            self::CLOSED => 'bg-gray-100 text-gray-800',
            self::FULL => 'bg-amber-100 text-amber-800',
            self::WAITLIST => 'bg-blue-100 text-blue-800',
        };
    }

    /**
     * Check if new enrollments are accepted
     */
    public function acceptsEnrollment(): bool
    {
        return $this === self::OPEN;
    }

    /**
     * Check if waitlist entries are accepted
     */
    public function acceptsWaitlist(): bool
    {
        return in_array($this, [self::OPEN, self::WAITLIST]);
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
}
