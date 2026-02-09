<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing student's Quran circle enrollments and related operations.
 *
 * Extracted from StudentProfileController to reduce controller complexity
 * and enable reuse across different contexts (web, API, etc.).
 */
class StudentCircleService
{
    public function __construct(
        private readonly CircleEnrollmentService $enrollmentService,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Get all active Quran circles for a student.
     *
     * @param  User  $user  The student user
     * @return array{group: Collection, individual: Collection}
     */
    public function getStudentCircles(User $user): array
    {
        // Get group circles via subscriptions
        $groupCircles = QuranCircle::whereHas('quranSubscriptions', function ($q) use ($user) {
            $q->where('student_id', $user->id)
                ->where('status', 'active');
        })
            ->with(['quranTeacher.user', 'academy'])
            ->get();

        // Get individual circles
        $individualCircles = QuranIndividualCircle::where('student_id', $user->id)
            ->whereHas('subscription', function ($q) {
                $q->where('status', 'active');
            })
            ->with(['quranTeacher.user', 'subscription', 'academy'])
            ->get();

        return [
            'group' => $groupCircles,
            'individual' => $individualCircles,
        ];
    }

    /**
     * Get detailed information about a group circle for a student.
     *
     * @param  User  $user  The student user
     * @param  int  $circleId  The circle ID
     * @return array|null Circle data or null if not enrolled
     */
    public function getCircleDetails(User $user, int $circleId): ?array
    {
        $circle = QuranCircle::with([
            'quranTeacher.user',
            'academy',
            'sessions' => function ($q) {
                $q->latest('scheduled_at')->limit(20);
            },
        ])->find($circleId);

        if (! $circle) {
            return null;
        }

        // Check if student is enrolled
        $subscription = $circle->quranSubscriptions()
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $subscription) {
            return null;
        }

        // Get student's session reports
        $sessionReports = $circle->sessions()
            ->with(['studentReports' => function ($q) use ($user) {
                $q->where('student_id', $user->id);
            }])
            ->get();

        // Calculate statistics
        $stats = $this->calculateCircleStatistics($user, $circle);

        return [
            'circle' => $circle,
            'subscription' => $subscription,
            'sessionReports' => $sessionReports,
            'stats' => $stats,
        ];
    }

    /**
     * Get detailed information about an individual circle for a student.
     *
     * @param  User  $user  The student user
     * @param  int  $circleId  The individual circle ID
     * @return array|null Circle data or null if not enrolled
     */
    public function getIndividualCircleDetails(User $user, int $circleId): ?array
    {
        $circle = QuranIndividualCircle::with([
            'quranTeacher.user',
            'subscription',
            'academy',
            'sessions' => function ($q) {
                $q->latest('scheduled_at')->limit(20);
            },
        ])
            ->where('student_id', $user->id)
            ->find($circleId);

        if (! $circle) {
            return null;
        }

        // Get student's session reports
        $sessionReports = $circle->sessions()
            ->with(['studentReports' => function ($q) use ($user) {
                $q->where('student_id', $user->id);
            }])
            ->get();

        // Calculate statistics
        $stats = $this->calculateIndividualCircleStatistics($user, $circle);

        return [
            'circle' => $circle,
            'subscription' => $circle->subscription,
            'sessionReports' => $sessionReports,
            'stats' => $stats,
        ];
    }

    /**
     * Enroll a student in a circle.
     *
     * @param  User  $user  The student user
     * @param  int  $circleId  The circle ID
     * @return array{success: bool, message: string, subscription?: QuranSubscription}
     */
    public function enrollInCircle(User $user, int $circleId): array
    {
        try {
            $result = $this->enrollmentService->enrollStudent($user, $circleId);

            if ($result['success']) {
                // Send notification
                $this->notificationService->sendEnrollmentConfirmation(
                    $user,
                    $result['subscription'] ?? null
                );
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to enroll student in circle', [
                'user_id' => $user->id,
                'circle_id' => $circleId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء التسجيل في الحلقة',
            ];
        }
    }

    /**
     * Remove a student from a circle.
     *
     * @param  User  $user  The student user
     * @param  int  $circleId  The circle ID
     * @return array{success: bool, message: string}
     */
    public function leaveCircle(User $user, int $circleId): array
    {
        try {
            $result = $this->enrollmentService->unenrollStudent($user, $circleId);

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to unenroll student from circle', [
                'user_id' => $user->id,
                'circle_id' => $circleId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء التسجيل من الحلقة',
            ];
        }
    }

    /**
     * Calculate statistics for a student in a group circle.
     */
    private function calculateCircleStatistics(User $user, QuranCircle $circle): array
    {
        $sessions = $circle->sessions()
            ->countable()
            ->get();

        $attendedCount = $sessions->filter(function ($session) use ($user) {
            return $session->studentReports()
                ->where('student_id', $user->id)
                ->where('attendance_status', 'attended')
                ->exists();
        })->count();

        $totalSessions = $sessions->count();
        $attendanceRate = $totalSessions > 0 ? round(($attendedCount / $totalSessions) * 100, 1) : 0;

        return [
            'total_sessions' => $totalSessions,
            'attended_sessions' => $attendedCount,
            'attendance_rate' => $attendanceRate,
            'upcoming_sessions' => $circle->sessions()
                ->where('status', SessionStatus::SCHEDULED->value)
                ->count(),
        ];
    }

    /**
     * Calculate statistics for a student in an individual circle.
     */
    private function calculateIndividualCircleStatistics(User $user, QuranIndividualCircle $circle): array
    {
        $sessions = $circle->sessions()
            ->countable()
            ->get();

        $attendedCount = $sessions->where('attendance_status', 'attended')->count();
        $totalSessions = $sessions->count();
        $attendanceRate = $totalSessions > 0 ? round(($attendedCount / $totalSessions) * 100, 1) : 0;

        return [
            'total_sessions' => $totalSessions,
            'attended_sessions' => $attendedCount,
            'attendance_rate' => $attendanceRate,
            'upcoming_sessions' => $circle->sessions()
                ->where('status', SessionStatus::SCHEDULED->value)
                ->count(),
            'remaining_sessions' => $circle->subscription?->remaining_sessions ?? 0,
        ];
    }
}
