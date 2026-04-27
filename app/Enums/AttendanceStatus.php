<?php

namespace App\Enums;

/**
 * Attendance Status Enum
 *
 * Current 3-state model (new code writes these only):
 * - ATTENDED: duration >= full_attendance_percent
 * - PARTIALLY_ATTENDED: duration >= partial_attendance_percent
 * - ABSENT: duration < partial_attendance_percent or no join
 *
 * Deprecated values kept for historical rows (will be migrated in Phase B):
 * - LATE: pre-refactor "arrived after grace period"
 * - LEFT: pre-refactor "left before session ended"
 *
 * @see \App\Models\MeetingAttendance
 */
enum AttendanceStatus: string
{
    case ATTENDED = 'attended';
    case PARTIALLY_ATTENDED = 'partially_attended';
    case ABSENT = 'absent';

    /** @deprecated Legacy value — new code must not write this. */
    case LATE = 'late';

    /** @deprecated Legacy value — new code must not write this. */
    case LEFT = 'left';

    /**
     * Currently-writable values for new data (excludes deprecated cases).
     * Use this for Select dropdowns in admin/teacher manual-entry forms.
     *
     * @return array<string, string>
     */
    public static function activeOptions(): array
    {
        return [
            self::ATTENDED->value => self::ATTENDED->label(),
            self::PARTIALLY_ATTENDED->value => self::PARTIALLY_ATTENDED->label(),
            self::ABSENT->value => self::ABSENT->label(),
        ];
    }

    /**
     * True for the "partial" tier: new PARTIALLY_ATTENDED and legacy LATE/LEFT.
     * Used anywhere UI/stats want to bucket these together.
     */
    public function isPartialTier(): bool
    {
        return in_array($this, [
            self::PARTIALLY_ATTENDED,
            self::LATE,
            self::LEFT,
        ], true);
    }

    /**
     * Status values that count as "present" for attendance-rate calculations.
     * Includes the full tier, partial tier, and legacy LATE/LEFT rows.
     *
     * @return list<string>
     */
    public static function presentValues(): array
    {
        return [
            self::ATTENDED->value,
            self::PARTIALLY_ATTENDED->value,
            self::LATE->value,
            self::LEFT->value,
        ];
    }

    /**
     * Status values in the "partial" tier (PARTIALLY_ATTENDED + legacy LATE/LEFT).
     *
     * @return list<string>
     */
    public static function partialValues(): array
    {
        return [
            self::PARTIALLY_ATTENDED->value,
            self::LATE->value,
            self::LEFT->value,
        ];
    }

    /**
     * Get localized label for the status
     */
    public function label(): string
    {
        return __('enums.attendance_status.'.$this->value);
    }

    /**
     * Get badge color class for the status
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::ATTENDED => 'bg-green-100 text-green-800',
            self::LATE => 'bg-yellow-100 text-yellow-800',
            self::LEFT => 'bg-orange-100 text-orange-800',
            self::ABSENT => 'bg-red-100 text-red-800',
            self::PARTIALLY_ATTENDED => 'bg-amber-100 text-amber-800',
        };
    }

    /**
     * Get icon for the status
     */
    public function icon(): string
    {
        return match ($this) {
            self::ATTENDED => 'ri-check-line',
            self::LATE => 'ri-time-line',
            self::LEFT => 'ri-logout-box-line',
            self::ABSENT => 'ri-close-line',
            self::PARTIALLY_ATTENDED => 'ri-timer-line',
        };
    }

    /**
     * Get Filament color string for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::ATTENDED => 'success',
            self::PARTIALLY_ATTENDED => 'warning',
            self::ABSENT => 'danger',
            self::LATE => 'warning',
            self::LEFT => 'info',
        };
    }

    /**
     * Get color options array for Filament TextColumn badge.
     *
     * @return array<string, string>
     */
    public static function colorOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->color() => $case->value,
        ])->toArray();
    }

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Status values that may be written by new code.
     * Excludes the deprecated LATE / LEFT cases (kept readable for legacy rows).
     *
     * @return list<string>
     */
    public static function writableValues(): array
    {
        return [
            self::ATTENDED->value,
            self::PARTIALLY_ATTENDED->value,
            self::ABSENT->value,
        ];
    }

    /**
     * Get all status options for dropdown
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->label(),
        ])->toArray();
    }
}
