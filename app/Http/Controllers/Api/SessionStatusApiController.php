<?php

namespace App\Http\Controllers\Api;

use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionReport;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Services\Attendance\AcademicReportService;
use App\Services\Attendance\InteractiveReportService;
use App\Services\Attendance\QuranReportService;
use App\Services\LiveKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Web API Controller for session status endpoints
 *
 * Handles session status and join eligibility for LiveKit meetings.
 * Used by web routes (routes/web/api.php) for in-browser session status checks.
 *
 * Provides type-specific methods:
 * - academicSessionStatus() - For academic sessions
 * - quranSessionStatus() - For Quran sessions
 * - generalSessionStatus() - For polymorphic session resolution
 */
class SessionStatusApiController extends Controller
{
    use ApiResponses;

    private const DEFAULT_PREPARATION_MINUTES = 15;

    private const DEFAULT_ENDING_BUFFER_MINUTES = 5;

    private const DEFAULT_DURATION_MINUTES = 60;

    /**
     * Get academic session status
     */
    public function academicSessionStatus(Request $request, int $sessionId): JsonResponse
    {
        if (! auth()->check()) {
            return $this->unauthenticatedResponse();
        }

        $user = $request->user();
        $userType = $user->hasRole(UserType::ACADEMIC_TEACHER->value) ? UserType::ACADEMIC_TEACHER->value : UserType::STUDENT->value;
        $session = AcademicSession::findOrFail($sessionId);

        // Get preparation minutes from session model (reads from academy settings)
        $statusData = $session->getStatusDisplayData();
        $preparationMinutes = $statusData['preparation_minutes'] ?? self::DEFAULT_PREPARATION_MINUTES;

        return $this->buildStatusResponse($session, $userType, 'academic', $preparationMinutes);
    }

    /**
     * Get Quran session status
     */
    public function quranSessionStatus(Request $request, int $sessionId): JsonResponse
    {
        if (! auth()->check()) {
            return $this->unauthenticatedResponse();
        }

        $user = $request->user();
        $userType = $user->hasRole(UserType::QURAN_TEACHER->value) ? UserType::QURAN_TEACHER->value : UserType::STUDENT->value;
        $session = QuranSession::findOrFail($sessionId);

        // Get preparation minutes from session model (reads from academy settings)
        $statusData = $session->getStatusDisplayData();
        $preparationMinutes = $statusData['preparation_minutes'] ?? self::DEFAULT_PREPARATION_MINUTES;

        return $this->buildStatusResponse($session, $userType, 'quran', $preparationMinutes);
    }

    /**
     * Get academic session attendance status
     */
    public function academicAttendanceStatus(Request $request, int $sessionId): JsonResponse
    {
        if (! auth()->check()) {
            return $this->unauthorized();
        }

        $session = AcademicSession::with('meetingAttendances')->findOrFail($sessionId);

        return $this->buildAttendanceResponse($session, $request->user());
    }

    /**
     * Get Quran session attendance status
     */
    public function quranAttendanceStatus(Request $request, int $sessionId): JsonResponse
    {
        if (! auth()->check()) {
            return $this->unauthorized();
        }

        $session = QuranSession::with('meetingAttendances')->findOrFail($sessionId);

        return $this->buildAttendanceResponse($session, $request->user());
    }

    /**
     * Build unauthenticated response
     */
    private function unauthenticatedResponse(): JsonResponse
    {
        return $this->success([
            'message' => 'يجب تسجيل الدخول لعرض حالة الجلسة',
            'status' => 'unauthenticated',
            'can_join' => false,
            'button_text' => 'يجب تسجيل الدخول',
            'button_class' => 'bg-gray-400 cursor-not-allowed',
        ], false, 401);
    }

