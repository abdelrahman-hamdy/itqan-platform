<?php

namespace App\Services;

use App\Contracts\StudentDashboardServiceInterface;
use App\Enums\SessionSubscriptionStatus;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranSubscription;
use App\Models\QuranTrialRequest;
use App\Models\RecordedCourse;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service for loading student dashboard data.
 *
 * Extracted from StudentProfileController::index() to reduce controller size.
 * Handles all dashboard data aggregation for the student portal.
 */
class StudentDashboardService implements StudentDashboardServiceInterface
{
    /**
     * Load all dashboard data for a student.
     *
     * @param  User  $user  The student user
     * @return array Dashboard data with keys: circles, privateSessions, trialRequests, interactiveCourses, recordedCourses
     */
    public function loadDashboardData(User $user): array
    {
        $cacheKey = "student:dashboard:{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            $studentProfile = $user->studentProfileUnscoped;
            $academy = $user->academy;

            return [
                'circles' => $this->getQuranCircles($user, $academy),
                'privateSessions' => $this->getQuranPrivateSessions($user, $academy),
                'trialRequests' => $this->getQuranTrialRequests($user, $academy),
                'interactiveCourses' => $this->getInteractiveCourses($studentProfile, $academy),
                'recordedCourses' => $this->getRecordedCourses($user, $academy),
            ];
        });
    }

    /**
     * Get student's enrolled Quran circles with teacher data.
     */
    public function getQuranCircles(User $user, $academy): Collection
    {
        $cacheKey = "student:circles:{$user->id}:{$academy->id}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user, $academy) {
            $circles = QuranCircle::where('academy_id', $academy->id)
                ->whereHas('students', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->with(['students', 'quranTeacher']) // Eager load teacher
                ->get();

            // Load teacher data for each circle
            foreach ($circles as $circle) {
                if ($circle->quran_teacher_id) {
                    $circle->teacherData = $circle->quranTeacher ?? User::find($circle->quran_teacher_id);
                }
            }

            return $circles;
        });
    }

    /**
     * Get student's active Quran private sessions (individual subscriptions).
     */
    public function getQuranPrivateSessions(User $user, $academy): Collection
    {
        $cacheKey = "student:private_sessions:{$user->id}:{$academy->id}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user, $academy) {
            $subscriptions = QuranSubscription::where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('subscription_type', 'individual')
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->whereHas('individualCircle', function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->with(['package', 'individualCircle', 'quranTeacher', 'sessions' => function ($query) {
                    $query->orderBy('scheduled_at', 'desc')->limit(5);
                }])
                ->get();

            // Load teacher data for each subscription
            foreach ($subscriptions as $subscription) {
                if ($subscription->quran_teacher_id) {
                    $subscription->teacherData = $subscription->quranTeacher ?? User::find($subscription->quran_teacher_id);
                }
            }

            return $subscriptions;
        });
    }

    /**
     * Get student's recent Quran trial requests.
     */
    public function getQuranTrialRequests(User $user, $academy): Collection
    {
        return QuranTrialRequest::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher', 'trialSession'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get student's enrolled interactive courses.
     */
    public function getInteractiveCourses($studentProfile, $academy): Collection
    {
        if (! $studentProfile?->id) {
            return collect();
        }

        $studentId = $studentProfile->id;
        $cacheKey = "student:interactive_courses:{$studentId}:{$academy->id}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($studentId, $academy) {
            return InteractiveCourse::where('academy_id', $academy->id)
                ->whereHas('enrollments', function ($query) use ($studentId) {
                    $query->where('student_id', $studentId)
                        ->whereIn('enrollment_status', ['enrolled', 'completed']);
                })
                ->with(['assignedTeacher', 'enrollments' => function ($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                }])
                ->get();
        });
    }

    /**
     * Get student's enrolled recorded courses with progress.
     */
    public function getRecordedCourses(User $user, $academy): Collection
    {
        $cacheKey = "student:recorded_courses:{$user->id}:{$academy->id}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user, $academy) {
            return RecordedCourse::where('academy_id', $academy->id)
                ->whereHas('enrollments', function ($query) use ($user) {
                    $query->where('student_id', $user->id);
                })
                ->with([
                    'enrollments' => function ($query) use ($user) {
                        $query->where('student_id', $user->id);
                    },
                    'instructor',
                    'chapters.lessons',
                ])
                ->get();
        });
    }

    /**
     * Clear all dashboard caches for a student.
     */
    public function clearStudentCache(int $userId, int $academyId): void
    {
        Cache::forget("student:dashboard:{$userId}");
        Cache::forget("student:circles:{$userId}:{$academyId}");
        Cache::forget("student:private_sessions:{$userId}:{$academyId}");
        Cache::forget("student:recorded_courses:{$userId}:{$academyId}");

        // Also clear interactive courses cache if student profile exists
        $user = User::find($userId);
        if ($user && $user->studentProfile) {
            Cache::forget("student:interactive_courses:{$user->studentProfile->id}:{$academyId}");
        }
    }
}
