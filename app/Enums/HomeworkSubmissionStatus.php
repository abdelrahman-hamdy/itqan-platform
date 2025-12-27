<?php

namespace App\Enums;

enum HomeworkSubmissionStatus: string
{
    case NOT_STARTED = 'not_started';
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case LATE = 'late';
    case GRADED = 'graded';
    case RETURNED = 'returned';
    case RESUBMITTED = 'resubmitted';

    /**
     * Get the Arabic label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'لم يتم البدء',
            self::DRAFT => 'مسودة',
            self::SUBMITTED => 'تم التسليم',
            self::LATE => 'متأخر',
            self::GRADED => 'تم التقييم',
            self::RETURNED => 'مُعاد للمراجعة',
            self::RESUBMITTED => 'أُعيد تسليمه',
        };
    }

    /**
     * Get the icon for the status
     */
    public function icon(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'ri-file-line',
            self::DRAFT => 'ri-draft-line',
            self::SUBMITTED => 'ri-send-plane-fill',
            self::LATE => 'ri-alarm-warning-line',
            self::GRADED => 'ri-checkbox-circle-line',
            self::RETURNED => 'ri-arrow-go-back-line',
            self::RESUBMITTED => 'ri-refresh-line',
        };
    }

    /**
     * Get the Filament color class for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'gray',
            self::DRAFT => 'warning',
            self::SUBMITTED => 'info',
            self::LATE => 'danger',
            self::GRADED => 'success',
            self::RETURNED => 'warning',
            self::RESUBMITTED => 'info',
        };
    }

    /**
     * Get the hex color for display
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::NOT_STARTED => '#6B7280',  // gray-500
            self::DRAFT => '#F59E0B',        // amber-500
            self::SUBMITTED => '#3B82F6',    // blue-500
            self::LATE => '#EF4444',         // red-500
            self::GRADED => '#22C55E',       // green-500
            self::RETURNED => '#F59E0B',     // amber-500
            self::RESUBMITTED => '#3B82F6',  // blue-500
        };
    }

    /**
     * Check if student can submit
     */
    public function canSubmit(): bool
    {
        return in_array($this, [
            self::NOT_STARTED,
            self::DRAFT,
            self::RETURNED,
        ]);
    }

    /**
     * Check if submission is pending (not submitted yet)
     */
    public function isPending(): bool
    {
        return in_array($this, [
            self::NOT_STARTED,
            self::DRAFT,
        ]);
    }

    /**
     * Check if submission has been submitted
     */
    public function isSubmitted(): bool
    {
        return in_array($this, [
            self::SUBMITTED,
            self::LATE,
            self::GRADED,
            self::RESUBMITTED,
        ]);
    }

    /**
     * Check if submission is graded
     */
    public function isGraded(): bool
    {
        return $this === self::GRADED;
    }

    /**
     * Check if submission needs teacher review
     */
    public function needsReview(): bool
    {
        return in_array($this, [
            self::SUBMITTED,
            self::LATE,
            self::RESUBMITTED,
        ]);
    }

    /**
     * Check if submission is late
     */
    public function isLate(): bool
    {
        return $this === self::LATE;
    }

    /**
     * Check if submission is in draft state
     */
    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if submission was returned for revision
     */
    public function isReturned(): bool
    {
        return $this === self::RETURNED;
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
     * Get student-visible statuses
     */
    public static function studentStatuses(): array
    {
        return [
            self::NOT_STARTED,
            self::DRAFT,
            self::SUBMITTED,
            self::LATE,
            self::GRADED,
            self::RETURNED,
            self::RESUBMITTED,
        ];
    }

    /**
     * Get teacher-visible statuses for filtering
     */
    public static function teacherFilterStatuses(): array
    {
        return [
            self::SUBMITTED,
            self::LATE,
            self::GRADED,
            self::RETURNED,
            self::RESUBMITTED,
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
