<?php

namespace App\DTOs\Reports;

/**
 * Data Transfer Object for Performance Statistics
 *
 * Represents performance metrics for a student across different
 * evaluation types (Quran memorization, academic homework, etc.)
 */
class PerformanceDTO
{
    public function __construct(
        public readonly float $averageOverall,
        public readonly ?float $averageMemorization = null,
        public readonly ?float $averageReservation = null,
        public readonly ?float $averageHomework = null,
        public readonly int $totalEvaluated = 0,
        public readonly string $type = 'quran', // quran, academic, interactive
    ) {}

    /**
     * Create DTO from Quran performance data
     *
     * @param  array  $data  Performance data array
     */
    public static function fromQuranData(array $data): self
    {
        return new self(
            averageOverall: $data['average_overall_performance'] ?? 0.0,
            averageMemorization: $data['average_memorization_degree'] ?? 0.0,
            averageReservation: $data['average_reservation_degree'] ?? 0.0,
            totalEvaluated: $data['sessions_evaluated'] ?? 0,
            type: 'quran'
        );
    }

    /**
     * Create DTO from Academic performance data
     *
     * @param  array  $data  Performance data array
     */
    public static function fromAcademicData(array $data): self
    {
        return new self(
            averageOverall: $data['average_overall_performance'] ?? 0.0,
            averageHomework: $data['average_homework_degree'] ?? 0.0,
            totalEvaluated: $data['sessions_evaluated'] ?? 0,
            type: 'academic'
        );
    }

    /**
     * Create DTO from Interactive course performance data
     *
     * @param  array  $data  Performance data array
     */
    public static function fromInteractiveData(array $data): self
    {
        return new self(
            averageOverall: $data['average_overall_performance'] ?? 0.0,
            averageHomework: $data['average_homework_degree'] ?? 0.0,
            totalEvaluated: $data['sessions_evaluated'] ?? 0,
            type: 'interactive'
        );
    }

    /**
     * Get Arabic rating label based on average overall performance
     *
     * @return string Rating label (ممتاز, جيد, مقبول, ضعيف)
     */
    public function getRatingLabel(): string
    {
        return match (true) {
            $this->averageOverall >= 8 => 'ممتاز',
            $this->averageOverall >= 6 => 'جيد',
            $this->averageOverall >= 4 => 'مقبول',
            default => 'ضعيف'
        };
    }

    /**
     * Get color class based on average overall performance
     *
     * @return string Color class (green, blue, yellow, red)
     */
    public function getColorClass(): string
    {
        return match (true) {
            $this->averageOverall >= 8 => 'green',
            $this->averageOverall >= 6 => 'blue',
            $this->averageOverall >= 4 => 'yellow',
            default => 'red'
        };
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'average_overall' => $this->averageOverall,
            'average_memorization' => $this->averageMemorization,
            'average_reservation' => $this->averageReservation,
            'average_homework' => $this->averageHomework,
            'total_evaluated' => $this->totalEvaluated,
            'type' => $this->type,
            'rating_label' => $this->getRatingLabel(),
            'color_class' => $this->getColorClass(),
        ];
    }
}
