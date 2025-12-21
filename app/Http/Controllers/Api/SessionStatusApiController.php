<?php

namespace App\Http\Controllers\Api;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for session status endpoints
 *
 * Handles session status and join eligibility for LiveKit meetings
 */
class SessionStatusApiController extends Controller
{
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
        $userType = $user->hasRole('academic_teacher') ? 'academic_teacher' : 'student';
        $session = AcademicSession::findOrFail($sessionId);

        return $this->buildStatusResponse($session, $userType, 'academic');
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
        $userType = $user->hasRole('quran_teacher') ? 'quran_teacher' : 'student';
        $session = QuranSession::findOrFail($sessionId);

        // For Quran sessions, get preparation minutes from circle if available
        $preparationMinutes = self::DEFAULT_PREPARATION_MINUTES;
        if ($session->circle) {
            $preparationMinutes = $session->circle->preparation_minutes ?? self::DEFAULT_PREPARATION_MINUTES;
        } elseif ($session->individualCircle) {
            $preparationMinutes = $session->individualCircle->preparation_minutes ?? self::DEFAULT_PREPARATION_MINUTES;
        }

        return $this->buildStatusResponse($session, $userType, 'quran', $preparationMinutes);
    }

    /**
     * Get academic session attendance status
     */
    public function academicAttendanceStatus(Request $request, int $sessionId): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
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
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $session = QuranSession::with('meetingAttendances')->findOrFail($sessionId);

        return $this->buildAttendanceResponse($session, $request->user());
    }

    /**
     * Build unauthenticated response
     */
    private function unauthenticatedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'يجب تسجيل الدخول لعرض حالة الجلسة',
            'status' => 'unauthenticated',
            'can_join' => false,
            'button_text' => 'يجب تسجيل الدخول',
            'button_class' => 'bg-gray-400 cursor-not-allowed',
        ], 401);
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
        $isTeacher = in_array($userType, ['academic_teacher', 'quran_teacher']);
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
        } elseif ($userType === 'student' && in_array($session->status, $joinableStatuses)) {
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

        return response()->json([
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
            return response()->json([
                'has_attendance' => true,
                'attendance_status' => $attendance->attendance_status,
                'first_join_time' => $attendance->first_join_time?->toISOString(),
                'last_leave_time' => $attendance->last_leave_time?->toISOString(),
                'total_duration_minutes' => $attendance->total_duration_minutes,
                'attendance_percentage' => $attendance->attendance_percentage,
            ]);
        }

        return response()->json([
            'has_attendance' => false,
            'attendance_status' => null,
            'message' => 'لم يتم تسجيل حضور بعد',
        ]);
    }
}
