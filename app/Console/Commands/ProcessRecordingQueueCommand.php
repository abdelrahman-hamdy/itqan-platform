<?php

namespace App\Console\Commands;

use App\Services\RecordingOrchestratorService;
use Illuminate\Console\Command;

/**
 * Safety-net command to process stale recording queue entries.
 * Scheduled every minute to catch missed webhook events.
 */
class ProcessRecordingQueueCommand extends Command
{
    protected $signature = 'recordings:process-queue';

    protected $description = 'Process stale recording queue entries and promote waiting sessions';

    public function handle(RecordingOrchestratorService $orchestrator): int
    {
        $orchestrator->processStaleQueue();

        $status = $orchestrator->getCapacityStatus();

        $this->info(sprintf(
            'Recording capacity: %d/%d active, %d queued',
            $status['active_count'],
            $status['max_count'],
            $status['queued_count']
        ));

        return self::SUCCESS;
    }
}
