<?php

namespace App\Enums;

/**
 * EnrollmentStatus Enum
 *
 * Simplified status for course-based enrollments.
 * Used for Interactive Courses and Recorded Courses.
 *
 * Lifecycle:
 * - PENDING → ENROLLED (payment received)
 * - ENROLLED → COMPLETED (course finished)
 * - ENROLLED → CANCELLED (user cancels)
 *
 * @see \App\Models\InteractiveCourseEnrollment
 * @see \App\Models\CourseSubscription
 * @see \App\Models\QuranCircleEnrollment
 */
enum EnrollmentStatus: string
{
    case PENDING = 'pending';       // Awaiting payment
    case ENROLLED = 'enrolled';     // Actively enrolled
    case COMPLETED = 'completed';   // Course finished
    case CANCELLED = 'cancelled';   // Terminated

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.enrollment_status.'.$this->value);
    }

    /**
     * Get English label
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ENROLLED => 'Enrolled',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get badge color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ENROLLED => 'success',
            self::COMPLETED => 'primary',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * Get icon for display (Heroicons)
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::ENROLLED => 'heroicon-o-academic-cap',
            self::COMPLETED => 'heroicon-o-trophy',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }

    /**
     * Get Tailwind badge classes
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::ENROLLED => 'bg-green-100 text-green-800',
            self::COMPLETED => 'bg-purple-100 text-purple-800',
            self::CANCELLED => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Check if enrollment is currently active (can access content)
     */
    public function isActive(): bool
    {
        return in_array($this, [self::ENROLLED, self::COMPLETED]);
    }

    /**
     * Check if content can be accessed
     */
    public function canAccess(): bool
    {
        return in_array($this, [self::ENROLLED, self::COMPLETED]);
    }

    /**
     * Check if enrollment can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::ENROLLED]);
    }

    /**
     * Check if enrollment is terminal (no further changes)
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * Get valid next statuses from current status
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::ENROLLED, self::CANCELLED],
            self::ENROLLED => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED => [], // Terminal
            self::CANCELLED => [], // Terminal
        };
    }

    /**
     * Get all statuses as options for select inputs
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $status) => [$status->value => $status->label()]
        )->all();
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
