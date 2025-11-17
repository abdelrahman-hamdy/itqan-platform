<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case ATTENDED = 'attended';
    case LATE = 'late';
    case LEAVED = 'leaved';
    case ABSENT = 'absent';

    /**
     * Get Arabic label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::ATTENDED => 'حاضر',
            self::LATE => 'متأخر',
            self::LEAVED => 'غادر مبكراً',
            self::ABSENT => 'غائب',
        };
    }

    /**
     * Get badge color class for the status
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::ATTENDED => 'bg-green-100 text-green-800',
            self::LATE => 'bg-yellow-100 text-yellow-800',
            self::LEAVED => 'bg-orange-100 text-orange-800',
            self::ABSENT => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Get icon for the status
     */
    public function icon(): string
    {
        return match($this) {
            self::ATTENDED => 'ri-check-line',
            self::LATE => 'ri-time-line',
            self::LEAVED => 'ri-logout-box-line',
            self::ABSENT => 'ri-close-line',
        };
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
        return collect(self::cases())->mapWithKeys(fn($case) => [
            $case->value => $case->label()
        ])->toArray();
    }
}
