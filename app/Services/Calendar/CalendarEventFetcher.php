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
 * Service for fetching calendar events from various session types.
 *
 * Handles querying and eager loading for Quran sessions, academic sessions,
 * interactive course sessions, and circle sessions.
 */
class CalendarEventFetcher
{
    /**
     * Get all Quran sessions for a user within a date range.
     *
     * @param User $user The user to fetch sessions for
     * @param Carbon $startDate Start of the date range
     * @param Carbon $endDate End of the date range
     * @return Collection
     */
    public function getQuranSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $query = QuranSession::query()
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with([
                'quranTeacher.user',
                'student',
                'individualCircle',
                'circle',
            ]);

        // Filter based on user role
        if ($user->isQuranTeacher()) {
            $teacherProfile = $user->quranTeacherProfile;
            if ($teacherProfile) {
                $query->where('quran_teacher_id', $teacherProfile->id);
            }
        } elseif ($user->isStudent()) {
            $query->where(function ($q) use ($user) {
                $q->where('student_id', $user->id)
                    ->orWhereHas('circle.quranSubscriptions', function ($sq) use ($user) {
                        $sq->where('student_id', $user->id)
                            ->where('status', 'active');
                    });
            });
        } elseif ($user->isParent()) {
            $childIds = $user->parentProfile?->students->pluck('user_id') ?? collect();
            $query->where(function ($q) use ($childIds) {
                $q->whereIn('student_id', $childIds)
                    ->orWhereHas('circle.quranSubscriptions', function ($sq) use ($childIds) {
                        $sq->whereIn('student_id', $childIds)
                            ->where('status', 'active');
                    });
            });
        }

        return $query->orderBy('scheduled_at')->get();
    }

    /**
     * Get all academic sessions for a user within a date range.
     *
     * @param User $user The user to fetch sessions for
     * @param Carbon $startDate Start of the date range
     * @param Carbon $endDate End of the date range
     * @return Collection
     */
    public function getAcademicSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $query = AcademicSession::query()
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with([
                'academicTeacher.user',
                'student',
                'academicSubscription.academicSubject',
            ]);

        // Filter based on user role
        if ($user->isAcademicTeacher()) {
            $teacherProfile = $user->academicTeacherProfile;
            if ($teacherProfile) {
                $query->where('academic_teacher_id', $teacherProfile->id);
            }
        } elseif ($user->isStudent()) {
            $query->where('student_id', $user->id);
        } elseif ($user->isParent()) {
            $childIds = $user->parentProfile?->students->pluck('user_id') ?? collect();
            $query->whereIn('student_id', $childIds);
        }

        return $query->orderBy('scheduled_at')->get();
    }

    /**
     * Get all interactive course sessions for a user within a date range.
     *
     * @param User $user The user to fetch sessions for
     * @param Carbon $startDate Start of the date range
     * @param Carbon $endDate End of the date range
     * @return Collection
     */
    public function getInteractiveCourseSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $query = InteractiveCourseSession::query()
            ->with([
                'course.assignedTeacher.user',
                'course.enrollments',
            ]);

        // Handle the different date field for interactive sessions
        $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('scheduled_at', [$startDate, $endDate])
                ->orWhere(function ($sq) use ($startDate, $endDate) {
                    $sq->whereNotNull('scheduled_date')
                        ->whereBetween('scheduled_date', [$startDate->toDateString(), $endDate->toDateString()]);
                });
        });

        // Filter based on user role
        if ($user->isAcademicTeacher()) {
            $teacherProfile = $user->academicTeacherProfile;
            if ($teacherProfile) {
                $query->whereHas('course', function ($q) use ($teacherProfile) {
                    $q->where('academic_teacher_id', $teacherProfile->id);
                });
            }
        } elseif ($user->isStudent()) {
            $query->whereHas('course.enrollments', function ($q) use ($user) {
                $q->where('student_id', $user->id)
                    ->where('status', 'active');
            });
        } elseif ($user->isParent()) {
            $childIds = $user->parentProfile?->students->pluck('user_id') ?? collect();
            $query->whereHas('course.enrollments', function ($q) use ($childIds) {
                $q->whereIn('student_id', $childIds)
                    ->where('status', 'active');
            });
        }

        return $query->orderBy('scheduled_at')->get();
    }

    /**
     * Get all circle sessions for a user within a date range.
     *
     * @param User $user The user to fetch sessions for
     * @param Carbon $startDate Start of the date range
     * @param Carbon $endDate End of the date range
     * @return Collection
     */
    public function getCircleSessions(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        // For group circles, we need to fetch sessions from circles the user is enrolled in
        $circleIds = collect();

        if ($user->isStudent()) {
            $circleIds = QuranCircle::whereHas('quranSubscriptions', function ($q) use ($user) {
                $q->where('student_id', $user->id)
                    ->where('status', 'active');
            })->pluck('id');
        } elseif ($user->isParent()) {
            $childIds = $user->parentProfile?->students->pluck('user_id') ?? collect();
            $circleIds = QuranCircle::whereHas('quranSubscriptions', function ($q) use ($childIds) {
                $q->whereIn('student_id', $childIds)
                    ->where('status', 'active');
            })->pluck('id');
        } elseif ($user->isQuranTeacher()) {
            $teacherProfile = $user->quranTeacherProfile;
            if ($teacherProfile) {
                $circleIds = QuranCircle::where('quran_teacher_id', $teacherProfile->id)->pluck('id');
            }
        }

        if ($circleIds->isEmpty()) {
            return collect();
        }

        return QuranSession::query()
            ->whereIn('quran_circle_id', $circleIds)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with([
                'quranTeacher.user',
                'circle',
            ])
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * Get upcoming sessions count for a user.
     *
     * @param User $user The user
     * @param int $days Number of days to look ahead
     * @return int
     */
    public function getUpcomingSessionsCount(User $user, int $days = 7): int
    {
        $startDate = now();
        $endDate = now()->addDays($days);

        $count = 0;

        $count += $this->getQuranSessions($user, $startDate, $endDate)
            ->where('status', SessionStatus::SCHEDULED->value)
            ->count();

        $count += $this->getAcademicSessions($user, $startDate, $endDate)
            ->where('status', SessionStatus::SCHEDULED->value)
            ->count();

        $count += $this->getInteractiveCourseSessions($user, $startDate, $endDate)
            ->where('status', SessionStatus::SCHEDULED->value)
            ->count();

        return $count;
    }
}
