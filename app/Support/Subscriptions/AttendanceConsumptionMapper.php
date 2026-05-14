<?php

namespace App\Support\Subscriptions;

use App\Enums\AttendanceStatus;
use App\Models\SessionConsumption;

/**
 * Shared mapping from a session/attendance status onto the
 * SessionConsumption consumption_type bucket. Lifted out of
 * BootstrapConsumptionRowsCommand so the same rules apply to:
 *
 *   - The historical bootstrap importer (one-time backfill).
 *   - The live auto-attendance Job (Tier 3 cutover).
 *   - The manual marking + reverse paths (G5 cutover).
 *
 * Rules:
 *  - ATTENDED               → TYPE_ATTENDED
 *  - LATE                   → TYPE_LATE
 *  - LEFT                   → TYPE_LEFT
 *  - PARTIALLY_ATTENDED     → TYPE_LATE (legacy collapsing, see
 *                              AttendanceStatus docblock)
 *  - ABSENT                 → TYPE_ABSENT_COUNTED when the attendance
 *                              row carries counts_for_subscription=true;
 *                              otherwise null (no consumption row).
 *  - anything else          → null
 */
final class AttendanceConsumptionMapper
{
    /**
     * @param  mixed  $attendanceStatus  AttendanceStatus enum or its string value
     * @param  bool  $countsForSubscription  whether the ABSENT case should mint a row
     */
    public static function consumptionTypeFor(mixed $attendanceStatus, bool $countsForSubscription = true): ?string
    {
        $value = $attendanceStatus instanceof AttendanceStatus
            ? $attendanceStatus->value
            : (is_string($attendanceStatus) ? $attendanceStatus : null);

        if ($value === null) {
            return null;
        }

        return match ($value) {
            AttendanceStatus::ATTENDED->value => SessionConsumption::TYPE_ATTENDED,
            AttendanceStatus::LATE->value => SessionConsumption::TYPE_LATE,
            AttendanceStatus::LEFT->value => SessionConsumption::TYPE_LEFT,
            AttendanceStatus::PARTIALLY_ATTENDED->value => SessionConsumption::TYPE_LATE,
            AttendanceStatus::ABSENT->value => $countsForSubscription
                ? SessionConsumption::TYPE_ABSENT_COUNTED
                : null,
            default => null,
        };
    }
}
