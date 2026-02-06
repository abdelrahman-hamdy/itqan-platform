<?php

namespace App\Services\Calendar;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Event Fetching Service
 *
 * Provides optimized database queries for fetching calendar events.
 * Queries are executed fresh each time to ensure data consistency.
 *
 * @see \App\Filament\Shared\Widgets\UnifiedCalendarWidget
 */
class EventFetchingService
{
    /**
     * Get Quran sessions for user
     */
    public function getQuranSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $query = QuranSession::select([
            'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
            'quran_teacher_id', 'student_id', 'quran_subscription_id', 'circle_id', 'session_type',
            'individual_circle_id',
        ])
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with([
                'quranTeacher:id,first_name,last_name,name,email,gender',
                'student:id,name',
                'subscription:id,package_id',
                'circle:id,name,circle_code',
                'individualCircle:id,name,circle_code,default_duration_minutes',
            ]);

        if ($user->isQuranTeacher()) {
            $query->where('quran_teacher_id', $user->id);
        } else {
            $query->where('student_id', $user->id);
        }

        return $query->get();
    }

    /**
     * Get course sessions for user
     */
    public function getCourseSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        // Use whereBetween for consistency with other session types
        $query = InteractiveCourseSession::whereBetween('scheduled_at', [$startDate, $endDate])
            ->with([
                'course' => function ($query) {
                    $query->with([
                        'assignedTeacher:id,user_id,first_name,last_name',
                        'assignedTeacher.user:id,name,email,gender',
                    ]);
                },
            ]);

        if ($user->isAcademicTeacher()) {
            $query->whereHas('course', function ($q) use ($user) {
                $q->where('assigned_teacher_id', $user->academicTeacherProfile->id);
            });
        } else {
            // Check if user has a student profile before accessing it
            if ($user->studentProfile) {
                $query->whereHas('course.enrollments', function ($q) use ($user) {
                    $q->where('student_id', $user->studentProfile->id);
                });
            } else {
                // Return empty collection if user has no student profile
                return collect();
            }
        }

        return $query->get();
    }

    /**
     * Get circle sessions for user
     */
    public function getCircleSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        if ($user->isQuranTeacher()) {
            return QuranSession::select([
                'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
                'quran_teacher_id', 'circle_id', 'session_type',
            ])
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->where('session_type', 'group')
                ->where('quran_teacher_id', $user->id)
                ->with([
                    'circle:id,name,circle_code,enrolled_students',
                    'quranTeacher:id,first_name,last_name,name,email,gender',
                ])
                ->get();
        } else {
            // Get sessions for circles the user is enrolled in
            $userCircles = QuranCircle::whereHas('students', function ($q) use ($user) {
                $q->where('student_id', $user->id);
            })->pluck('id');

            return QuranSession::select([
                'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
                'quran_teacher_id', 'circle_id', 'session_type',
            ])
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->where('session_type', 'group')
                ->whereIn('circle_id', $userCircles)
                ->with([
                    'circle:id,name,circle_code',
                    'quranTeacher:id,first_name,last_name,name,email,gender',
                ])
                ->get();
        }
    }

    /**
     * Get break times and unavailable periods
     */
    public function getBreakTimes(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        // This would be extended to include user-defined break times
        // For now, return empty collection
        return collect();
    }

    /**
     * Check session conflicts
     */
    public function checkSessionConflicts(
        User $user,
        Carbon $startTime,
        Carbon $endTime,
        ?string $excludeType,
        ?int $excludeId
    ): Collection {

        $query = QuranSession::where('status', '!=', SessionStatus::CANCELLED->value);

        if ($user->isQuranTeacher()) {
            $query->where('quran_teacher_id', $user->id);
        } else {
            $query->where('student_id', $user->id);
        }

        if ($excludeType === 'quran_session' && $excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Get all sessions and filter in PHP for better portability
        return $query->get()->filter(function ($session) use ($startTime, $endTime) {
            $sessionStart = Carbon::parse($session->scheduled_at);
            $sessionEnd = $sessionStart->copy()->addMinutes($session->duration_minutes);

            // Check if there's any overlap
            return $startTime->lt($sessionEnd) && $endTime->gt($sessionStart);
        });
    }

    /**
     * Check course conflicts
     */
    public function checkCourseConflicts(
        User $user,
        Carbon $startTime,
        Carbon $endTime,
        ?string $excludeType,
        ?int $excludeId
    ): Collection {

        $query = InteractiveCourseSession::where(function ($q) use ($startTime, $endTime) {
            // Check for time conflicts using scheduled_at datetime
            $q->where('scheduled_at', '>=', $startTime)
                ->where('scheduled_at', '<=', $endTime);
        })->where('status', '!=', SessionStatus::CANCELLED->value);

        if ($user->isAcademicTeacher()) {
            $profile = $user->academicTeacherProfile;
            if (! $profile) {
                return collect();
            }
            $query->whereHas('course', function ($q) use ($profile) {
                $q->where('assigned_teacher_id', $profile->id);
            });
        } else {
            $studentProfile = $user->studentProfile;
            if (! $studentProfile) {
                return collect();
            }
            $query->whereHas('course.enrollments', function ($q) use ($studentProfile) {
                $q->where('student_id', $studentProfile->id);
            });
        }

        if ($excludeType === 'course_session' && $excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }

    /**
     * Check circle conflicts for teachers
     */
    public function checkCircleConflicts(
        User $user,
        Carbon $startTime,
        Carbon $endTime,
        ?string $excludeType,
        ?int $excludeId
    ): Collection {

        if (! $user->isQuranTeacher()) {
            return collect();
        }

        $query = QuranSession::where('session_type', 'group')
            ->where('quran_teacher_id', $user->id)
            ->where('status', '!=', SessionStatus::CANCELLED->value);

        if ($excludeType === 'circle_session' && $excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Get all sessions and filter in PHP for better portability
        return $query->get()->filter(function ($session) use ($startTime, $endTime) {
            $sessionStart = Carbon::parse($session->scheduled_at);
            $sessionEnd = $sessionStart->copy()->addMinutes($session->duration_minutes);

            // Check if there's any overlap
            return $startTime->lt($sessionEnd) && $endTime->gt($sessionStart);
        });
    }

    /**
     * Get trial sessions for a Quran teacher.
     *
     * Trial sessions are QuranSession records linked to a TrialRequest.
     */
    public function getTrialSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        if (! $user->isQuranTeacher()) {
            return collect();
        }

        return QuranSession::select([
            'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
            'quran_teacher_id', 'student_id', 'session_type', 'trial_request_id',
        ])
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->where('quran_teacher_id', $user->id)
            ->whereNotNull('trial_request_id')
            ->with([
                'quranTeacher:id,first_name,last_name,name,email,gender',
                'student:id,name',
                'trialRequest:id,student_name,status',
            ])
            ->get();
    }

    /**
     * Get academic private lesson sessions for an academic teacher.
     */
    public function getAcademicSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        if (! $user->isAcademicTeacher()) {
            return collect();
        }

        $profile = $user->academicTeacherProfile;
        if (! $profile) {
            return collect();
        }

        return AcademicSession::select([
            'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
            'academic_teacher_id', 'student_id', 'academic_subscription_id',
            'academic_individual_lesson_id', 'session_type', 'session_code',
        ])
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->where('academic_teacher_id', $profile->id)
            ->with([
                'academicTeacher:id,user_id,first_name,last_name',
                'academicTeacher.user:id,name,email,gender',
                'student:id,name',
                'academicIndividualLesson:id,subject_id,subscription_id',
                'academicIndividualLesson.subject:id,name,name_en',
                'subscription:id,package_id,starts_at,ends_at,status',
            ])
            ->get();
    }

    /**
     * Get Quran individual sessions (excludes group and trial).
     */
    public function getQuranIndividualSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        if (! $user->isQuranTeacher()) {
            return collect();
        }

        return QuranSession::select([
            'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
            'quran_teacher_id', 'student_id', 'quran_subscription_id',
            'individual_circle_id', 'session_type',
        ])
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->where('quran_teacher_id', $user->id)
            ->where('session_type', 'individual')
            ->whereNull('trial_request_id')
            ->with([
                'quranTeacher:id,first_name,last_name,name,email,gender',
                'student:id,name',
                'subscription:id,package_id,starts_at,ends_at,status',
                'individualCircle:id,name,circle_code,default_duration_minutes',
            ])
            ->get();
    }

    /**
     * Get Quran group sessions (circle sessions).
     */
    public function getQuranGroupSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        // This is essentially the same as getCircleSessions, but with a clearer name
        return $this->getCircleSessions($user, $startDate, $endDate);
    }

    /**
     * Clear calendar cache for a user.
     * Note: Caching has been removed, this method is kept for compatibility.
     */
    public function clearUserCache(User $user): void
    {
        // No-op: caching has been removed
    }

    /**
     * Clear all calendar caches for a user ID.
     * Note: Caching has been removed, this method is kept for compatibility.
     */
    public function clearAllCalendarCaches(int $userId): void
    {
        // No-op: caching has been removed
    }
}
