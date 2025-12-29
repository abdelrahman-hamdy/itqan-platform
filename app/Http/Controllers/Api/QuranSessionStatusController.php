<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponses;
use App\Models\QuranSession;
use App\Services\Session\SessionAttendanceStatusService;
use App\Services\Session\SessionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for Quran session status endpoints
 */
class QuranSessionStatusController extends Controller
{
    use ApiResponses;

    public function __construct(
        private SessionStatusService $statusService,
        private SessionAttendanceStatusService $attendanceStatusService
    ) {}

    /**
     * Get Quran session status
     */
    public function status(Request $request, int $sessionId): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthenticatedResponse();
        }

        $user = $request->user();
        $session = QuranSession::findOrFail($sessionId);

        $userType = $user->hasRole('quran_teacher') ? 'quran_teacher' : 'student';

        // Get preparation minutes from circle if available
        $preparationMinutes = 15;
        if ($session->circle) {
            $preparationMinutes = $session->circle->preparation_minutes ?? 15;
        } elseif ($session->individualCircle) {
            $preparationMinutes = $session->individualCircle->preparation_minutes ?? 15;
        }

        // Auto-complete if expired
        $this->statusService->autoCompleteIfExpired($session);

        // Get status display
        $displayData = $this->statusService->getStatusDisplay($session, $userType, $preparationMinutes);
        [$preparationMinutes, $bufferMinutes] = $this->statusService->getSessionConfiguration($session);

        return $this->successResponse([
            'status' => $session->status->value ?? $session->status,
            'message' => $displayData['message'],
            'button_text' => $displayData['button_text'],
            'button_class' => $displayData['button_class'],
            'can_join' => $displayData['can_join'],
            'session_type' => 'quran',
            'session_info' => [
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes,
                'preparation_minutes' => $preparationMinutes,
                'ending_buffer_minutes' => $bufferMinutes,
            ],
        ]);
    }

    /**
     * Get Quran session attendance status
     */
    public function attendance(Request $request, int $sessionId): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthorizedResponse('Unauthenticated');
        }

        $session = QuranSession::with('meetingAttendances')->findOrFail($sessionId);
        $attendanceData = $this->attendanceStatusService->getAttendanceStatus($session, $request->user());

        return $this->successResponse($attendanceData);
    }

    /**
     * Build unauthenticated response
     */
    private function unauthenticatedResponse(): JsonResponse
    {
        return $this->customResponse([
            'message' => 'يجب تسجيل الدخول لعرض حالة الجلسة',
            'status' => 'unauthenticated',
            'can_join' => false,
            'button_text' => 'يجب تسجيل الدخول',
            'button_class' => 'bg-gray-400 cursor-not-allowed',
        ], false, 401);
    }
}
