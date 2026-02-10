<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Services\MeetingObserverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingObserverController extends Controller
{
    use ApiResponses;

    public function __construct(
        private MeetingObserverService $observerService
    ) {}

    /**
     * Get an observer-only LiveKit token for a session meeting.
     * Used by supervisors and super admins to observe sessions without affecting attendance.
     */
    public function getObserverToken(Request $request, string $sessionType, string $sessionId): JsonResponse
    {
        $user = $request->user();

        $session = $this->observerService->resolveSession($sessionType, $sessionId);

        if (! $session) {
            return $this->error(__('supervisor.observation.session_not_found'), 404);
        }

        if (! $this->observerService->canObserveSession($user, $session)) {
            return $this->error(__('supervisor.observation.not_authorized'), 403);
        }

        if (! $this->observerService->isSessionObservable($session)) {
            return $this->error(__('supervisor.observation.session_not_active'), 422);
        }

        $token = $this->observerService->generateObserverToken(
            $session->meeting_room_name,
            $user
        );

        return $this->success([
            'access_token' => $token,
            'server_url' => config('livekit.server_url'),
            'room_name' => $session->meeting_room_name,
            'is_observer' => true,
        ]);
    }
}