    /**
     * Build session status response
     */
    private function buildStatusResponse(
        $session,
        string $userType,
        string $sessionType,
        int $preparationMinutes = self::DEFAULT_PREPARATION_MINUTES
    ): JsonResponse {
        $endingBufferMinutes = self::DEFAULT_ENDING_BUFFER_MINUTES;
        $now = now();
        $canJoinMeeting = false;

        // Check if user can join meeting based on timing and status
        $isTeacher = in_array($userType, [UserType::ACADEMIC_TEACHER->value, UserType::QURAN_TEACHER->value]);
        $joinableStatuses = [
            SessionStatus::READY,
            SessionStatus::ONGOING,
            SessionStatus::SCHEDULED,
        ];

        if ($isTeacher && in_array($session->status, $joinableStatuses)) {
            if ($session->scheduled_at) {
                $preparationStart = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
                $sessionEnd = $session->scheduled_at->copy()->addMinutes(
                    ($session->duration_minutes ?? self::DEFAULT_DURATION_MINUTES) + $endingBufferMinutes
                );
                if ($now->gte($preparationStart) && $now->lt($sessionEnd)) {
                    $canJoinMeeting = true;
                }
            }
        } elseif ($userType === UserType::STUDENT->value && in_array($session->status, $joinableStatuses)) {
            if ($session->scheduled_at) {
                $sessionEnd = $session->scheduled_at->copy()->addMinutes(
                    ($session->duration_minutes ?? self::DEFAULT_DURATION_MINUTES) + $endingBufferMinutes
                );
                if ($now->lt($sessionEnd)) {
                    $canJoinMeeting = true;
                }
            }
        }

        // Determine message and button state based on session status
        $statusValue = $session->status instanceof SessionStatus
            ? $session->status->value
            : $session->status;

        [$message, $buttonText, $buttonClass, $canJoinMeeting] = $this->getStatusDisplay(
            $session,
            $canJoinMeeting,
            $preparationMinutes
        );

        return $this->success([
            'status' => $statusValue,
            'message' => $message,
            'button_text' => $buttonText,
            'button_class' => $buttonClass,
            'can_join' => $canJoinMeeting,
            'session_type' => $sessionType,
        ]);
    }

    /**
     * Get display values based on session status
     */
    private function getStatusDisplay($session, bool $canJoinMeeting, int $preparationMinutes): array
    {
        $message = '';
        $buttonText = '';
        $buttonClass = '';

        switch ($session->status) {
            case SessionStatus::READY:
                $message = 'الجلسة جاهزة';
                $buttonText = 'غير متاح';
                $buttonClass = 'bg-gray-400 cursor-not-allowed';
                $canJoinMeeting = false;
                break;

            case SessionStatus::ONGOING:
                $message = 'الجلسة جارية حالياً';
                $buttonText = $canJoinMeeting ? 'انضم للجلسة' : 'غير متاح';
                $buttonClass = $canJoinMeeting
                    ? 'bg-green-500 hover:bg-green-600'
                    : 'bg-gray-400 cursor-not-allowed';
                break;

            case SessionStatus::COMPLETED:
                $message = 'تم إنهاء الجلسة';
                $buttonText = 'الجلسة منتهية';
                $buttonClass = 'bg-gray-400 cursor-not-allowed';
                $canJoinMeeting = false;
                break;

            case SessionStatus::CANCELLED:
                $message = 'تم إلغاء الجلسة';
                $buttonText = 'الجلسة ملغية';
                $buttonClass = 'bg-red-400 cursor-not-allowed';
                $canJoinMeeting = false;
                break;

            case SessionStatus::SCHEDULED:
                if ($canJoinMeeting) {
                    $message = 'يمكنك الانضمام للجلسة الآن';
                    $buttonText = 'انضم للجلسة';
                    $buttonClass = 'bg-green-500 hover:bg-green-600';
                } else {
                    if ($session->scheduled_at) {
                        $preparationTime = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
                        $timeData = formatTimeRemaining($preparationTime);
                        $message = ! $timeData['is_past']
                            ? "الجلسة ستكون متاحة خلال {$timeData['formatted']}"
                            : 'الجلسة ستكون متاحة قريباً';
                    } else {
                        $message = 'الجلسة محجوزة ولكن لم يتم تحديد موعد';
                    }
                    $buttonText = 'في انتظار الجلسة';
                    $buttonClass = 'bg-blue-400 cursor-not-allowed';
                    $canJoinMeeting = false;
                }
                break;

            case SessionStatus::UNSCHEDULED:
                $message = 'الجلسة غير مجدولة بعد';
                $buttonText = 'في انتظار الجدولة';
                $buttonClass = 'bg-gray-400 cursor-not-allowed';
                $canJoinMeeting = false;
                break;

            default:
                $message = 'حالة غير معروفة';
                $buttonText = 'غير متاح';
                $buttonClass = 'bg-gray-400 cursor-not-allowed';
                $canJoinMeeting = false;
        }

        return [$message, $buttonText, $buttonClass, $canJoinMeeting];
    }

