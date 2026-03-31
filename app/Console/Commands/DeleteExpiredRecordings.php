<?php

namespace App\Console\Commands;

use App\Enums\RecordingStatus;
use App\Models\SessionRecording;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteExpiredRecordings extends Command
{
    protected $signature = 'recordings:cleanup {--days=7 : Number of days after which completed recordings are deleted}';

    protected $description = 'Delete session recordings older than the specified retention period';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $expired = SessionRecording::where('status', RecordingStatus::COMPLETED)
            ->where('completed_at', '<', $cutoff)
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired recordings found.');

            return self::SUCCESS;
        }

        $this->info("Found {$expired->count()} expired recording(s) (older than {$days} days).");

        $deleted = 0;
        foreach ($expired as $recording) {
            try {
                $recording->markAsDeleted();
                $deleted++;
            } catch (\Exception $e) {
                Log::error('Failed to delete expired recording', [
                    'recording_id' => $recording->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Deleted {$deleted} recording(s).");
        Log::info('Expired recordings cleanup completed', [
            'found' => $expired->count(),
            'deleted' => $deleted,
            'retention_days' => $days,
        ]);

        return self::SUCCESS;
    }
}
