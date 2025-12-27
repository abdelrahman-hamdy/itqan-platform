<?php

namespace App\Enums;

enum HomeworkStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case IN_PROGRESS = 'in_progress';
    case ARCHIVED = 'archived';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('مسودة'),
            self::PUBLISHED => __('منشور'),
            self::IN_PROGRESS => __('قيد التقدم'),
            self::ARCHIVED => __('مؤرشف'),
        };
    }

    /**
     * Get badge color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'success',
            self::IN_PROGRESS => 'info',
            self::ARCHIVED => 'warning',
        };
    }

    /**
     * Get icon for display
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-pencil-square',
            self::PUBLISHED => 'heroicon-o-check-circle',
            self::IN_PROGRESS => 'heroicon-o-arrow-path',
            self::ARCHIVED => 'heroicon-o-archive-box',
        };
    }

    /**
     * Check if homework is visible to students
     */
    public function isVisibleToStudents(): bool
    {
        return in_array($this, [self::PUBLISHED, self::IN_PROGRESS]);
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
}
