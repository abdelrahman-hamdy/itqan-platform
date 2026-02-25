<?php

namespace App\Filament\Shared\Traits;

use Exception;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Trait ValidatesConflicts
 *
 * Provides conflict detection functionality for session scheduling.
 * Checks conflicts across ALL session types: Quran, Academic, and Interactive Course.
 */
trait ValidatesConflicts
{
    /**
     * Default break time between sessions in minutes.
     * This ensures teachers have time to prepare between sessions.
     */
    protected int $defaultBreakMinutes = 5;

    /**
     * Get the break time between sessions.
     * Can be overridden in implementing classes to customize.
     */
    protected function getBreakMinutes(): int
    {
        return $this->defaultBreakMinutes;
    }

    /**
     * Validate session conflicts across all session types
     *
     * @param  array  $data  Session data containing scheduled_at, duration_minutes
     * @param  int|null  $excludeId  Session ID to exclude from conflict check
     * @param  string  $sessionType  Type of session being validated ('quran' or 'academic')
     *
     * @throws Exception If conflict is found
     */
    protected function validateSessionConflicts(array $data, ?int $excludeId = null, string $sessionType = 'quran'): void
    {
        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $duration = $data['duration_minutes'] ?? 60;
        $teacherId = $data['teacher_id'] ?? Auth::id();
        $breakMinutes = $this->getBreakMinutes();

        // Include break time in the conflict window
        // Start time includes buffer before, end time includes buffer after
        $effectiveStart = $scheduledAt->copy()->subMinutes($breakMinutes);
        $effectiveEnd = $scheduledAt->copy()->addMinutes($duration + $breakMinutes);
        $endTime = $scheduledAt->copy()->addMinutes($duration);

        // Check if trying to schedule in the past
        $timezone = AcademyContextService::getTimezone();
        if ($scheduledAt < Carbon::now($timezone)) {
            throw new Exception('لا يمكن جدولة جلسة في وقت ماضي');
        }

        // Check for conflicts with Quran sessions (using effective times that include break buffer)
        $quranConflict = $this->checkQuranSessionConflicts($teacherId, $effectiveStart, $effectiveEnd, $sessionType === 'quran' ? $excludeId : null);
        if ($quranConflict) {
            $conflictTime = $quranConflict->scheduled_at->timezone($timezone)->format('Y/m/d H:i');
            $conflictEnd = $quranConflict->scheduled_at->copy()->addMinutes($quranConflict->duration_minutes ?? 60)->timezone($timezone)->format('H:i');
            throw new Exception("يوجد تعارض مع جلسة قرآن ({$conflictTime} - {$conflictEnd}). يجب ترك {$breakMinutes} دقائق على الأقل بين الجلسات.");
        }

        // Check for conflicts with Academic sessions
        $academicConflict = $this->checkAcademicSessionConflicts($teacherId, $effectiveStart, $effectiveEnd, $sessionType === 'academic' ? $excludeId : null);
        if ($academicConflict) {
            $conflictTime = $academicConflict->scheduled_at->timezone($timezone)->format('Y/m/d H:i');
            $conflictEnd = $academicConflict->scheduled_at->copy()->addMinutes($academicConflict->duration_minutes ?? 60)->timezone($timezone)->format('H:i');
            throw new Exception("يوجد تعارض مع جلسة أكاديمية ({$conflictTime} - {$conflictEnd}). يجب ترك {$breakMinutes} دقائق على الأقل بين الجلسات.");
        }

        // Check for conflicts with Interactive Course sessions
        $courseConflict = $this->checkInteractiveCourseSessionConflicts($teacherId, $effectiveStart, $effectiveEnd, $sessionType === 'interactive' ? $excludeId : null);
        if ($courseConflict) {
            $conflictTime = $courseConflict->scheduled_at->timezone($timezone)->format('Y/m/d H:i');
            $conflictEnd = $courseConflict->scheduled_at->copy()->addMinutes($courseConflict->duration_minutes ?? 60)->timezone($timezone)->format('H:i');
            $courseTitle = $courseConflict->course?->title ?? 'دورة تفاعلية';
            throw new Exception("يوجد تعارض مع جلسة دورة ({$courseTitle}) في ({$conflictTime} - {$conflictEnd}). يجب ترك {$breakMinutes} دقائق على الأقل بين الجلسات.");
        }
    }

    /**
     * Check for conflicts with Quran sessions
     *
     * @param  int  $teacherId  Teacher ID
     * @param  Carbon  $scheduledAt  Session start time
     * @param  Carbon  $endTime  Session end time
     * @param  int|null  $excludeId  Session ID to exclude
     * @return QuranSession|null Conflicting session or null
     */
    protected function checkQuranSessionConflicts(int $teacherId, Carbon $scheduledAt, Carbon $endTime, ?int $excludeId = null): ?QuranSession
    {
        return QuranSession::where('quran_teacher_id', $teacherId)
            ->where('status', '!=', SessionStatus::CANCELLED->value)
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
     * @param  int  $teacherId  Teacher ID (or teacher profile ID for academic)
     * @param  Carbon  $scheduledAt  Session start time
     * @param  Carbon  $endTime  Session end time
     * @param  int|null  $excludeId  Session ID to exclude
     * @return AcademicSession|null Conflicting session or null
     */
    protected function checkAcademicSessionConflicts(int $teacherId, Carbon $scheduledAt, Carbon $endTime, ?int $excludeId = null): ?AcademicSession
    {
        // For academic sessions, we need to use the teacher profile ID.
        // Resolve the profile from the passed $teacherId (user ID), not from Auth::user().
        $academicTeacherId = AcademicTeacherProfile::where('user_id', $teacherId)->value('id');

        if (! $academicTeacherId) {
            return null; // No academic teacher profile for this user, no conflict possible
        }

        return AcademicSession::where('academic_teacher_id', $academicTeacherId)
            ->where('status', '!=', SessionStatus::CANCELLED->value)
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
     * Check for conflicts with Interactive Course sessions
     *
     * @param  int  $teacherId  Teacher user ID
     * @param  Carbon  $scheduledAt  Session start time
     * @param  Carbon  $endTime  Session end time
     * @param  int|null  $excludeId  Session ID to exclude
     * @return InteractiveCourseSession|null Conflicting session or null
     */
    protected function checkInteractiveCourseSessionConflicts(int $teacherId, Carbon $scheduledAt, Carbon $endTime, ?int $excludeId = null): ?InteractiveCourseSession
    {
        // For interactive courses, we need to find sessions where the teacher is assigned.
        // Resolve the academic teacher profile ID from the passed $teacherId (user ID), not from Auth::user().
        $academicTeacherId = AcademicTeacherProfile::where('user_id', $teacherId)->value('id');

        if (! $academicTeacherId) {
            return null; // No academic teacher profile for this user, no conflict possible
        }

        return InteractiveCourseSession::whereHas('course', function ($query) use ($academicTeacherId) {
            $query->where('assigned_teacher_id', $academicTeacherId);
        })
            ->where('status', '!=', SessionStatus::CANCELLED->value)
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
            ->with('course:id,title')
            ->first();
    }

    /**
     * Validate if teacher has any conflicts at the specified time
     *
     * Returns true if slot is available, false if conflict exists
     *
     * @param  Carbon  $scheduledAt  Session start time
     * @param  int  $duration  Duration in minutes
     * @param  int|null  $excludeSessionId  Session ID to exclude
     * @param  string  $sessionType  Type of session ('quran', 'academic', or 'interactive')
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
        } catch (Exception $e) {
            return false;
        }
    }
}
