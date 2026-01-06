<?php

namespace App\DTOs\Reports;

/**
 * Data Transfer Object for Attendance Statistics
 *
 * Represents attendance metrics for a student or group of students
 * across multiple sessions.
 */
class AttendanceDTO
{
    public function __construct(
        public readonly int $totalSessions,
        public readonly int $attended,
        public readonly int $absent,
        public readonly int $late,
        public readonly float $attendanceRate,
        public readonly int $averageDurationMinutes = 0,
    ) {}

    /**
     * Create DTO from array data
     *
     * @param  array  $data  Attendance data array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            totalSessions: $data['total_sessions'] ?? 0,
            attended: $data['attended'] ?? 0,
            absent: $data['absent'] ?? 0,
            late: $data['late'] ?? 0,
            attendanceRate: $data['attendance_rate'] ?? 0.0,
            averageDurationMinutes: $data['average_duration_minutes'] ?? 0,
        );
    }

    /**
     * Get color class based on attendance rate
     *
     * @return string Color class (green, yellow, red)
     */
    public function getColorClass(): string
    {
        return match (true) {
            $this->attendanceRate >= 80 => 'green',
            $this->attendanceRate >= 60 => 'yellow',
            default => 'red'
        };
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'total_sessions' => $this->totalSessions,
            'attended' => $this->attended,
            'absent' => $this->absent,
            'late' => $this->late,
            'attendance_rate' => $this->attendanceRate,
            'average_duration_minutes' => $this->averageDurationMinutes,
            'color_class' => $this->getColorClass(),
        ];
    }
}
