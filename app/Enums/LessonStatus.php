<?php

namespace App\Enums;

/**
 * Lesson Status Enum
 *
 * Tracks the lifecycle of individual lessons.
 *
 * States:
 * - PENDING: Lesson scheduled but not started
 * - ACTIVE: Lesson currently in progress
 * - COMPLETED: Lesson finished
 * - CANCELLED: Lesson cancelled
 *
 * @see \App\Models\Lesson
 */
enum LessonStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.lesson_status.' . $this->value);
    }

    /**
     * Get badge color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACTIVE => 'success',
            self::COMPLETED => 'info',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * Get icon for display
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::ACTIVE => 'heroicon-o-play',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
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
