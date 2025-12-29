<?php

namespace App\Enums;

/**
 * Approval Status Enum
 *
 * Tracks the approval state of submissions requiring review.
 * Used by teacher applications, content submissions, and other approval workflows.
 *
 * States:
 * - PENDING: Awaiting review
 * - APPROVED: Approved and active
 * - REJECTED: Rejected with feedback
 *
 * @see \App\Models\QuranTeacherProfile
 * @see \App\Models\AcademicTeacherProfile
 */
enum ApprovalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.approval_status.' . $this->value);
    }

    /**
     * Get badge color for Filament
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
     * Get icon for display
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
