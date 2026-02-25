<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Unified controller that aggregates sessions from all types
 */
class UnifiedSessionController extends BaseStudentSessionController
{
    /**
     * Get all sessions for the student (aggregated from all types).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessions = [];

        // Get filter parameters
        $type = $request->get('type'); // quran, academic, interactive, or null for all
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Get student profile ID for interactive course enrollment queries
        $studentProfileId = $user->studentProfile?->id;

        if (! $type || $type === 'quran') {
            $quranSessions = $this->getQuranSessions($user->id, $status, $dateFrom, $dateTo);
            $sessions = array_merge($sessions, $quranSessions);
        }

        if (! $type || $type === 'academic') {
            $academicSessions = $this->getAcademicSessions($user->id, $status, $dateFrom, $dateTo);
            $sessions = array_merge($sessions, $academicSessions);
        }

        if ((! $type || $type === 'interactive') && $studentProfileId) {
            $interactiveSessions = $this->getInteractiveSessions($studentProfileId, $status, $dateFrom, $dateTo);
            $sessions = array_merge($sessions, $interactiveSessions);
        }

        // Sort by scheduled time (descending)
        $sessions = $this->sortSessionsByTime($sessions);

        $page = (int) $request->get('page', 1);
        $perPage = min((int) $request->get('per_page', 15), 100);

        return $this->success(
            $this->manualPaginateSessions($sessions, $page, $perPage),
            __('Sessions retrieved successfully')
        );
    }

    /**
     * Get today's sessions (all types).
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();
        $sessions = [];
        $studentProfileId = $user->studentProfile?->id;

        // Quran sessions
        $quranSessions = QuranSession::where('student_id', $user->id)
            ->whereDate('scheduled_at', $today)
            ->with(['quranTeacher', 'individualCircle', 'circle'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($quranSessions as $session) {
            $sessions[] = $this->formatSession($session, 'quran');
        }

        // Academic sessions
        $academicSessions = AcademicSession::where('student_id', $user->id)
            ->whereDate('scheduled_at', $today)
            ->with(['academicTeacher.user', 'academicSubscription'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($academicSessions as $session) {
            $sessions[] = $this->formatSession($session, 'academic');
        }

        // Interactive sessions (use student_id from StudentProfile, not user_id)
        if ($studentProfileId) {
            $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($studentProfileId) {
                $q->where('student_id', $studentProfileId);
            })
                ->whereDate('scheduled_at', $today)
                ->with(['course.assignedTeacher.user'])
                ->orderBy('scheduled_at')
                ->get();

            foreach ($interactiveSessions as $session) {
                $sessions[] = $this->formatSession($session, 'interactive');
            }
        }

        // Sort by time (ascending for today)
        $sessions = $this->sortSessionsByTime($sessions, true);

        return $this->success([
            'date' => $today->toDateString(),
            'sessions' => $sessions,
            'count' => count($sessions),
        ], __('Today\'s sessions retrieved successfully'));
    }

    /**
     * Get upcoming sessions (all types).
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = now();
        $endDate = $now->copy()->addDays(14);
        $sessions = [];
        $studentProfileId = $user->studentProfile?->id;

        // Quran sessions
        $quranSessions = QuranSession::where('student_id', $user->id)
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['quranTeacher'])
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get();

        foreach ($quranSessions as $session) {
            $sessions[] = $this->formatSession($session, 'quran');
        }

        // Academic sessions
        $academicSessions = AcademicSession::where('student_id', $user->id)
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['academicTeacher.user', 'academicSubscription'])
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get();

        foreach ($academicSessions as $session) {
            $sessions[] = $this->formatSession($session, 'academic');
        }

        // Interactive sessions (use student_id from StudentProfile, not user_id)
        if ($studentProfileId) {
            $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($studentProfileId) {
                $q->where('student_id', $studentProfileId);
            })
                ->where('scheduled_at', '>', $now)
                ->where('scheduled_at', '<=', $endDate)
                ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
                ->with(['course.assignedTeacher.user'])
                ->orderBy('scheduled_at')
                ->limit(20)
                ->get();

            foreach ($interactiveSessions as $session) {
                $sessions[] = $this->formatSession($session, 'interactive');
            }
        }

        // Sort by time (ascending for upcoming)
        $sessions = $this->sortSessionsByTime($sessions, true);

        return $this->success([
            'sessions' => array_slice($sessions, 0, 20),
            'from_date' => $now->toDateString(),
            'to_date' => $endDate->toDateString(),
        ], __('Upcoming sessions retrieved successfully'));
    }

    /**
     * Get Quran sessions.
     */
    protected function getQuranSessions(int $userId, ?string $status, ?string $dateFrom, ?string $dateTo): array
    {
        $query = QuranSession::where('student_id', $userId)
            ->with(['quranTeacher']);

        if ($status) {
            $query->where('status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('scheduled_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('scheduled_at', '<=', $dateTo);
        }

        return $query->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(fn ($s) => $this->formatSession($s, 'quran'))
            ->toArray();
    }

    /**
     * Get Academic sessions.
     */
    protected function getAcademicSessions(int $userId, ?string $status, ?string $dateFrom, ?string $dateTo): array
    {
        $query = AcademicSession::where('student_id', $userId)
            ->with(['academicTeacher.user', 'academicSubscription']);

        if ($status) {
            $query->where('status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('scheduled_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('scheduled_at', '<=', $dateTo);
        }

        return $query->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(fn ($s) => $this->formatSession($s, 'academic'))
            ->toArray();
    }

    /**
     * Get Interactive sessions.
     *
     * @param  int  $studentProfileId  The StudentProfile ID (not User ID)
     */
    protected function getInteractiveSessions(int $studentProfileId, ?string $status, ?string $dateFrom, ?string $dateTo): array
    {
        $query = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($studentProfileId) {
            $q->where('student_id', $studentProfileId);
        })->with(['course.assignedTeacher.user']);

        if ($status) {
            $query->where('status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('scheduled_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('scheduled_at', '<=', $dateTo);
        }

        return $query->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(fn ($s) => $this->formatSession($s, 'interactive'))
            ->toArray();
    }
}
