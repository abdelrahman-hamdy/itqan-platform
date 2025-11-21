<?php

namespace App\Filament\Shared\Traits;

use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Trait ValidatesConflicts
 *
 * Provides conflict detection functionality for session scheduling.
 * Checks conflicts across both Quran and Academic sessions.
 */
trait ValidatesConflicts
{
    /**
     * Validate session conflicts across all session types
     *
     * @param array $data Session data containing scheduled_at, duration_minutes
     * @param int|null $excludeId Session ID to exclude from conflict check
     * @param string $sessionType Type of session being validated ('quran' or 'academic')
     * @return void
     * @throws \Exception If conflict is found
     */
    protected function validateSessionConflicts(array $data, ?int $excludeId = null, string $sessionType = 'quran'): void
    {
        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $duration = $data['duration_minutes'] ?? 60;
        $teacherId = $data['teacher_id'] ?? Auth::id();

        $endTime = $scheduledAt->copy()->addMinutes($duration);

        // Check if trying to schedule in the past
        $timezone = AcademyContextService::getTimezone();
        if ($scheduledAt < Carbon::now($timezone)) {
            throw new \Exception('لا يمكن جدولة جلسة في وقت ماضي');
        }

        // Check for conflicts with Quran sessions
        $quranConflict = $this->checkQuranSessionConflicts($teacherId, $scheduledAt, $endTime, $sessionType === 'quran' ? $excludeId : null);
        if ($quranConflict) {
            $conflictTime = $quranConflict->scheduled_at->timezone($timezone)->format('Y/m/d H:i');
            throw new \Exception("يوجد تعارض مع جلسة قرآن في {$conflictTime}. المعلم لا يمكنه أن يكون في مكانين في نفس الوقت!");
        }

        // Check for conflicts with Academic sessions
        $academicConflict = $this->checkAcademicSessionConflicts($teacherId, $scheduledAt, $endTime, $sessionType === 'academic' ? $excludeId : null);
        if ($academicConflict) {
            $conflictTime = $academicConflict->scheduled_at->timezone($timezone)->format('Y/m/d H:i');
            throw new \Exception("يوجد تعارض مع جلسة أكاديمية في {$conflictTime}. المعلم لا يمكنه أن يكون في مكانين في نفس الوقت!");
        }
    }

    /**
     * Check for conflicts with Quran sessions
     *
     * @param int $teacherId Teacher ID
     * @param Carbon $scheduledAt Session start time
     * @param Carbon $endTime Session end time
     * @param int|null $excludeId Session ID to exclude
     * @return QuranSession|null Conflicting session or null
     */
    protected function checkQuranSessionConflicts(int $teacherId, Carbon $scheduledAt, Carbon $endTime, ?int $excludeId = null): ?QuranSession
    {
        return QuranSession::where('quran_teacher_id', $teacherId)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->where(function ($query) use ($scheduledAt, $endTime) {
                $query->where(function ($q) use ($scheduledAt, $endTime) {
                    // New session starts during existing session
                    $q->whereRaw('? BETWEEN scheduled_at AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)', [$scheduledAt])
                      // New session ends during existing session
                        ->orWhereRaw('? BETWEEN scheduled_at AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)', [$endTime])
                      // New session completely contains existing session
                        ->orWhere(function ($subQ) use ($scheduledAt, $endTime) {
                            $subQ->where('scheduled_at', '>=', $scheduledAt)
                                ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) <= ?', [$endTime]);
                        });
                });
            })
            ->first();
    }

    /**
     * Check for conflicts with Academic sessions
     *
     * @param int $teacherId Teacher ID (or teacher profile ID for academic)
     * @param Carbon $scheduledAt Session start time
     * @param Carbon $endTime Session end time
     * @param int|null $excludeId Session ID to exclude
     * @return AcademicSession|null Conflicting session or null
     */
    protected function checkAcademicSessionConflicts(int $teacherId, Carbon $scheduledAt, Carbon $endTime, ?int $excludeId = null): ?AcademicSession
    {
        // For academic sessions, we need to use the teacher profile ID
        $user = Auth::user();
        $academicTeacherId = $user?->academicTeacherProfile?->id;

        if (!$academicTeacherId) {
            return null; // No academic teacher profile, no conflict possible
        }

        return AcademicSession::where('academic_teacher_id', $academicTeacherId)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->where(function ($query) use ($scheduledAt, $endTime) {
                $query->where(function ($q) use ($scheduledAt, $endTime) {
                    // New session starts during existing session
                    $q->whereRaw('? BETWEEN scheduled_at AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)', [$scheduledAt])
                      // New session ends during existing session
                        ->orWhereRaw('? BETWEEN scheduled_at AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)', [$endTime])
                      // New session completely contains existing session
                        ->orWhere(function ($subQ) use ($scheduledAt, $endTime) {
                            $subQ->where('scheduled_at', '>=', $scheduledAt)
                                ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) <= ?', [$endTime]);
                        });
                });
            })
            ->first();
    }

    /**
     * Validate if teacher has any conflicts at the specified time
     *
     * Returns true if slot is available, false if conflict exists
     *
     * @param Carbon $scheduledAt Session start time
     * @param int $duration Duration in minutes
     * @param int|null $excludeSessionId Session ID to exclude
     * @param string $sessionType Type of session ('quran' or 'academic')
     * @return bool True if no conflicts, false otherwise
     */
    protected function isTimeSlotAvailable(Carbon $scheduledAt, int $duration, ?int $excludeSessionId = null, string $sessionType = 'quran'): bool
    {
        try {
            $this->validateSessionConflicts([
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $duration,
            ], $excludeSessionId, $sessionType);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
