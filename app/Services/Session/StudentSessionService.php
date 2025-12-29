<?php

namespace App\Services\Session;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for handling student session queries and operations
 */
class StudentSessionService
{
    /**
     * Get all sessions for a student with filters
     */
    public function getStudentSessions(
        int $studentId,
        ?string $type = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $sessions = [];

        if (!$type || $type === 'quran') {
            $quranSessions = $this->getQuranSessions($studentId, $status, $dateFrom, $dateTo);
            $sessions = array_merge($sessions, $quranSessions);
        }

        if (!$type || $type === 'academic') {
            $academicSessions = $this->getAcademicSessions($studentId, $status, $dateFrom, $dateTo);
            $sessions = array_merge($sessions, $academicSessions);
        }

        if (!$type || $type === 'interactive') {
            $interactiveSessions = $this->getInteractiveSessions($studentId, $status, $dateFrom, $dateTo);
            $sessions = array_merge($sessions, $interactiveSessions);
        }

        // Sort by scheduled time (newest first)
        usort($sessions, function ($a, $b) {
            return strtotime($b['scheduled_at']) <=> strtotime($a['scheduled_at']);
        });

        return $sessions;
    }

    /**
     * Get today's sessions for a student
     */
    public function getTodaySessions(int $studentId): array
    {
        $today = Carbon::today();
        $sessions = [];

        // Quran sessions
        $quranSessions = QuranSession::where('student_id', $studentId)
            ->whereDate('scheduled_at', $today)
            ->with(['quranTeacher', 'individualCircle', 'circle'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($quranSessions as $session) {
            $sessions[] = $this->formatSession($session, 'quran');
        }

        // Academic sessions
        $academicSessions = AcademicSession::where('student_id', $studentId)
            ->whereDate('scheduled_at', $today)
            ->with(['academicTeacher.user', 'academicSubscription'])
            ->orderBy('scheduled_at')
            ->get();

        foreach ($academicSessions as $session) {
            $sessions[] = $this->formatSession($session, 'academic');
        }

        // Interactive sessions
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($studentId) {
            $q->where('user_id', $studentId);
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

        return $sessions;
    }

    /**
     * Get upcoming sessions for a student
     */
    public function getUpcomingSessions(int $studentId, int $days = 14, int $limit = 20): array
    {
        $now = now();
        $endDate = $now->copy()->addDays($days);
        $sessions = [];

        // Quran sessions
        $quranSessions = QuranSession::where('student_id', $studentId)
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['quranTeacher'])
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($quranSessions as $session) {
            $sessions[] = $this->formatSession($session, 'quran');
        }

        // Academic sessions
        $academicSessions = AcademicSession::where('student_id', $studentId)
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['academicTeacher.user', 'academicSubscription'])
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($academicSessions as $session) {
            $sessions[] = $this->formatSession($session, 'academic');
        }

        // Interactive sessions
        $interactiveSessions = InteractiveCourseSession::whereHas('course.enrollments', function ($q) use ($studentId) {
            $q->where('user_id', $studentId);
        })
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $endDate)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value])
            ->with(['course.assignedTeacher.user'])
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($interactiveSessions as $session) {
            $sessions[] = $this->formatSession($session, 'interactive');
        }

        // Sort by time
        usort($sessions, function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

        return array_slice($sessions, 0, $limit);
    }

    /**
     * Get a specific session with details
     */
    public function getSessionDetail(int $studentId, string $type, int $sessionId): ?array
    {
        $session = match ($type) {
            'quran' => QuranSession::where('id', $sessionId)
                ->where('student_id', $studentId)
                ->with([
                    'quranTeacher',
                    'individualCircle',
                    'circle',
                    'meeting',
                    'attendances' => function ($q) use ($studentId) {
                        $q->where('user_id', $studentId);
                    },
                ])
                ->first(),
            'academic' => AcademicSession::where('id', $sessionId)
                ->where('student_id', $studentId)
                ->with([
                    'academicTeacher.user',
                    'academicSubscription.subject',
                    'meeting',
                    'attendances' => function ($q) use ($studentId) {
                        $q->where('user_id', $studentId);
                    },
                ])
                ->first(),
            'interactive' => InteractiveCourseSession::where('id', $sessionId)
                ->whereHas('course.enrollments', function ($q) use ($studentId) {
                    $q->where('user_id', $studentId);
                })
                ->with([
                    'course.assignedTeacher.user',
                    'meeting',
                ])
                ->first(),
            default => null,
        };

        return $session ? $this->formatSessionDetails($session, $type) : null;
    }

    /**
     * Submit feedback for a session
     */
    public function submitFeedback(int $studentId, string $type, int $sessionId, int $rating, ?string $feedback): bool
    {
        $session = match ($type) {
            'quran' => QuranSession::where('id', $sessionId)
                ->where('student_id', $studentId)
                ->where('status', SessionStatus::COMPLETED->value)
                ->first(),
            'academic' => AcademicSession::where('id', $sessionId)
                ->where('student_id', $studentId)
                ->where('status', SessionStatus::COMPLETED->value)
                ->first(),
            'interactive' => InteractiveCourseSession::where('id', $sessionId)
                ->whereHas('course.enrollments', function ($q) use ($studentId) {
                    $q->where('user_id', $studentId);
                })
                ->where('status', SessionStatus::COMPLETED->value)
                ->first(),
            default => null,
        };

        if (!$session || $session->student_rating) {
            return false;
        }

        $session->update([
            'student_rating' => $rating,
            'student_feedback' => $feedback,
        ]);

        return true;
    }

    /**
     * Get Quran sessions
     */
    private function getQuranSessions(int $userId, ?string $status, ?string $dateFrom, ?string $dateTo): array
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
     * Get Academic sessions
     */
    private function getAcademicSessions(int $userId, ?string $status, ?string $dateFrom, ?string $dateTo): array
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
     * Get Interactive sessions
     */
    private function getInteractiveSessions(int $userId, ?string $status, ?string $dateFrom, ?string $dateTo): array
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

    /**
     * Format a session for API response
     */
    private function formatSession($session, string $type): array
    {
        $scheduledAt = $session->scheduled_at?->toISOString();

        $teacher = match ($type) {
            'quran' => $session->quranTeacher,
            'academic' => $session->academicTeacher?->user,
            'interactive' => $session->course?->assignedTeacher?->user,
            default => null,
        };

        $title = match ($type) {
            'quran' => $session->title ?? 'جلسة قرآنية',
            'academic' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
            'interactive' => $session->title ?? $session->course?->title ?? 'جلسة تفاعلية',
            default => 'جلسة',
        };

        return [
            'id' => $session->id,
            'type' => $type,
            'title' => $title,
            'status' => $session->status->value ?? $session->status,
            'status_label' => $session->status->label ?? $session->status,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $session->duration_minutes ?? 45,
            'teacher' => $teacher ? [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'avatar' => $teacher->avatar ? asset('storage/' . $teacher->avatar) : null,
            ] : null,
            'can_join' => $this->canJoin($session),
            'has_meeting' => $session->meeting !== null,
        ];
    }

    /**
     * Format session details for single view
     */
    private function formatSessionDetails($session, string $type): array
    {
        $base = $this->formatSession($session, $type);

        // Add more details
        $base['description'] = $session->description;
        $base['notes'] = $session->notes ?? $session->teacher_notes ?? null;
        $base['student_rating'] = $session->student_rating;
        $base['student_feedback'] = $session->student_feedback;

        // Meeting info
        if ($session->meeting) {
            $base['meeting'] = [
                'id' => $session->meeting->id,
                'room_name' => $session->meeting->room_name,
                'status' => $session->meeting->status,
            ];
        }

        // Type-specific details
        if ($type === 'quran') {
            $base['quran_details'] = [
                'from_surah' => $session->from_surah,
                'from_verse' => $session->from_verse,
                'to_surah' => $session->to_surah,
                'to_verse' => $session->to_verse,
                'pages_count' => $session->pages_count,
                'memorization_quality' => $session->memorization_quality,
                'tajweed_quality' => $session->tajweed_quality,
            ];
        }

        if ($type === 'academic') {
            $base['academic_details'] = [
                'subject' => $session->academicSubscription?->subject_name,
                'homework' => $session->homework,
                'homework_due_date' => $session->homework_due_date?->toISOString(),
                'topics_covered' => $session->topics_covered,
            ];
        }

        if ($type === 'interactive') {
            $base['course_details'] = [
                'course_id' => $session->course_id,
                'course_title' => $session->course?->title,
                'session_number' => $session->session_number,
                'total_sessions' => $session->course?->total_sessions,
            ];
        }

        // Attendance info
        if (isset($session->attendances) && $session->attendances->isNotEmpty()) {
            $attendance = $session->attendances->first();
            $base['attendance'] = [
                'status' => $attendance->status,
                'attended_at' => $attendance->attended_at?->toISOString(),
                'left_at' => $attendance->left_at?->toISOString(),
                'duration_minutes' => $attendance->duration_minutes,
            ];
        }

        return $base;
    }

    /**
     * Check if session can be joined
     */
    private function canJoin($session): bool
    {
        $now = now();
        $sessionTime = $session->scheduled_at;

        if (!$sessionTime) {
            return false;
        }

        $joinStart = $sessionTime->copy()->subMinutes(10);
        $duration = $session->duration_minutes ?? 45;
        $joinEnd = $sessionTime->copy()->addMinutes($duration);

        $status = $session->status->value ?? $session->status;

        return $now->between($joinStart, $joinEnd)
            && !in_array($status, [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value]);
    }
}
