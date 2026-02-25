<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Traits\Api\PaginatesResults;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\Unified\UnifiedSessionFetchingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Student Session API Controller
 *
 * Provides session data for students including list, today's sessions,
 * upcoming sessions, details, and feedback submission.
 *
 * Uses UnifiedSessionFetchingService for consistent session fetching.
 */
class SessionController extends Controller
{
    use ApiResponses, PaginatesResults;

    public function __construct(
        private UnifiedSessionFetchingService $sessionService
    ) {}

    /**
     * Get all sessions for the student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();
        $academyId = $academy?->id;

        // Get filter parameters
        $type = $request->get('type');
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Build types array
        $types = $type ? [$type] : ['quran', 'academic', 'interactive'];

        // Parse status if provided
        $sessionStatus = $status ? SessionStatus::tryFrom($status) : null;

        // Parse dates
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        // Use unified service
        $sessions = $this->sessionService->getForStudents(
            studentIds: [$user->id],
            academyId: $academyId,
            status: $sessionStatus,
            types: $types,
            from: $from,
            to: $to,
            useCache: false
        );

        // Format for API and sort newest first
        $formattedSessions = $this->formatSessionsForApi($sessions->sortByDesc('scheduled_at')->values());

        // Paginate using helper
        $result = PaginationHelper::paginateArray($formattedSessions, $request);

        return $this->success([
            'sessions' => $result['items'],
            'pagination' => $result['pagination'],
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Get today's sessions.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();
        $academyId = $academy?->id;

        $sessions = $this->sessionService->getToday([$user->id], $academyId);
        $formattedSessions = $this->formatSessionsForApi($sessions);

        return $this->success([
            'date' => now()->toDateString(),
            'sessions' => $formattedSessions,
            'count' => count($formattedSessions),
        ], __('Today\'s sessions retrieved successfully'));
    }

    /**
     * Get upcoming sessions.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();
        $academyId = $academy?->id;

        $now = now();
        $endDate = $now->copy()->addDays(14);

        $sessions = $this->sessionService->getUpcoming([$user->id], $academyId, 14);
        $formattedSessions = $this->formatSessionsForApi($sessions->take(20));

        return $this->success([
            'sessions' => $formattedSessions,
            'from_date' => $now->toDateString(),
            'to_date' => $endDate->toDateString(),
        ], __('Upcoming sessions retrieved successfully'));
    }

    /**
     * Get a specific session.
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $session = $this->getSessionDetail($user->id, $type, $id);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        return $this->success([
            'session' => $session,
        ], __('Session retrieved successfully'));
    }

    /**
     * Submit feedback for a session.
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
        $success = $this->submitSessionFeedback(
            $user->id,
            $type,
            $id,
            $request->rating,
            $request->feedback
        );

        if (! $success) {
            return $this->error(
                __('Session not found, not completed, or feedback already submitted.'),
                400,
                'FEEDBACK_SUBMISSION_FAILED'
            );
        }

        return $this->success([
            'rating' => $request->rating,
            'feedback' => $request->feedback,
        ], __('Feedback submitted successfully'));
    }

    /**
     * Format unified sessions for API response.
     */
    private function formatSessionsForApi($sessions): array
    {
        return $sessions->map(function ($session) {
            return [
                'id' => $session['id'],
                'type' => $session['type'],
                'title' => $session['title'],
                'status' => $session['status'],
                'status_label' => $session['status_label'],
                'scheduled_at' => $session['scheduled_at']?->toISOString(),
                'duration_minutes' => $session['duration_minutes'],
                'teacher' => $session['teacher_name'] ? [
                    'name' => $session['teacher_name'],
                    'avatar' => $session['teacher_avatar'],
                ] : null,
                'can_join' => $session['can_join'],
                'has_meeting' => ! empty($session['meeting_link']),
            ];
        })->toArray();
    }

