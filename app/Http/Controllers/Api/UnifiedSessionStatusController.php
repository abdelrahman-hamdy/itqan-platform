<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponses;
use App\Services\Session\SessionAttendanceStatusService;
use App\Services\Session\SessionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for unified session status (works with all session types)
 */
class UnifiedSessionStatusController extends Controller
{
    use ApiResponses;

    public function __construct(
        private SessionStatusService $statusService,
        private SessionAttendanceStatusService $attendanceStatusService
    ) {}

    /**
     * General session status API with smart polymorphic resolution
     */
    public function status(Request $request, int $sessionId): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthenticatedResponse();
        }

        $user = $request->user();
        $session = $this->statusService->resolveSession($sessionId, $user);

        if (!$session) {
            abort(404, 'الجلسة غير موجودة');
        }

        // Auto-complete if expired
        $this->statusService->autoCompleteIfExpired($session);

        // Determine user type
        $userType = $this->getUserType($user);

        // Get status display
        $displayData = $this->statusService->getStatusDisplay($session, $userType);
        [$preparationMinutes, $bufferMinutes] = $this->statusService->getSessionConfiguration($session);

        return $this->successResponse([
            'status' => $session->status->value ?? $session->status,
            'can_join' => $displayData['can_join'],
            'message' => $displayData['message'],
            'button_text' => $displayData['button_text'],
            'button_class' => $displayData['button_class'],
            'session_info' => [
                'scheduled_at' => $session->scheduled_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes,
                'preparation_minutes' => $preparationMinutes,
                'ending_buffer_minutes' => $bufferMinutes,
                'meeting_room_name' => $session->meeting_room_name,
            ],
        ]);
    }

    /**
     * General attendance status (smart resolution across session types)
     */
    public function attendance(Request $request, int $sessionId): JsonResponse
    {
        if (!auth()->check()) {
            return $this->unauthorizedResponse('Unauthenticated');
        }

        $user = $request->user();
        $session = $this->statusService->resolveSession($sessionId, $user);

        if (!$session) {
            abort(404, 'الجلسة غير موجودة');
        }

        $attendanceData = $this->attendanceStatusService->getAttendanceStatus($session, $user);

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

    /**
     * Get user type string
     */
    private function getUserType($user): string
    {
        if ($user->hasRole('quran_teacher')) {
            return 'quran_teacher';
        }
        if ($user->hasRole('academic_teacher')) {
            return 'academic_teacher';
        }
        return 'student';
    }
}
