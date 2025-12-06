<?php

namespace App\Enums;

enum InteractiveCourseStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Get Arabic label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::PUBLISHED => 'منشور',
            self::ACTIVE => 'نشط',
            self::COMPLETED => 'مكتمل',
            self::CANCELLED => 'ملغي',
        };
    }

    /**
     * Get English label for the status
     */
    public function labelEn(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get icon for the status
     */
    public function icon(): string
    {
        return match($this) {
            self::DRAFT => 'ri-draft-line',
            self::PUBLISHED => 'ri-check-line',
            self::ACTIVE => 'ri-play-circle-line',
            self::COMPLETED => 'ri-checkbox-circle-line',
            self::CANCELLED => 'ri-close-circle-line',
        };
    }

    /**
     * Get color for the status
     */
    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
            self::ACTIVE => 'blue',
            self::COMPLETED => 'purple',
            self::CANCELLED => 'red',
        };
    }

    /**
     * Check if status allows enrollment
     */
    public function allowsEnrollment(): bool
    {
        return in_array($this, [self::PUBLISHED, self::ACTIVE]);
    }

    /**
     * Check if status is visible to public
     */
    public function isVisibleToPublic(): bool
    {
        return in_array($this, [self::PUBLISHED, self::ACTIVE]);
    }
}
