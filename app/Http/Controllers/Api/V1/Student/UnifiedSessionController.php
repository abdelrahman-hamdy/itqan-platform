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

        if (! $type || $type === 'quran') {
            $quranSessions = $this->getQuranSessions($user->id, $status, $dateFrom, $dateTo);
            $sessions = array_merge($sessions, $quranSessions);
        }

        if (! $type || $type === 'academic') {
            $academicSessions = $this->getAcademicSessions($user->id, $status, $dateFrom, $dateTo);
            $sessions = array_merge($sessions, $academicSessions);
        }

        if (! $type || $type === 'interactive') {
            $interactiveSessions = $this->getInteractiveSessions($user->id, $status, $dateFrom, $dateTo);
            $sessions = array_merge($sessions, $interactiveSessions);
        }

        // Sort by scheduled time
        usort($sessions, function ($a, $b) {
            return strtotime($b['scheduled_at']) <=> strtotime($a['scheduled_at']);
        });

        // Manual pagination
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);
        $total = count($sessions);
        $offset = ($page - 1) * $perPage;
        $paginatedSessions = array_slice($sessions, $offset, $perPage);

        return $this->success([
            'sessions' => $paginatedSessions,
            'pagination' => PaginationHelper::fromArray($total, $page, $perPage),
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get today's sessions (all types).
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();
        $sessions = [];

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

        // Interactive sessions
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->whereDate('scheduled_at', $today)
            ->with(['course.assignedTeacher.user'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($interactiveSessions as $session) {
            $sessions[] = $this->formatSession($session, 'interactive');
        }

        // Sort by time
        usort($sessions, function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

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

        // Interactive sessions
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($user) {
            $q->where('user_id', $user->id);
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

        // Sort by time
        usort($sessions, function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

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
     */
    protected function getInteractiveSessions(int $userId, ?string $status, ?string $dateFrom, ?string $dateTo): array
    {
        $query = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($userId) {
            $q->where('user_id', $userId);
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
