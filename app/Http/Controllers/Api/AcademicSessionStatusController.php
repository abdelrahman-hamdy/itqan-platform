<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponses;
use App\Models\AcademicSession;
use App\Services\Session\SessionAttendanceStatusService;
use App\Services\Session\SessionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for Academic session status endpoints
 */
class AcademicSessionStatusController extends Controller
{
    use ApiResponses;

    public function __construct(
        private SessionStatusService $statusService,
        private SessionAttendanceStatusService $attendanceStatusService
    ) {}

    /**
     * Get academic session status
     */
    public function status(Request $request, int $sessionId): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthenticatedResponse();
        }

        $user = $request->user();
        $session = AcademicSession::findOrFail($sessionId);

        $userType = $user->hasRole('academic_teacher') ? 'academic_teacher' : 'student';

        // Auto-complete if expired
        $this->statusService->autoCompleteIfExpired($session);

        // Get status display
        $displayData = $this->statusService->getStatusDisplay($session, $userType);
        [$preparationMinutes, $bufferMinutes] = $this->statusService->getSessionConfiguration($session);

        return $this->successResponse([
            'status' => $session->status->value ?? $session->status,
            'message' => $displayData['message'],
            'button_text' => $displayData['button_text'],
            'button_class' => $displayData['button_class'],
            'can_join' => $displayData['can_join'],
            'session_type' => 'academic',
            'session_info' => [
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes,
                'preparation_minutes' => $preparationMinutes,
                'ending_buffer_minutes' => $bufferMinutes,
            ],
        ]);
    }

    /**
     * Get academic session attendance status
     */
    public function attendance(Request $request, int $sessionId): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthorizedResponse('Unauthenticated');
        }

        $session = AcademicSession::with('meetingAttendances')->findOrFail($sessionId);
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
