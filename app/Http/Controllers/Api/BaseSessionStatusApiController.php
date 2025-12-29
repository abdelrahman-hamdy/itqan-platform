<?php

namespace App\Http\Controllers\Api;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

/**
 * Base controller for session status API endpoints
 *
 * Provides shared functionality for session status and attendance checking
 */
abstract class BaseSessionStatusApiController extends Controller
{
    use ApiResponses;

    protected const DEFAULT_PREPARATION_MINUTES = 15;
    protected const DEFAULT_ENDING_BUFFER_MINUTES = 5;
    protected const DEFAULT_DURATION_MINUTES = 60;

    /**
     * Build unauthenticated response
     */
    protected function unauthenticatedResponse(): JsonResponse
    {
        return $this->customResponse([
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
    protected function buildStatusResponse(
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

        return $this->successResponse([
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
    protected function getStatusDisplay($session, bool $canJoinMeeting, int $preparationMinutes): array
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
    protected function buildAttendanceResponse($session, $user): JsonResponse
    {
        $attendance = $session->meetingAttendances
            ->where('user_id', $user->id)
            ->first();

        if ($attendance) {
            return $this->successResponse([
                'has_attendance' => true,
                'attendance_status' => $attendance->attendance_status,
                'first_join_time' => $attendance->first_join_time?->toISOString(),
                'last_leave_time' => $attendance->last_leave_time?->toISOString(),
                'total_duration_minutes' => $attendance->total_duration_minutes,
                'attendance_percentage' => $attendance->attendance_percentage,
            ]);
        }

        return $this->successResponse([
            'has_attendance' => false,
            'attendance_status' => null,
            'message' => 'لم يتم تسجيل حضور بعد',
        ]);
    }
}
