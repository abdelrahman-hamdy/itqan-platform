<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Traits\Api\PaginatesResults;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class SessionController extends Controller
{
    use ApiResponses, PaginatesResults;

    /**
     * Get all sessions for the student.
     *
     * @param Request $request
     * @return JsonResponse
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

        if (!$type || $type === 'quran') {
            $quranSessions = $this->getQuranSessions($user->id, $status, $dateFrom, $dateTo);
            $sessions = array_merge($sessions, $quranSessions);
        }

        if (!$type || $type === 'academic') {
            $academicSessions = $this->getAcademicSessions($user->id, $status, $dateFrom, $dateTo);
            $sessions = array_merge($sessions, $academicSessions);
        }

        if (!$type || $type === 'interactive') {
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
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total,
            ],
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get today's sessions.
     *
     * @param Request $request
     * @return JsonResponse
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
            ->whereDate('scheduled_date', $today)
            ->with(['course.assignedTeacher.user'])
            ->orderBy('scheduled_time')
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
     * Get upcoming sessions.
     *
     * @param Request $request
     * @return JsonResponse
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
            ->whereNotIn('status', ['cancelled', 'completed'])
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
            ->whereNotIn('status', ['cancelled', 'completed'])
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
            ->whereDate('scheduled_date', '>', $now->toDateString())
            ->whereDate('scheduled_date', '<=', $endDate->toDateString())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['course.assignedTeacher.user'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
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
     * Get a specific session.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $session = null;

        switch ($type) {
            case 'quran':
                $session = QuranSession::where('id', $id)
                    ->where('student_id', $user->id)
                    ->with([
                        'quranTeacher',
                        'individualCircle',
                        'circle',
                        'meeting',
                        'attendances' => function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        },
                    ])
                    ->first();
                break;

            case 'academic':
                $session = AcademicSession::where('id', $id)
                    ->where('student_id', $user->id)
                    ->with([
                        'academicTeacher.user',
                        'academicSubscription.subject',
                        'meeting',
                        'attendances' => function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        },
                    ])
                    ->first();
                break;

            case 'interactive':
                $session = InteractiveCourseSession::where('id', $id)
                    ->whereHas('course.enrollments', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->with([
                        'course.assignedTeacher.user',
                        'meeting',
                    ])
                    ->first();
                break;
        }

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        return $this->success([
            'session' => $this->formatSessionDetails($session, $type),
        ], __('Session retrieved successfully'));
    }

    /**
     * Submit feedback for a session.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function submitFeedback(Request $request, string $type, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $session = null;

        switch ($type) {
            case 'quran':
                $session = QuranSession::where('id', $id)
                    ->where('student_id', $user->id)
                    ->where('status', 'completed')
                    ->first();
                break;

            case 'academic':
                $session = AcademicSession::where('id', $id)
                    ->where('student_id', $user->id)
                    ->where('status', 'completed')
                    ->first();
                break;

            case 'interactive':
                $session = InteractiveCourseSession::where('id', $id)
                    ->whereHas('course.enrollments', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->where('status', 'completed')
                    ->first();
                break;
        }

        if (!$session) {
            return $this->notFound(__('Session not found or not completed yet.'));
        }

        // Check if already submitted feedback
        if ($session->student_rating) {
            return $this->error(
                __('Feedback already submitted for this session.'),
                400,
                'FEEDBACK_ALREADY_SUBMITTED'
            );
        }

        $session->update([
            'student_rating' => $request->rating,
            'student_feedback' => $request->feedback,
        ]);

        return $this->success([
            'rating' => $request->rating,
            'feedback' => $request->feedback,
        ], __('Feedback submitted successfully'));
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
            ->map(fn($s) => $this->formatSession($s, 'quran'))
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
            ->map(fn($s) => $this->formatSession($s, 'academic'))
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
            $query->whereDate('scheduled_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('scheduled_date', '<=', $dateTo);
        }

        return $query->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->get()
            ->map(fn($s) => $this->formatSession($s, 'interactive'))
            ->toArray();
    }

    /**
     * Format a session for the API response.
     */
    protected function formatSession($session, string $type): array
    {
        $scheduledAt = $type === 'interactive'
            ? Carbon::parse($session->scheduled_date . ' ' . $session->scheduled_time)->toISOString()
            : $session->scheduled_at?->toISOString();

        $teacher = match ($type) {
            'quran' => $session->quranTeacher, // QuranSession::quranTeacher returns User directly
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
            'can_join' => $this->canJoin($session, $type),
            'has_meeting' => $session->meeting !== null,
        ];
    }

    /**
     * Format session details for single view.
     */
    protected function formatSessionDetails($session, string $type): array
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
     * Check if session can be joined.
     */
    protected function canJoin($session, string $type): bool
    {
        $now = now();

        if ($type === 'interactive') {
            $sessionTime = Carbon::parse($session->scheduled_date . ' ' . $session->scheduled_time);
        } else {
            $sessionTime = $session->scheduled_at;
        }

        if (!$sessionTime) {
            return false;
        }

        $joinStart = $sessionTime->copy()->subMinutes(10);
        $duration = $session->duration_minutes ?? 45;
        $joinEnd = $sessionTime->copy()->addMinutes($duration);

        $status = $session->status->value ?? $session->status;

        return $now->between($joinStart, $joinEnd)
            && !in_array($status, ['cancelled', 'completed']);
    }
}
