<?php

namespace App\DTOs\Reports;

/**
 * Data Transfer Object for Student Report Table Row
 *
 * Represents a single student's row in aggregate report tables
 * (group circles, interactive courses, etc.)
 */
class StudentReportRowDTO
{
    public function __construct(
        public readonly int $studentId,
        public readonly string $studentName,
        public readonly ?string $enrollmentDate = null,
        public readonly float $attendanceRate = 0.0,
        public readonly float $performanceScore = 0.0,
        public readonly int $completedSessions = 0,
        public readonly ?string $detailUrl = null,
    ) {}

    /**
     * Create DTO from array data
     *
     * @param array $data Student row data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            studentId: $data['student_id'],
            studentName: $data['student_name'],
            enrollmentDate: $data['enrollment_date'] ?? null,
            attendanceRate: $data['attendance_rate'] ?? 0.0,
            performanceScore: $data['performance_score'] ?? 0.0,
            completedSessions: $data['completed_sessions'] ?? 0,
            detailUrl: $data['detail_url'] ?? null,
        );
    }

    /**
     * Get attendance color class
     *
     * @return string Color class (green, yellow, red)
     */
    public function getAttendanceColorClass(): string
    {
        return match(true) {
            $this->attendanceRate >= 80 => 'green',
            $this->attendanceRate >= 60 => 'yellow',
            default => 'red'
        };
    }

    /**
     * Get performance color class
     *
     * @return string Color class (green, blue, yellow, red)
     */
    public function getPerformanceColorClass(): string
    {
        return match(true) {
            $this->performanceScore >= 8 => 'green',
            $this->performanceScore >= 6 => 'blue',
            $this->performanceScore >= 4 => 'yellow',
            default => 'red'
        };
    }

    /**
     * Convert DTO to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'student_id' => $this->studentId,
            'student_name' => $this->studentName,
            'enrollment_date' => $this->enrollmentDate,
            'attendance_rate' => $this->attendanceRate,
            'performance_score' => $this->performanceScore,
            'completed_sessions' => $this->completedSessions,
            'detail_url' => $this->detailUrl,
            'attendance_color_class' => $this->getAttendanceColorClass(),
            'performance_color_class' => $this->getPerformanceColorClass(),
        ];
    }
}
