<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Homework Submission Status Enum
 *
 * Simplified lifecycle: pending → submitted/late → graded
 *
 * States:
 * - PENDING: Not yet submitted
 * - SUBMITTED: Submitted on time
 * - LATE: Submitted after deadline
 * - GRADED: Teacher has graded
 */
enum HomeworkSubmissionStatus: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case SUBMITTED = 'submitted';
    case LATE = 'late';
    case GRADED = 'graded';

    /**
     * Get localized label (Filament HasLabel interface)
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'بانتظار التسليم',
            self::SUBMITTED => 'تم التسليم',
            self::LATE => 'متأخر',
            self::GRADED => 'تم التصحيح',
        };
    }

    /**
     * Alias for getLabel() for backward compatibility
     */
    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * Get the icon for the status (Filament HasIcon interface)
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-document',
            self::SUBMITTED => 'heroicon-o-paper-airplane',
            self::LATE => 'heroicon-o-clock',
            self::GRADED => 'heroicon-o-check-circle',
        };
    }

    /**
     * Alias for getIcon() for backward compatibility
     */
    public function icon(): string
    {
        return $this->getIcon();
    }

    /**
     * Get the Filament color class for the status (Filament HasColor interface)
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::SUBMITTED => 'info',
            self::LATE => 'danger',
            self::GRADED => 'success',
        };
    }

    /**
     * Alias for getColor() for backward compatibility
     */
    public function color(): string
    {
        return $this->getColor();
    }

    /**
     * Check if student can submit
     */
    public function canSubmit(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if submission is pending (not submitted yet)
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if submission has been submitted
     */
    public function isSubmitted(): bool
    {
        return in_array($this, [
            self::SUBMITTED,
            self::LATE,
            self::GRADED,
        ]);
    }

    /**
     * Check if submission is graded
     */
    public function isGraded(): bool
    {
        return $this === self::GRADED;
    }

    /**
     * Check if submission needs teacher review
     */
    public function needsReview(): bool
    {
        return in_array($this, [
            self::SUBMITTED,
            self::LATE,
        ]);
    }

    /**
     * Check if submission is late
     */
    public function isLate(): bool
    {
        return $this === self::LATE;
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
