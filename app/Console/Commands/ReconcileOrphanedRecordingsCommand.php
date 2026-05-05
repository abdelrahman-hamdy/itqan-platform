<?php

namespace App\Console\Commands;

use App\Contracts\RecordingCapable;
use App\Enums\LiveKitEgressStatus;
use App\Enums\RecordingStatus;
use App\Enums\SessionStatus;
use App\Models\SessionRecording;
use App\Services\LiveKitService;
use App\Services\RecordingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Repair recordings whose status pipeline got stuck:
 *
 *  - PROCESSING rows older than 10 minutes — the egress_ended webhook
 *    was likely lost. One ListEgress call resolves all of them.
 *  - RECORDING rows whose parent session is COMPLETED — the cron or
 *    the session-completed listener missed them. Nudge stopRecording.
 *
 * Safe to run repeatedly — every action is idempotent.
 */
class ReconcileOrphanedRecordingsCommand extends Command
{
    private const PROCESSING_STALE_MINUTES = 10;

    private const RECORDING_STALE_MINUTES = 30;

    protected $signature = 'recordings:reconcile-orphaned';

    protected $description = 'Repair stuck PROCESSING/RECORDING rows by polling LiveKit ListEgress';

    public function __construct(
        private RecordingService $recordingService,
        private LiveKitService $liveKitService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->reconcileProcessing();
        $this->reconcileOrphanedRecording();

        return Command::SUCCESS;
    }

    private function reconcileProcessing(): void
    {
        $stuck = SessionRecording::query()
            ->where('status', RecordingStatus::PROCESSING->value)
            ->where('updated_at', '<', now()->subMinutes(self::PROCESSING_STALE_MINUTES))
            ->whereNotNull('recording_id')
            ->limit(50)
            ->get();

        if ($stuck->isEmpty()) {
            return;
        }

        Log::info('[RECORDINGS] Reconciling stuck PROCESSING recordings', [
            'count' => $stuck->count(),
        ]);

        // One ListEgress call covers every stuck row — avoids N HTTP roundtrips.
        $activeByEgress = $this->liveKitService->listAllActiveEgresses();

        foreach ($stuck as $recording) {
            try {
                $item = $activeByEgress[$recording->recording_id]
                    ?? $this->liveKitService->getRecordingInfo($recording->recording_id);

                if (! $item) {
                    $recording->markAsFailed('Egress not found on LiveKit (likely lost)');
                    Log::warning('[RECORDINGS] Stuck recording — egress not found, marked failed', [
                        'recording_id' => $recording->id,
                        'egress_id' => $recording->recording_id,
                    ]);

                    continue;
                }

                $this->resolveStuckProcessing($recording, $item);
            } catch (Throwable $e) {
                Log::error('[RECORDINGS] Reconciliation failed for stuck recording', [
                    'recording_id' => $recording->id,
                    'egress_id' => $recording->recording_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function resolveStuckProcessing(SessionRecording $recording, array $item): void
    {
        $status = LiveKitEgressStatus::tryFrom($item['status'] ?? '');

        if ($status === LiveKitEgressStatus::COMPLETE) {
            $this->recordingService->processEgressWebhook([
                'event' => 'egress_ended',
                'egressInfo' => $item,
            ]);
            Log::info('[RECORDINGS] Stuck recording resolved as completed via polling', [
                'recording_id' => $recording->id,
                'egress_id' => $recording->recording_id,
            ]);

            return;
        }

        if ($status?->isFailure()) {
            $error = $item['error'] ?? 'LiveKit reported '.$status->value;
            $recording->markAsFailed($error);
            Log::warning('[RECORDINGS] Stuck recording resolved as failed via polling', [
                'recording_id' => $recording->id,
                'egress_id' => $recording->recording_id,
                'livekit_status' => $status->value,
            ]);

            return;
        }

        Log::debug('[RECORDINGS] Stuck PROCESSING recording still active on LiveKit', [
            'recording_id' => $recording->id,
            'livekit_status' => $status?->value,
        ]);
    }

    private function reconcileOrphanedRecording(): void
    {
        $orphaned = SessionRecording::query()
            ->where('status', RecordingStatus::RECORDING->value)
            ->where('started_at', '<', now()->subMinutes(self::RECORDING_STALE_MINUTES))
            ->with('recordable')
            ->limit(50)
            ->get();

        foreach ($orphaned as $recording) {
            $session = $recording->recordable;

            if (! $session instanceof RecordingCapable) {
                continue;
            }

            if ($session->status !== SessionStatus::COMPLETED) {
                continue;
            }

            try {
                $session->stopRecording();
                Log::info('[RECORDINGS] Nudged orphaned recording for completed session', [
                    'recording_id' => $recording->id,
                    'session_id' => $session->id,
                ]);
            } catch (Throwable $e) {
                Log::warning('[RECORDINGS] Nudge failed for orphaned recording', [
                    'recording_id' => $recording->id,
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
