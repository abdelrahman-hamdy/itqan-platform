<?php

namespace App\Jobs;

use App\Services\RecordingOrchestratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\NotTenantAware;

/**
 * Processes the recording queue after a recording slot frees up.
 * NotTenantAware because recording capacity is server-wide, not per-tenant.
 */
class ProcessRecordingQueueJob implements NotTenantAware, ShouldBeUnique, ShouldQueue
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