    /**
     * Build attendance status response
     */
    private function buildAttendanceResponse($session, $user): JsonResponse
    {
        $attendance = $session->meetingAttendances
            ->where('user_id', $user->id)
            ->first();

        if ($attendance) {
            return $this->success([
                'has_attendance' => true,
                'attendance_status' => $attendance->attendance_status,
                'first_join_time' => $attendance->first_join_time?->toISOString(),
                'last_leave_time' => $attendance->last_leave_time?->toISOString(),
                'total_duration_minutes' => $attendance->total_duration_minutes,
                'attendance_percentage' => $attendance->attendance_percentage,
            ]);
        }

        return $this->success([
            'has_attendance' => false,
            'attendance_status' => null,
            'message' => 'لم يتم تسجيل حضور بعد',
        ]);
    }

    /**
     * General session status API with smart polymorphic resolution
     * Supports all session types (Academic, Quran, Interactive) with unified response format
     */
    public function generalSessionStatus(Request $request, int $sessionId): JsonResponse
    {
        if (! auth()->check()) {
            return $this->unauthenticatedResponse();
        }

        $user = $request->user();
        $sessionType = $request->query('type'); // 'academic', 'quran', or 'interactive'
        $session = $this->resolveSession($sessionId, $user, $sessionType);

        if (! $session) {
            abort(404, 'الجلسة غير موجودة');
        }

        // Get configuration based on session type
        [$preparationMinutes, $endingBufferMinutes] = $this->getSessionConfiguration($session);

        // Determine user type
        $userType = $this->getUserType($user);

        // Check for auto-complete
        $now = now();
        $sessionEndTime = null;
        $hasExpired = false;

        if ($session->scheduled_at && in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            $sessionEndTime = $session->scheduled_at->copy()->addMinutes(
                ($session->duration_minutes ?? self::DEFAULT_DURATION_MINUTES) + $endingBufferMinutes
            );
            $hasExpired = $now->gte($sessionEndTime);

            if ($hasExpired) {
                $this->autoCompleteSession($session, $sessionEndTime);
            }
        }

        // Determine if user can join
        $canJoinMeeting = $this->canUserJoinMeeting($session, $hasExpired, $preparationMinutes, $endingBufferMinutes);

        // Get status display
        [$message, $buttonText, $buttonClass] = $this->getGeneralStatusDisplay(
            $session,
            $userType,
            $canJoinMeeting,
            $preparationMinutes
        );

        return $this->success([
            'status' => $session->status instanceof SessionStatus
                ? $session->status->value
                : $session->status,
            'can_join' => $canJoinMeeting,
            'message' => $message,
            'button_text' => $buttonText,
            'button_class' => $buttonClass,
            'session_info' => [
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes,
                'preparation_minutes' => $preparationMinutes,
                'ending_buffer_minutes' => $endingBufferMinutes,
                'meeting_room_name' => $session->meeting_room_name,
                'session_end_time' => $sessionEndTime?->toISOString(),
                'has_expired' => $hasExpired,
            ],
        ]);
    }

