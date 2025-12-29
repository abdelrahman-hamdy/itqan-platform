<?php

namespace App\Http\Controllers\Api;

use App\Models\AcademicSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for Academic session status endpoints
 *
 * Handles session status and join eligibility for Academic sessions
 */
class AcademicSessionStatusApiController extends BaseSessionStatusApiController
{
    /**
     * Get academic session status
     */
    public function status(Request $request, int $sessionId): JsonResponse
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
     * Get academic session attendance status
     */
    public function attendance(Request $request, int $sessionId): JsonResponse
    {
        if (! auth()->check()) {
            return $this->unauthorizedResponse('Unauthenticated');
        }

        $session = AcademicSession::with('meetingAttendances')->findOrFail($sessionId);

        return $this->buildAttendanceResponse($session, $request->user());
    }
}
