<?php

namespace App\DTOs\Reports;

/**
 * Data Transfer Object for Progress Tracking
 *
 * Represents progress metrics such as completion percentage,
 * pages memorized, or sessions completed.
 */
class ProgressDTO
{
    public function __construct(
        public readonly int|float $currentValue,
        public readonly int|float $totalValue,
        public readonly float $percentage,
        public readonly string $label,
        public readonly ?string $unit = null,
    ) {}

    /**
     * Create DTO from array data
     *
     * @param array $data Progress data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $current = $data['current_value'] ?? 0;
        $total = $data['total_value'] ?? 0;
        $percentage = $total > 0 ? round(($current / $total) * 100, 2) : 0;

        return new self(
            currentValue: $current,
            totalValue: $total,
            percentage: $data['percentage'] ?? $percentage,
            label: $data['label'] ?? 'التقدم',
            unit: $data['unit'] ?? null,
        );
    }

    /**
     * Create DTO for Quran progress (pages/papers)
     *
     * @param float $pagesMemorized Pages memorized
     * @param int|null $totalPages Total Quran pages (defaults to config value)
     * @return self
     */
    public static function forQuranProgress(float $pagesMemorized, ?int $totalPages = null): self
    {
        $totalPages = $totalPages ?? config('quran.total_pages', 604);
        $percentage = $totalPages > 0 ? round(($pagesMemorized / $totalPages) * 100, 2) : 0;

        return new self(
            currentValue: $pagesMemorized,
            totalValue: $totalPages,
            percentage: $percentage,
            label: 'نسبة إتمام الحفظ',
            unit: 'صفحة',
        );
    }

    /**
     * Create DTO for sessions progress
     *
     * @param int $completedSessions Completed sessions count
     * @param int $totalSessions Total sessions count
     * @return self
     */
    public static function forSessionsProgress(int $completedSessions, int $totalSessions): self
    {
        $percentage = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 2) : 0;

        return new self(
            currentValue: $completedSessions,
            totalValue: $totalSessions,
            percentage: $percentage,
            label: 'الجلسات المكتملة',
            unit: 'جلسة',
        );
    }

    /**
     * Get color class based on progress percentage
     *
     * @return string Color class (green, yellow, red)
     */
    public function getColorClass(): string
    {
        return match(true) {
            $this->percentage >= 75 => 'green',
            $this->percentage >= 50 => 'blue',
            $this->percentage >= 25 => 'yellow',
            default => 'red'
        };
    }

    /**
     * Get formatted display value
     *
     * @return string Formatted value with unit
     */
    public function getFormattedValue(): string
    {
        $value = is_float($this->currentValue)
            ? number_format($this->currentValue, 1)
            : $this->currentValue;

        return $this->unit ? "{$value} {$this->unit}" : (string) $value;
    }

    /**
     * Convert DTO to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'current_value' => $this->currentValue,
            'total_value' => $this->totalValue,
            'percentage' => $this->percentage,
            'label' => $this->label,
            'unit' => $this->unit,
            'color_class' => $this->getColorClass(),
            'formatted_value' => $this->getFormattedValue(),
        ];
    }
}
