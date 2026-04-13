<?php

namespace App\Jobs;

use App\Services\RecordingOrchestratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Processes the recording queue after a recording slot frees up.
 * Dispatched when an egress ends (via webhook) or a session finishes.
 * ShouldBeUnique prevents duplicate processing when multiple webhooks fire close together.
 */
class ProcessRecordingQueueJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 10;

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(RecordingOrchestratorService $orchestrator): void
    {
        $orchestrator->processQueue();
    }
}
