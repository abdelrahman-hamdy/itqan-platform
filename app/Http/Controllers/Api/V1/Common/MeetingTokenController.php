<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\LiveKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\SessionStatus;

class MeetingTokenController extends Controller
{
    use ApiResponses;

    protected LiveKitService $liveKitService;

    public function __construct(LiveKitService $liveKitService)
    {
        $this->liveKitService = $liveKitService;
    }

    /**
     * Get meeting token for a session.
     *
     * @param Request $request
     * @param string $sessionType
     * @param int $sessionId
     * @return JsonResponse
     */
    public function getToken(Request $request, string $sessionType, int $sessionId): JsonResponse
    {
        $user = $request->user();
        $session = $this->getSession($sessionType, $sessionId, $user->id);

        if (!$session) {
            return $this->notFound(__('Session not found or access denied.'));
        }

        // Check if session can be joined
        if (!$this->canJoinSession($session, $sessionType)) {
            return $this->error(
                __('Cannot join session at this time.'),
                400,
                'SESSION_NOT_JOINABLE'
            );
        }

        // Get or create meeting
        $meeting = $session->meeting;

        if (!$meeting) {
            return $this->error(
                __('Meeting not available yet.'),
                400,
                'MEETING_NOT_AVAILABLE'
            );
        }

        // Determine participant role
        $isTeacher = $this->isTeacher($session, $sessionType, $user->id);
        $role = $isTeacher ? 'teacher' : 'student';

        try {
            // Generate LiveKit token
            $token = $this->liveKitService->createToken(
                $meeting->room_name,
                $user->id,
                $user->name,
                [
                    'canPublish' => true,
                    'canSubscribe' => true,
                    'canPublishData' => true,
                    'hidden' => false,
                    'recorder' => false,
                ],
                7200 // 2 hours TTL
            );

            return $this->success([
                'token' => $token,
                'room_name' => $meeting->room_name,
                'livekit_url' => config('livekit.server_url'),
                'participant' => [
                    'identity' => (string) $user->id,
                    'name' => $user->name,
                    'role' => $role,
                ],
                'session' => [
                    'id' => $session->id,
                    'type' => $sessionType,
                    'title' => $session->title,
                    'duration_minutes' => $session->duration_minutes ?? 45,
                ],
                'expires_in' => 7200,
            ], __('Meeting token generated'));

        } catch (\Exception $e) {
            return $this->error(
                __('Failed to generate meeting token.'),
                500,
                'TOKEN_GENERATION_FAILED'
            );
        }
    }

    /**
     * Get meeting info without token.
     *
     * @param Request $request
     * @param string $sessionType
     * @param int $sessionId
     * @return JsonResponse
     */
    public function getInfo(Request $request, string $sessionType, int $sessionId): JsonResponse
    {
        $user = $request->user();
        $session = $this->getSession($sessionType, $sessionId, $user->id);

        if (!$session) {
            return $this->notFound(__('Session not found or access denied.'));
        }

        $meeting = $session->meeting;
        $canJoin = $this->canJoinSession($session, $sessionType);
        $isTeacher = $this->isTeacher($session, $sessionType, $user->id);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'type' => $sessionType,
                'title' => $session->title,
                'status' => $session->status->value ?? $session->status,
                'scheduled_at' => $this->getScheduledAt($session, $sessionType)?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 45,
            ],
            'meeting' => $meeting ? [
                'id' => $meeting->id,
                'room_name' => $meeting->room_name,
                'status' => $meeting->status,
                'is_active' => $meeting->status === 'active',
            ] : null,
            'participant' => [
                'role' => $isTeacher ? 'teacher' : 'student',
                'can_join' => $canJoin,
            ],
            'join_window' => [
                'opens_at' => $this->getJoinWindowStart($session, $sessionType)?->toISOString(),
                'closes_at' => $this->getJoinWindowEnd($session, $sessionType)?->toISOString(),
            ],
        ], __('Meeting info retrieved'));
    }

    /**
     * Get session by type and ID.
     */
    protected function getSession(string $type, int $id, int $userId)
    {
        return match ($type) {
            'quran' => QuranSession::where('id', $id)
                ->where(function ($q) use ($userId) {
                    $q->where('student_id', $userId)
                        ->orWhereHas('quranTeacher', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->with(['meeting', 'quranTeacher'])
                ->first(),

            'academic' => AcademicSession::where('id', $id)
                ->where(function ($q) use ($userId) {
                    $q->where('student_id', $userId)
                        ->orWhereHas('academicTeacher', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->with(['meeting', 'academicTeacher'])
                ->first(),

            'interactive' => InteractiveCourseSession::where('id', $id)
                ->where(function ($q) use ($userId) {
                    $q->whereHas('course.enrollments', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    })
                        ->orWhereHas('course.assignedTeacher', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->with(['meeting', 'course.assignedTeacher'])
                ->first(),

            default => null,
        };
    }

    /**
     * Check if session can be joined.
     */
    protected function canJoinSession($session, string $type): bool
    {
        $scheduledAt = $this->getScheduledAt($session, $type);

        if (!$scheduledAt) {
            return false;
        }

        $now = now();
        $joinStart = $scheduledAt->copy()->subMinutes(10);
        $duration = $session->duration_minutes ?? 45;
        $joinEnd = $scheduledAt->copy()->addMinutes($duration + 15); // 15 min grace after

        $status = $session->status->value ?? $session->status;

        return $now->between($joinStart, $joinEnd)
            && !in_array($status, [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value]);
    }

    /**
     * Check if user is the teacher.
     */
    protected function isTeacher($session, string $type, int $userId): bool
    {
        return match ($type) {
            'quran' => $session->quranTeacher?->id === $userId, // QuranSession::quranTeacher returns User directly
            'academic' => $session->academicTeacher?->user_id === $userId,
            'interactive' => $session->course?->assignedTeacher?->user_id === $userId,
            default => false,
        };
    }

    /**
     * Get scheduled at time.
     */
    protected function getScheduledAt($session, string $type)
    {
        // All session types now use scheduled_at
        return $session->scheduled_at;
    }

    /**
     * Get join window start time.
     */
    protected function getJoinWindowStart($session, string $type)
    {
        $scheduledAt = $this->getScheduledAt($session, $type);
        return $scheduledAt?->copy()->subMinutes(10);
    }

    /**
     * Get join window end time.
     */
    protected function getJoinWindowEnd($session, string $type)
    {
        $scheduledAt = $this->getScheduledAt($session, $type);
        $duration = $session->duration_minutes ?? 45;
        return $scheduledAt?->copy()->addMinutes($duration + 15);
    }
}
