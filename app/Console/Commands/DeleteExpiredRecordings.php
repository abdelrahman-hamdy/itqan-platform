<?php

namespace App\Console\Commands;

use App\Enums\RecordingStatus;
use App\Models\SessionRecording;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteExpiredRecordings extends Command
{
    protected $signature = 'recordings:cleanup {--days= : Override retention days (default from config)}';

    protected $description = 'Delete session recordings older than the configured retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('recordings.retention_days', 7));
        $cutoff = now()->subDays($days);

        $total = SessionRecording::where('status', RecordingStatus::COMPLETED)
            ->where('completed_at', '<', $cutoff)
            ->count();

        if ($total === 0) {
            $this->info('No expired recordings found.');

            return self::SUCCESS;
        }

        $this->info("Found {$total} expired recording(s) (older than {$days} days).");

        $deleted = 0;
        $failed = 0;

        SessionRecording::where('status', RecordingStatus::COMPLETED)
            ->where('completed_at', '<', $cutoff)
            ->chunkById(100, function ($recordings) use (&$deleted, &$failed) {
                foreach ($recordings as $recording) {
                    try {
                        $recording->markAsDeleted();
                        $deleted++;
                    } catch (\Exception $e) {
                        $failed++;
                        $this->error("Failed: recording #{$recording->id} — {$e->getMessage()}");
                        Log::error('Failed to delete expired recording', [
                            'recording_id' => $recording->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Deleted {$deleted}, failed {$failed}.");
        Log::info('Expired recordings cleanup completed', [
            'total' => $total,
            'deleted' => $deleted,
            'failed' => $failed,
            'retention_days' => $days,
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
