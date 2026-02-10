<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Services\MeetingObserverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Meeting Observer API Controller
 *
 * Provides observer token generation for Admins, SuperAdmins, and Supervisors
 * to observe live sessions via LiveKit with read-only access.
 */
class MeetingObserverController extends Controller
{
    use ApiResponses;

    protected MeetingObserverService $observerService;

    public function __construct(MeetingObserverService $observerService)
    {
        $this->observerService = $observerService;
    }

    /**
     * Generate observer token for active session meeting
     *
     * Only Admins, SuperAdmins, and Supervisors can observe sessions.
     * Parents are explicitly excluded from observation.
     *
     * @param Request $request
     * @param string $sessionType One of: quran, academic, interactive
     * @param string $sessionId Session ID or UUID
     * @return JsonResponse
     */
    public function getObserverToken(
        Request $request,
        string $sessionType,
        string $sessionId
    ): JsonResponse {
        $user = $request->user();

        // Only admins, super admins, supervisors can observe (NOT parents)
        if (! $user->isAdmin() && ! $user->isSuperAdmin() && ! $user->isSupervisor()) {
            return $this->error(
                __('Access denied. Admin, SuperAdmin, or Supervisor account required.'),
                403,
                'FORBIDDEN'
            );
        }

        // Resolve session by type
        $session = $this->observerService->resolveSession($sessionType, $sessionId);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Check if user can observe this specific session
        if (! $this->observerService->canObserveSession($user, $session)) {
            return $this->error(
                __('You do not have permission to observe this session.'),
                403,
                'FORBIDDEN'
            );
        }

        // Check if session is observable (has active meeting)
        if (! $this->observerService->isSessionObservable($session)) {
            return $this->error(
                __('Session has no active meeting. Observation is only available during live sessions.'),
                400,
                'NOT_ACTIVE'
            );
        }

        // Generate observer token (read-only: can_publish: false, can_subscribe: true)
        $token = $this->observerService->generateObserverToken(
            $session->meeting_room_name,
            $user
        );

        return $this->success([
            'access_token' => $token,
            'server_url' => config('livekit.server_url'),
            'room_name' => $session->meeting_room_name,
            'is_observer' => true,
        ], __('Observer token generated successfully'));
    }
}
