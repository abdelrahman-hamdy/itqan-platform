<?php

namespace App\Enums;

/**
 * Attendance Status Enum
 *
 * Tracks participant attendance status for sessions.
 * Used by MeetingAttendance and session-specific attendance models.
 *
 * States:
 * - ATTENDED: Full participation in the session
 * - LATE: Arrived after session start but attended
 * - LEFT: Left before session ended (partial attendance)
 * - ABSENT: Did not attend at all
 *
 * @see \App\Models\MeetingAttendance
 * @see \App\Services\MeetingAttendanceService
 */
enum AttendanceStatus: string
{
    case ATTENDED = 'attended';                   // Full attendance
    case LATE = 'late';                           // Arrived late but attended
    case LEFT = 'left';                           // Left early (partial attendance)
    case ABSENT = 'absent';                       // Did not attend
    case PARTIALLY_ATTENDED = 'partially_attended'; // Partial attendance (teacher: 50-89% of session)

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
            self::LATE => 'warning',
            self::LEFT => 'primary',
            self::ABSENT => 'danger',
            self::PARTIALLY_ATTENDED => 'warning',
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
     * Get all status options for dropdown
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->label(),
        ])->toArray();
    }
}
