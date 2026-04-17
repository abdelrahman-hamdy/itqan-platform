<?php

namespace App\Jobs;

use App\Models\BaseSession;
use App\Services\Meeting\AudioQualitySummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued off RoomFinishedHandler. Keeps the webhook response fast — parsing
 * the daily telemetry log for one session can scan tens of megabytes.
 */
class SummarizeSessionTelemetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 2;

    public function __construct(
        private readonly string $sessionClass,
        private readonly int $sessionId,
    ) {
        $this->onQueue('default');
    }

    public function handle(AudioQualitySummaryService $service): void
    {
        if (! is_subclass_of($this->sessionClass, BaseSession::class)) {
            return;
        }

        /** @var BaseSession|null $session */
        $session = $this->sessionClass::query()->withoutGlobalScopes()->find($this->sessionId);
        if ($session === null) {
            return;
        }

        $service->summarize($session);
    }
}
