<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * Shared response shape for calendar-schedule endpoints, so a no-op run
 * (zero sessions actually created) cannot ship a green "success" toast.
 */
trait RespondsWithScheduleResult
{
    protected function scheduleResultResponse(int $created, int $requested): JsonResponse
    {
        $failed = max(0, $requested - $created);

        if ($created <= 0) {
            return response()->json([
                'success' => false,
                'message' => __('calendar.schedule_no_sessions_created'),
                'created' => 0,
                'requested' => $requested,
                'failed' => $failed,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $created < $requested
                ? __('calendar.schedule_partial', ['created' => $created, 'requested' => $requested])
                : __('calendar.schedule_created_successfully'),
            'created' => $created,
            'requested' => $requested,
            'failed' => $failed,
        ]);
    }
}
