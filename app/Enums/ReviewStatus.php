<?php

namespace App\Enums;

/**
 * Review Status Enum
 *
 * Represents the approval status of reviews (course and teacher reviews).
 * Used for managing review moderation workflow.
 *
 * @see \App\Models\CourseReview
 * @see \App\Models\TeacherReview
 */
enum ReviewStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.review_status.'.$this->value);
    }

    /**
     * Get icon for the status
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::APPROVED => 'heroicon-o-check-circle',
            self::REJECTED => 'heroicon-o-x-circle',
        };
    }

    /**
     * Get color for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
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

    /**
     * Check if the status is approved
     */
    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Check if the status is pending
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if the status is rejected
     */
    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }
}
