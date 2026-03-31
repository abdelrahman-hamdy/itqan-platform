<?php

namespace App\Services;

use App\Filament\Shared\Traits\ValidatesConflicts;
use Carbon\Carbon;
use Exception;

/**
 * Service for validating session scheduling conflicts.
 *
 * Wraps the ValidatesConflicts trait so it can be called from
 * static Filament actions, Create/Edit pages, and other contexts.
 * Checks conflicts across ALL session types (Quran, Academic, Interactive Course)
 * with a 5-minute break buffer between sessions.
 */
class SessionConflictService
{
    use ValidatesConflicts;

    /**
     * Validate that the teacher has no conflicts at the given time.
     * Also enforces quarter-hour scheduling constraint.
     *
     * @param  int  $teacherId  Teacher user ID
     * @param  Carbon  $scheduledAt  Scheduled time (UTC)
     * @param  int  $durationMinutes  Session duration in minutes
     * @param  int|null  $excludeId  Session ID to exclude (for updates)
     * @param  string  $sessionType  Type: 'quran', 'academic', or 'interactive'
     *
     * @throws Exception If conflict found or invalid time
     */
    public function validate(int $teacherId, Carbon $scheduledAt, int $durationMinutes, ?int $excludeId = null, string $sessionType = 'quran'): void
    {
        // Quarter-hour enforcement
        if ($scheduledAt->minute % 15 !== 0) {
            throw new Exception(__('sessions.validation.quarter_hour_only'));
        }

        $this->validateSessionConflicts([
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $durationMinutes,
            'teacher_id' => $teacherId,
        ], $excludeId, $sessionType);
    }
}
