<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SessionAlarmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Alarm endpoints — teacher/student calling each other back to a session.
 *
 * Routes (registered in routes/api/v1/common.php):
 *  - POST /sessions/{sessionType}/{sessionId}/alarm  — start a ring
 *  - POST /sessions/alarms/{callId}/answer           — target answered
 *  - POST /sessions/alarms/{callId}/decline          — target declined
 */
class SessionAlarmController extends Controller
{
    public function __construct(
        private readonly SessionAlarmService $alarms,
    ) {}

    public function alarm(
        Request $request,
        string $sessionType,
        string $sessionId,
    ): JsonResponse {
        $data = $request->validate([
            'target_user_id' => ['required', 'integer', 'min:1'],
        ]);

        if (! in_array($sessionType, ['quran', 'academic', 'interactive'], true)) {
            return response()->json([
                'success' => false,
                'message' => __('meetings.alarm.invalid_session_type'),
            ], 422);
        }

        $result = $this->alarms->alarm(
            $request->user(),
            $sessionType,
            $sessionId,
            (int) $data['target_user_id'],
        );

        return match ($result['status']) {
            'sent' => response()->json([
                'success' => true,
                'call_id' => $result['call_id'],
                'message' => __('meetings.alarm.sent'),
            ]),
            'cooldown' => response()->json([
                'success' => false,
                'retry_after' => $result['retry_after'] ?? SessionAlarmService::COOLDOWN_SECONDS,
                'message' => __('meetings.alarm.cooldown'),
            ], 429),
            default => response()->json([
                'success' => false,
                'message' => __('meetings.alarm.forbidden'),
            ], 403),
        };
    }

    public function answer(Request $request, string $callId): JsonResponse
    {
        $alarm = $this->alarms->markAnswered($callId, $request->user());
        if ($alarm === null) {
            return response()->json([
                'success' => false,
                'message' => __('meetings.alarm.not_found'),
            ], 404);
        }
        return response()->json(['success' => true]);
    }

    public function decline(Request $request, string $callId): JsonResponse
    {
        $alarm = $this->alarms->markDeclined($callId, $request->user());
        if ($alarm === null) {
            return response()->json([
                'success' => false,
                'message' => __('meetings.alarm.not_found'),
            ], 404);
        }
        return response()->json(['success' => true]);
    }
}