    /**
     * Get a specific session with details.
     */
    private function getSessionDetail(int $studentId, string $type, int $sessionId): ?array
    {
        $session = match ($type) {
            'quran' => QuranSession::where('id', $sessionId)
                ->where('student_id', $studentId)
                ->with([
                    'quranTeacher',
                    'individualCircle',
                    'circle',
                    'attendances' => fn ($q) => $q->where('user_id', $studentId),
                ])
                ->first(),
            'quran_group' => QuranSession::where('id', $sessionId)
                ->whereHas('circle.enrollments', fn ($q) => $q->where('student_id', $studentId))
                ->with([
                    'quranTeacher',
                    'circle',
                    'attendances' => fn ($q) => $q->where('user_id', $studentId),
                ])
                ->first(),
            'academic' => AcademicSession::where('id', $sessionId)
                ->where('student_id', $studentId)
                ->with([
                    'academicTeacher.user',
                    'academicSubscription',
                    'attendances' => fn ($q) => $q->where('user_id', $studentId),
                ])
                ->first(),
            'interactive' => InteractiveCourseSession::where('id', $sessionId)
                ->whereHas('course.enrollments', fn ($q) => $q->where('user_id', $studentId))
                ->with([
                    'course.assignedTeacher.user',
                ])
                ->first(),
            default => null,
        };

        return $session ? $this->formatSessionDetails($session, $type) : null;
    }

    /**
     * Format session details for single view.
     */
    private function formatSessionDetails($session, string $type): array
    {
        $teacher = match ($type) {
            'quran' => $session->quranTeacher,
            'academic' => $session->academicTeacher?->user,
            'interactive' => $session->course?->assignedTeacher?->user,
            default => null,
        };

        $title = match ($type) {
            'quran', 'quran_group' => $session->title ?? __('sessions.default_title_quran'),
            'academic' => $session->title ?? $session->academicSubscription?->subject_name ?? __('sessions.default_title_academic'),
            'interactive' => $session->title ?? $session->course?->title ?? __('sessions.default_title_interactive'),
            default => __('sessions.default_title_generic'),
        };

        $base = [
            'id' => $session->id,
            'type' => $type,
            'title' => $title,
            'status' => $session->status->value ?? $session->status,
            'status_label' => $session->status->label ?? $session->status,
            'scheduled_at' => $session->scheduled_at?->toISOString(),
            'duration_minutes' => $session->duration_minutes ?? 45,
            'teacher' => $teacher ? [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'avatar' => $teacher->avatar ? asset('storage/'.$teacher->avatar) : null,
            ] : null,
            'can_join' => $this->canJoin($session),
            'has_meeting' => $session->meeting !== null,
            'description' => $session->description,
            'notes' => $session->notes ?? $session->teacher_notes ?? null,
            'student_rating' => $session->student_rating,
            'student_feedback' => $session->student_feedback,
        ];

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
     * Submit feedback for a session.
     */
    private function submitSessionFeedback(int $studentId, string $type, int $sessionId, int $rating, ?string $feedback): bool
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
                ->whereHas('course.enrollments', fn ($q) => $q->where('user_id', $studentId))
                ->where('status', SessionStatus::COMPLETED->value)
                ->first(),
            default => null,
        };

        if (! $session || $session->student_rating) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($session, $rating, $feedback) {
            $fresh = $session::lockForUpdate()->find($session->id);
            if (! $fresh || $fresh->student_rating) {
                return false;
            }

            $fresh->update([
                'student_rating' => $rating,
                'student_feedback' => $feedback,
            ]);

            return true;
        });
    }

    /**
     * Check if session can be joined.
     */
    private function canJoin($session): bool
    {
        $now = now();
        $sessionTime = $session->scheduled_at;

        if (! $sessionTime) {
            return false;
        }

        $joinStart = $sessionTime->copy()->subMinutes(10);
        $duration = $session->duration_minutes ?? 60;
        $joinEnd = $sessionTime->copy()->addMinutes($duration);

        $status = $session->status->value ?? $session->status;

        return $now->between($joinStart, $joinEnd)
            && ! in_array($status, [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value]);
    }
}
