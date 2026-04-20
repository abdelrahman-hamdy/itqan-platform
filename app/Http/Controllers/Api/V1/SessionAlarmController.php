<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
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
    use ApiResponses;

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
            return $this->error(__('meetings.alarm.invalid_session_type'), 422);
        }

        $result = $this->alarms->alarm(
            $request->user(),
            $sessionType,
            $sessionId,
            (int) $data['target_user_id'],
        );

        return match ($result['status']) {
            'sent' => $this->success(
                ['call_id' => $result['call_id']],
                __('meetings.alarm.sent'),
            ),
            'cooldown' => $this->error(
                message: __('meetings.alarm.cooldown'),
                status: 429,
                errorCode: 'COOLDOWN',
                meta: [
                    'retry_after' => $result['retry_after']
                        ?? SessionAlarmService::COOLDOWN_SECONDS,
                ],
            ),
            default => $this->error(__('meetings.alarm.forbidden'), 403),
        };
    }

    public function answer(Request $request, string $callId): JsonResponse
    {
        $alarm = $this->alarms->markAnswered($callId, $request->user());
        if ($alarm === null) {
            return $this->notFound(__('meetings.alarm.not_found'));
        }
        return $this->success();
    }

    public function decline(Request $request, string $callId): JsonResponse
    {
        $alarm = $this->alarms->markDeclined($callId, $request->user());
        if ($alarm === null) {
            return $this->notFound(__('meetings.alarm.not_found'));
        }
        return $this->success();
    }
}
