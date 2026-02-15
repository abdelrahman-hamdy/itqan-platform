<?php

namespace App\Enums;

/**
 * Performance Level Enum
 *
 * Represents the performance level based on evaluation scores (0-10 scale).
 * Used by BaseSessionReport for teacher evaluation grading.
 *
 * @see \App\Models\BaseSessionReport
 */
enum PerformanceLevel: string
{
    case EXCELLENT = 'excellent';
    case VERY_GOOD = 'very_good';
    case GOOD = 'good';
    case ACCEPTABLE = 'acceptable';
    case NEEDS_IMPROVEMENT = 'needs_improvement';

    /**
     * Get localized label for the performance level
     */
    public function label(): string
    {
        return __('enums.performance_level.'.$this->value);
    }

    /**
     * Get Filament color string for the performance level
     */
    public function color(): string
    {
        return match ($this) {
            self::EXCELLENT => 'success',
            self::VERY_GOOD => 'info',
            self::GOOD => 'primary',
            self::ACCEPTABLE => 'warning',
            self::NEEDS_IMPROVEMENT => 'danger',
        };
    }

    /**
     * Determine performance level from a numeric score (0-10 scale)
     */
    public static function fromScore(?float $score): ?self
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 9 => self::EXCELLENT,
            $score >= 8 => self::VERY_GOOD,
            $score >= 7 => self::GOOD,
            $score >= 6 => self::ACCEPTABLE,
            default => self::NEEDS_IMPROVEMENT,
        };
    }

    /**
     * Get all performance levels as options for dropdowns
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->label(),
        ])->toArray();
    }
}
