<?php

namespace App\DTOs\Reports;

/**
 * Data Transfer Object for Individual Stat Card
 *
 * Represents a single statistic display with label, value,
 * icon, color, and optional trend information.
 */
class StatDTO
{
    public function __construct(
        public readonly string $label,
        public readonly string|int|float $value,
        public readonly string $color = 'blue',
        public readonly string $icon = 'ri-star-line',
        public readonly ?string $trend = null, // 'up', 'down', or null
        public readonly ?string $trendValue = null,
    ) {}

    /**
     * Create DTO from array data
     *
     * @param array $data Stat data array
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'] ?? '',
            value: $data['value'] ?? '',
            color: $data['color'] ?? 'blue',
            icon: $data['icon'] ?? 'ri-star-line',
            trend: $data['trend'] ?? null,
            trendValue: $data['trend_value'] ?? null,
        );
    }

    /**
     * Convert DTO to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'color' => $this->color,
            'icon' => $this->icon,
            'trend' => $this->trend,
            'trend_value' => $this->trendValue,
        ];
    }
}
