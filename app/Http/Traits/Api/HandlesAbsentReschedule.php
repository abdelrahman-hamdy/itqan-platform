<?php

namespace App\Http\Traits\Api;

use App\Enums\SessionStatus;
use App\Models\BaseSession;
use App\Services\SessionTransitionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HandlesAbsentReschedule
{
    /**
     * Handle rescheduling a session from ABSENT status via SessionTransitionService.
     *
     * Returns a JsonResponse if the session is ABSENT (handled), or null if not ABSENT (caller should continue).
     */
    protected function rescheduleFromAbsentIfNeeded(
        BaseSession $session,
        Request $request,
        int $userId,
    ): ?JsonResponse {
        $status = $session->status instanceof SessionStatus
            ? $session->status
            : SessionStatus::tryFrom($session->status);

        if ($status !== SessionStatus::ABSENT) {
            return null;
        }

        $transitionService = app(SessionTransitionService::class);
        $oldScheduledAt = $session->scheduled_at;
        $success = $transitionService->transitionToScheduledFromAbsent(
            $session,
            Carbon::parse($request->scheduled_at),
            $request->reason,
            $userId
        );

        if (! $success) {
            return $this->error(__('Failed to reschedule session.'), 400, 'RESCHEDULE_FAILED');
        }

        $session->refresh();

        return $this->success([
            'session' => [
                'id' => $session->id,
                'scheduled_at' => $session->scheduled_at->toISOString(),
                'rescheduled_from' => $oldScheduledAt?->toISOString(),
            ],
        ], __('Session rescheduled successfully'));
    }
}
