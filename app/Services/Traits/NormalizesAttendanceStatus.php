<?php

namespace App\Services\Traits;

/**
 * Shared attendance status normalization logic.
 *
 * Used by both Reports/ (historical report generation) and
 * Attendance/ (real-time attendance tracking) service hierarchies.
 */
trait NormalizesAttendanceStatus
{
    /**
     * Normalize attendance status to a plain string.
     *
     * Handles backed enums (->value), unit enums (->name), and plain strings.
     */
    protected function normalizeAttendanceStatus($status): string
    {
        if (empty($status)) {
            return '';
        }

        if ($status instanceof \BackedEnum) {
            return (string) $status->value;
        }

        if ($status instanceof \UnitEnum) {
            return $status->name;
        }

        return (string) $status;
    }
}
