<?php

namespace App\DTOs\Reports;

/**
 * Data Transfer Object for Trend Chart Data
 *
 * Represents time-series data for Chart.js line charts,
 * typically used for performance trends over time.
 */
class TrendDataDTO
{
    public function __construct(
        public readonly array $labels,
        public readonly array $attendance,
        public readonly array $memorization,
        public readonly array $reservation,
    ) {}

    /**
     * Create DTO from array data
     *
     * @param array $data Trend data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            labels: $data['labels'] ?? [],
            attendance: $data['attendance'] ?? [],
            memorization: $data['memorization'] ?? [],
            reservation: $data['reservation'] ?? [],
        );
    }

    /**
     * Check if trend data is available
     *
     * @return bool True if data exists
     */
    public function hasData(): bool
    {
        return !empty($this->labels);
    }

    /**
     * Get data count
     *
     * @return int Number of data points
     */
    public function getDataCount(): int
    {
        return count($this->labels);
    }

    /**
     * Convert DTO to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'labels' => $this->labels,
            'attendance' => $this->attendance,
            'memorization' => $this->memorization,
            'reservation' => $this->reservation,
        ];
    }
}
