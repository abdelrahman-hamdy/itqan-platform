<?php

namespace App\Enums;

/**
 * Homework Status Enum
 *
 * Tracks the publication status of homework assignments.
 * Used by homework models across all session types.
 *
 * States:
 * - DRAFT: Homework created but not visible to students
 * - PUBLISHED: Homework visible and assignable to students
 * - IN_PROGRESS: Homework currently being worked on by students
 * - ARCHIVED: Homework completed and archived
 *
 * @see \App\Models\AcademicHomework
 * @see \App\Models\InteractiveCourseHomework
 */
enum HomeworkStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case IN_PROGRESS = 'in_progress';
    case ARCHIVED = 'archived';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.homework_status.'.$this->value);
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

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if homework is active (published and in progress)
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PUBLISHED, self::IN_PROGRESS]);
    }

    /**
     * Check if homework is closed/archived
     */
    public function isClosed(): bool
    {
        return $this === self::ARCHIVED;
    }

    /**
     * Get badge CSS classes for display
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-gray-100 text-gray-800',
            self::PUBLISHED => 'bg-green-100 text-green-800',
            self::IN_PROGRESS => 'bg-blue-100 text-blue-800',
            self::ARCHIVED => 'bg-red-100 text-red-800',
        };
    }
}
