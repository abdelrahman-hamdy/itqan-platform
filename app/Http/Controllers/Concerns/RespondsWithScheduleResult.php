<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Calendar\BatchScheduleResult;
use Illuminate\Http\JsonResponse;

trait RespondsWithScheduleResult
{
    /**
     * Cap on `failures` entries returned to the UI — the panel only renders
     * the first 5 with an overflow count, so a 50-conflict batch shouldn't
     * ship 50 entries over the wire.
     */
    private const FAILURES_PAYLOAD_CAP = 10;

    protected function scheduleResultResponse(BatchScheduleResult $result): JsonResponse
    {
        $failuresTotal = count($result->failures);

        return response()->json(
            [
                'success' => ! $result->isEmpty(),
                'message' => __($result->messageKey(), $result->messageParams()),
                'created' => $result->created,
                'requested' => $result->requested,
                'failed' => max(0, $result->requested - $result->created),
                'failures' => array_slice($result->failures, 0, self::FAILURES_PAYLOAD_CAP),
                'failures_total' => $failuresTotal,
            ],
            $result->isEmpty() ? 422 : 200,
        );
    }
}
