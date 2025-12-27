<?php

namespace App\Enums;

enum RecordingStatus: string
{
    case RECORDING = 'recording';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case DELETED = 'deleted';

    /**
     * Get the Arabic label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::RECORDING => 'جاري التسجيل',
            self::PROCESSING => 'جاري المعالجة',
            self::COMPLETED => 'مكتمل',
            self::FAILED => 'فشل',
            self::DELETED => 'محذوف',
        };
    }

    /**
     * Get the icon for the status
     */
    public function icon(): string
    {
        return match ($this) {
            self::RECORDING => 'ri-record-circle-line',
            self::PROCESSING => 'ri-loader-4-line',
            self::COMPLETED => 'ri-checkbox-circle-line',
            self::FAILED => 'ri-error-warning-line',
            self::DELETED => 'ri-delete-bin-line',
        };
    }

    /**
     * Get the Filament color class for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::RECORDING => 'danger',   // Red - recording in progress
            self::PROCESSING => 'warning', // Yellow - being processed
            self::COMPLETED => 'success',  // Green - ready
            self::FAILED => 'danger',      // Red - error
            self::DELETED => 'gray',       // Gray - deleted
        };
    }

    /**
     * Get the hex color for display
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::RECORDING => '#EF4444',  // red-500
            self::PROCESSING => '#F59E0B', // amber-500
            self::COMPLETED => '#22C55E',  // green-500
            self::FAILED => '#DC2626',     // red-600
            self::DELETED => '#9CA3AF',    // gray-400
        };
    }

    /**
     * Check if recording is in progress
     */
    public function isRecording(): bool
    {
        return $this === self::RECORDING;
    }

    /**
     * Check if recording is being processed
     */
    public function isProcessing(): bool
    {
        return $this === self::PROCESSING;
    }

    /**
     * Check if recording is completed and available
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if recording has failed
     */
    public function hasFailed(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Check if recording has been deleted
     */
    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }

    /**
     * Check if recording is in a final state (no further changes expected)
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::DELETED,
        ]);
    }

    /**
     * Check if recording is in progress or being processed
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::RECORDING,
            self::PROCESSING,
        ]);
    }

    /**
     * Check if recording is available for playback/download
     */
    public function isAvailable(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if recording can be deleted
     */
    public function canDelete(): bool
    {
        return !in_array($this, [
            self::RECORDING,
            self::DELETED,
        ]);
    }

    /**
     * Check if recording can be processed
     */
    public function canProcess(): bool
    {
        return $this === self::RECORDING;
    }

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($status) => $status->label(), self::cases())
        );
    }

    /**
     * Get statuses that should be visible to students
     */
    public static function studentVisibleStatuses(): array
    {
        return [
            self::COMPLETED,
            self::PROCESSING,
        ];
    }

    /**
     * Get statuses for admin filtering
     */
    public static function adminFilterStatuses(): array
    {
        return [
            self::RECORDING,
            self::PROCESSING,
            self::COMPLETED,
            self::FAILED,
        ];
    }

    /**
     * Get color options for badge columns (color => value)
     */
    public static function colorOptions(): array
    {
        $colors = [];
        foreach (self::cases() as $status) {
            $colors[$status->color()] = $status->value;
        }
        return $colors;
    }
}
