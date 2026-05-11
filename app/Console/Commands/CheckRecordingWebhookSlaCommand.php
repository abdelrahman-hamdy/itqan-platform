<?php

namespace App\Console\Commands;

use App\Enums\RecordingStatus;
use App\Models\SessionRecording;
use App\Services\LiveKit\LiveKitRecordingManager;
use Illuminate\Console\Command;

/**
 * Pages Telegram when a recording row has been stuck in `recording` status
 * past the longest plausible session window. Cross-checks LiveKit's
 * ListEgress so we only alert on rows where LiveKit already says the
 * stream finished — i.e. webhook delivery actually missed.
 *
 * Scheduled every 10 minutes from routes/console.php. Reconciliation
 * itself is handled by `recordings:reconcile-orphaned`; this command's
 * job is the human-facing alarm.
 */
class CheckRecordingWebhookSlaCommand extends Command
{
    protected $signature = 'recordings:check-webhook-sla
                            {--threshold-minutes=90 : Age beyond which a stuck row is considered SLA breach}';

    protected $description = 'Page Telegram when SessionRecording rows are stuck in recording past the SLA';

    public function handle(LiveKitRecordingManager $livekit): int
    {
        $thresholdMinutes = (int) $this->option('threshold-minutes');
        $cutoff = now()->subMinutes($thresholdMinutes);

        $candidates = SessionRecording::withoutGlobalScopes()
            ->where('status', RecordingStatus::RECORDING->value)
            ->whereNotNull('egress_id')
            ->where('started_at', '<', $cutoff)
            ->limit(50)
            ->get(['id', 'egress_id', 'started_at']);

        if ($candidates->isEmpty()) {
            $this->info('No recordings past SLA.');

            return self::SUCCESS;
        }

        $activeMap = $livekit->listAllActiveEgresses();

        $finishedStatuses = [
            'EGRESS_COMPLETE',
            'EGRESS_ENDING',
            'EGRESS_FAILED',
            'EGRESS_ABORTED',
            'EGRESS_LIMIT_REACHED',
        ];

        $breached = [];

        foreach ($candidates as $row) {
            $egressId = (string) $row->egress_id;
            $remote = $activeMap[$egressId] ?? null;

            // Egress missing from the active map = LiveKit already pruned
            // it. Definitive webhook miss. Egress present with a finished
            // status = LiveKit knows it ended, our row didn't catch up.
            $status = is_array($remote) ? ($remote['status'] ?? null) : null;
            $missingRemote = $remote === null;
            $remoteFinished = $status !== null && in_array($status, $finishedStatuses, true);

            if ($missingRemote || $remoteFinished) {
                $breached[] = [
                    'id' => $row->id,
                    'egress_id' => $egressId,
                    'remote_status' => $status ?? 'absent',
                    'started_at' => optional($row->started_at)->toIso8601String(),
                ];
            }
        }

        if (empty($breached)) {
            $this->info('Candidates found but none confirm webhook miss; reconciliation will handle them.');

            return self::SUCCESS;
        }

        $count = count($breached);
        $ids = collect($breached)->pluck('id')->take(10)->implode(', ');

        $this->warn("SLA breach detected for {$count} recording(s): {$ids}");

        alert_telegram(
            'medium',
            'recording-webhook-sla',
            "Webhook miss for {$count} recording(s) — LiveKit reports finished but our row stuck in recording. IDs: {$ids}"
        );

        return self::SUCCESS;
    }
}