    /**
     * General attendance status (smart resolution across session types)
     */
    public function generalAttendanceStatus(Request $request, int $sessionId): JsonResponse
    {
        $user = $request->user();
        $sessionType = $request->query('type'); // 'academic', 'quran', or 'interactive'
        $session = $this->resolveSession($sessionId, $user, $sessionType);

        if (! $session) {
            abort(404, 'الجلسة غير موجودة');
        }

        $statusValue = $session->status instanceof SessionStatus
            ? $session->status->value
            : $session->status;

        // Check if user has ever joined this session
        $meetingAttendance = MeetingAttendance::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        $hasEverJoined = $meetingAttendance !== null;

        // Handle session timing and states
        $now = now();
        $sessionStart = $session->scheduled_at;
        $sessionEnd = $sessionStart
            ? $sessionStart->copy()->addMinutes($session->duration_minutes ?? self::DEFAULT_DURATION_MINUTES)
            : null;

        $isBeforeSession = $sessionStart && $now->isBefore($sessionStart);
        $isDuringSession = $sessionStart && $sessionEnd && $now->between($sessionStart, $sessionEnd);
        $isAfterSession = $sessionEnd && $now->isAfter($sessionEnd);

        // Completed or ended sessions
        if ($statusValue === SessionStatus::COMPLETED->value || $isAfterSession) {
            return $this->buildCompletedAttendanceResponse($session, $user, $meetingAttendance, $hasEverJoined);
        }

        // Before session
        if ($isBeforeSession) {
            return $this->success([
                'is_currently_in_meeting' => false,
                'attendance_status' => 'not_started',
                'attendance_percentage' => '0.00',
                'duration_minutes' => 0,
                'join_count' => 0,
                'session_state' => 'scheduled',
                'has_ever_joined' => false,
                'minutes_until_start' => max(0, ceil($now->diffInMinutes($sessionStart, false))),
            ]);
        }

        // Active session - use real-time data
        return $this->buildActiveAttendanceResponse($session, $user, $hasEverJoined, $isDuringSession, $statusValue);
    }

    /**
     * Resolve session from ID across all session types
     *
     * @param  string|null  $sessionType  Optional type hint: 'academic', 'quran', or 'interactive'
     */
    private function resolveSession(int $sessionId, $user, ?string $sessionType = null): mixed
    {
        // If session type is explicitly provided, use it directly (most reliable)
        if ($sessionType) {
            return match ($sessionType) {
                'academic' => AcademicSession::find($sessionId),
                'interactive' => InteractiveCourseSession::find($sessionId),
                'quran' => QuranSession::find($sessionId),
                default => null,
            };
        }

        // Fallback: Try to resolve based on user role and session existence
        // This is less reliable when multiple session types have the same ID
        $academicSession = AcademicSession::find($sessionId);
        $quranSession = QuranSession::find($sessionId);
        $interactiveSession = InteractiveCourseSession::find($sessionId);

        // Prefer based on user role to avoid ID conflicts
        if ($user->hasRole(UserType::ACADEMIC_TEACHER->value)) {
            return $academicSession ?: $interactiveSession ?: $quranSession;
        }

        if ($user->hasRole(UserType::QURAN_TEACHER->value)) {
            return $quranSession ?: $interactiveSession ?: $academicSession;
        }

        // For students, try to find which session they're actually enrolled in
        if ($academicSession && $academicSession->student_id === $user->id) {
            return $academicSession;
        }

        if ($quranSession && ($quranSession->student_id === $user->id ||
            ($quranSession->circle && $quranSession->circle->students->contains('id', $user->id)))) {
            return $quranSession;
        }

        if ($interactiveSession && $interactiveSession->course?->enrollments->contains('student_id', $user->id)) {
            return $interactiveSession;
        }

        // Last fallback: return any found session
        return $academicSession ?: $quranSession ?: $interactiveSession;
    }

    /**
     * Get session configuration (preparation and buffer times)
     * Uses the session model's getStatusDisplayData() which reads from academy settings
     */
    private function getSessionConfiguration($session): array
    {
        $statusData = $session->getStatusDisplayData();

        return [
            $statusData['preparation_minutes'] ?? self::DEFAULT_PREPARATION_MINUTES,
            $statusData['ending_buffer_minutes'] ?? self::DEFAULT_ENDING_BUFFER_MINUTES,
        ];
    }

    /**
     * Get user type string
     */
    private function getUserType($user): string
    {
        if ($user->hasRole(UserType::QURAN_TEACHER->value)) {
            return UserType::QURAN_TEACHER->value;
        }
        if ($user->hasRole(UserType::ACADEMIC_TEACHER->value)) {
            return UserType::ACADEMIC_TEACHER->value;
        }

        return UserType::STUDENT->value;
    }

