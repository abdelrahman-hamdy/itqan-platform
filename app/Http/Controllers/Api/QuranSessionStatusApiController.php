<?php

namespace App\Http\Controllers\Api;

use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for Quran session status endpoints
 *
 * Handles session status and join eligibility for Quran sessions
 */
class QuranSessionStatusApiController extends BaseSessionStatusApiController
{
    /**
     * Get Quran session status
     */
    public function status(Request $request, int $sessionId): JsonResponse
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
     * Get Quran session attendance status
     */
    public function attendance(Request $request, int $sessionId): JsonResponse
    {
        if (! auth()->check()) {
            return $this->unauthorizedResponse('Unauthenticated');
        }

        $session = QuranSession::with('meetingAttendances')->findOrFail($sessionId);

        return $this->buildAttendanceResponse($session, $request->user());
    }
}
