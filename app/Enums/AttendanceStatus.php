<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case LATE = 'late';
    case PARTIAL = 'partial';
    case ABSENT = 'absent';

    /**
     * Get the Arabic label for the attendance status
     */
    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'حاضر',
            self::LATE => 'متأخر',
            self::PARTIAL => 'حضور جزئي',
            self::ABSENT => 'غائب',
        };
    }

    /**
     * Get the icon for the attendance status
     */
    public function icon(): string
    {
        return match ($this) {
            self::PRESENT => 'ri-check-circle-line',
            self::LATE => 'ri-time-line',
            self::PARTIAL => 'ri-pie-chart-line',
            self::ABSENT => 'ri-close-circle-line',
        };
    }

    /**
     * Get the color class for the attendance status
     */
    public function color(): string
    {
        return match ($this) {
            self::PRESENT => 'green',
            self::LATE => 'yellow',
            self::PARTIAL => 'orange',
            self::ABSENT => 'red',
        };
    }

    /**
     * Get the CSS background color class for the attendance status
     */
    public function bgColor(): string
    {
        return match ($this) {
            self::PRESENT => 'bg-green-100 text-green-800',
            self::LATE => 'bg-yellow-100 text-yellow-800',
            self::PARTIAL => 'bg-orange-100 text-orange-800',
            self::ABSENT => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Check if attendance status counts as present
     */
    public function isPresent(): bool
    {
        return $this === self::PRESENT || $this === self::LATE;
    }

    /**
     * Check if attendance status indicates absence
     */
    public function isAbsent(): bool
    {
        return $this === self::ABSENT;
    }

    /**
     * Check if attendance status is partial
     */
    public function isPartial(): bool
    {
        return $this === self::PARTIAL;
    }

    /**
     * Get all attendance status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get attendance status options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($status) => $status->label(), self::cases())
        );
    }

    /**
     * Create from string value with validation
     */
    public static function fromString(string $value): self
    {
        return match ($value) {
            'present' => self::PRESENT,
            'late' => self::LATE,
            'partial' => self::PARTIAL,
            'absent' => self::ABSENT,
            default => throw new \InvalidArgumentException("Invalid attendance status: {$value}"),
        };
    }

    /**
     * Get status based on attendance percentage and lateness
     */
    public static function fromPercentageAndLateness(float $percentage, bool $isLate = false): self
    {
        if ($percentage < 30) {
            return self::ABSENT;
        } elseif ($percentage < 80) {
            return self::PARTIAL;
        } else {
            return $isLate ? self::LATE : self::PRESENT;
        }
    }
}