    /**
     * Check if user can join meeting
     */
    private function canUserJoinMeeting($session, bool $hasExpired, int $preparationMinutes, int $endingBufferMinutes): bool
    {
        if ($hasExpired) {
            return false;
        }

        $now = now();

        if (in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            return true;
        }

        if (in_array($session->status, [SessionStatus::ABSENT, SessionStatus::SCHEDULED])) {
            if ($session->scheduled_at) {
                $preparationStart = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
                $sessionEnd = $session->scheduled_at->copy()->addMinutes(
                    ($session->duration_minutes ?? self::DEFAULT_DURATION_MINUTES) + $endingBufferMinutes
                );

                return $now->gte($preparationStart) && $now->lt($sessionEnd);
            }
        }

        return false;
    }

    /**
     * Auto-complete expired session
     */
    private function autoCompleteSession($session, $sessionEndTime): void
    {
        $session->update([
            'status' => SessionStatus::COMPLETED,
            'ended_at' => $sessionEndTime,
            'actual_duration_minutes' => $session->duration_minutes ?? self::DEFAULT_DURATION_MINUTES,
        ]);

        if ($session->meeting_room_name) {
            try {
                $liveKitService = app(LiveKitService::class);
                $liveKitService->endMeeting($session->meeting_room_name);
                Log::info('LiveKit room closed on session auto-complete', [
                    'session_id' => $session->id,
                    'room_name' => $session->meeting_room_name,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to close LiveKit room on auto-complete', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Session auto-completed due to time expiration', [
            'session_id' => $session->id,
            'session_type' => get_class($session),
        ]);
    }

    /**
     * Get general status display values
     */
    private function getGeneralStatusDisplay($session, string $userType, bool $canJoinMeeting, int $preparationMinutes): array
    {
        switch ($session->status) {
            case SessionStatus::READY:
                return [
                    'الجلسة جاهزة - يمكنك الانضمام الآن',
                    'انضم للجلسة',
                    'bg-green-600 hover:bg-green-700',
                ];

            case SessionStatus::ONGOING:
                return [
                    'الجلسة جارية الآن - انضم للمشاركة',
                    'انضمام للجلسة الجارية',
                    'bg-orange-600 hover:bg-orange-700 animate-pulse',
                ];

            case SessionStatus::SCHEDULED:
                if ($canJoinMeeting) {
                    return [
                        'جاري تحضير الاجتماع - يمكنك الانضمام الآن',
                        'انضم للجلسة',
                        'bg-blue-600 hover:bg-blue-700',
                    ];
                }
                $message = $session->scheduled_at
                    ? $this->getWaitingMessage($session->scheduled_at, $preparationMinutes)
                    : 'الجلسة محجوزة ولكن لم يتم تحديد موعد';

                return [$message, 'في انتظار تحضير الاجتماع', 'bg-gray-400 cursor-not-allowed'];

            case SessionStatus::ABSENT:
                return $this->getAbsentStatusDisplay($userType, $canJoinMeeting);

            case SessionStatus::COMPLETED:
                return ['تم إنهاء الجلسة بنجاح', 'الجلسة منتهية', 'bg-gray-400 cursor-not-allowed'];

            case SessionStatus::CANCELLED:
                return ['تم إلغاء الجلسة', 'الجلسة ملغية', 'bg-red-400 cursor-not-allowed'];

            case SessionStatus::UNSCHEDULED:
                return ['الجلسة غير مجدولة بعد', 'في انتظار الجدولة', 'bg-gray-400 cursor-not-allowed'];

            default:
                $statusLabel = $session->status instanceof SessionStatus
                    ? $session->status->label()
                    : (string) $session->status;

                return ['حالة الجلسة: '.$statusLabel, 'غير متاح', 'bg-gray-400 cursor-not-allowed'];
        }
    }

    /**
     * Get waiting message for scheduled sessions
     */
    private function getWaitingMessage($scheduledAt, int $preparationMinutes): string
    {
        $preparationTime = $scheduledAt->copy()->subMinutes($preparationMinutes);
        $timeData = formatTimeRemaining($preparationTime);

        return ! $timeData['is_past']
            ? 'سيتم تحضير الاجتماع خلال '.$timeData['formatted']
            : 'جاري تحضير الاجتماع...';
    }

    /**
     * Get absent status display
     */
    private function getAbsentStatusDisplay(string $userType, bool $canJoinMeeting): array
    {
        if ($canJoinMeeting) {
            if (in_array($userType, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
                return [
                    'الجلسة نشطة - يمكنك بدء أو الانضمام للاجتماع',
                    'انضم للجلسة',
                    'bg-green-600 hover:bg-green-700',
                ];
            }

            return [
                'تم تسجيل غيابك ولكن يمكنك الانضمام الآن',
                'انضم للجلسة (غائب)',
                'bg-yellow-600 hover:bg-yellow-700',
            ];
        }

        if (in_array($userType, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            return ['انتهت فترة الجلسة', 'الجلسة منتهية', 'bg-gray-400 cursor-not-allowed'];
        }

        return ['تم تسجيل غياب الطالب', 'غياب الطالب', 'bg-red-400 cursor-not-allowed'];
    }

    /**
     * Build completed attendance response
     */
    private function buildCompletedAttendanceResponse($session, $user, $meetingAttendance, bool $hasEverJoined): JsonResponse
    {
        $sessionReport = $this->getSessionReport($session, $user);

        if ($sessionReport) {
            $attendanceStatus = $sessionReport->attendance_status ?? 'absent';
            $duration = $sessionReport->actual_attendance_minutes ?? 0;

            if (! $hasEverJoined) {
                $attendanceStatus = 'not_attended';
            } elseif ($duration > 0 && in_array($attendanceStatus, ['left', 'partial'])) {
                $attendanceStatus = 'partial_attendance';
            }

            return $this->success([
                'is_currently_in_meeting' => false,
                'attendance_status' => $attendanceStatus,
                'attendance_percentage' => number_format($sessionReport->attendance_percentage ?? 0, 2),
                'duration_minutes' => $duration,
                'join_count' => $meetingAttendance?->join_count ?? 0,
                'is_late' => $sessionReport->is_late ?? false,
                'late_minutes' => $sessionReport->late_minutes ?? 0,
                'last_updated' => $sessionReport->updated_at,
                'session_state' => 'completed',
                'has_ever_joined' => $hasEverJoined,
            ]);
        }

        return $this->success([
            'is_currently_in_meeting' => false,
            'attendance_status' => $hasEverJoined ? 'not_enough_time' : 'not_attended',
            'attendance_percentage' => '0.00',
            'duration_minutes' => $meetingAttendance?->total_duration_minutes ?? 0,
            'join_count' => $meetingAttendance?->join_count ?? 0,
            'session_state' => 'completed',
            'has_ever_joined' => $hasEverJoined,
        ]);
    }

    /**
     * Get session report based on session type
     */
    private function getSessionReport($session, $user)
    {
        if ($session instanceof AcademicSession) {
            return AcademicSessionReport::where('session_id', $session->id)
                ->where('student_id', $user->id)
                ->first();
        }

        if ($session instanceof InteractiveCourseSession) {
            return InteractiveSessionReport::where('session_id', $session->id)
                ->where('student_id', $user->id)
                ->first();
        }

        return StudentSessionReport::where('session_id', $session->id)
            ->where('student_id', $user->id)
            ->first();
    }

    /**
     * Build active attendance response
     */
    private function buildActiveAttendanceResponse($session, $user, bool $hasEverJoined, bool $isDuringSession, string $statusValue): JsonResponse
    {
        // Resolve the appropriate service based on session type
        if ($session instanceof AcademicSession) {
            $service = app(AcademicReportService::class);
        } elseif ($session instanceof InteractiveCourseSession) {
            $service = app(InteractiveReportService::class);
        } else {
            // QuranSession
            $service = app(QuranReportService::class);
        }

        $status = $service->getCurrentAttendanceStatus($session, $user);

        $status['session_state'] = $isDuringSession ? 'ongoing' : 'scheduled';
        $status['has_ever_joined'] = $hasEverJoined;

        if (! $hasEverJoined && ($statusValue === SessionStatus::SCHEDULED->value || $isDuringSession)) {
            $status['attendance_status'] = 'not_joined_yet';
        }

        return $this->success($status);
    }
}
