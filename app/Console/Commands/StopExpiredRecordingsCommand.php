<?php

namespace App\Console\Commands;

use App\Contracts\RecordingCapable;
use App\Enums\RecordingStatus;
use App\Models\BaseSession;
use App\Models\SessionRecording;
use App\Services\SessionSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Stop recordings for sessions that have reached their scheduled end time.
 *
 * This command runs every minute to check for sessions with active recordings
 * where the scheduled end time (scheduled_at + duration_minutes) has passed.
 */
class StopExpiredRecordingsCommand extends Command
{
    private const STALE_RECORDING_MINUTES = 30;

    protected $signature = 'recordings:stop-expired';

    protected $description = 'Stop recordings for sessions that have reached their scheduled end time';

    public function __construct(private SessionSettingsService $settingsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        Log::info('[RECORDINGS] Starting expired recordings check');

        $stoppedCount = 0;

        $activeRecordings = SessionRecording::where('status', RecordingStatus::RECORDING->value)
            ->with('recordable.academy.settings')
            ->get();

        Log::info('[RECORDINGS] Found active recordings', [
            'count' => $activeRecordings->count(),
        ]);

        foreach ($activeRecordings as $recordingRecord) {
            $session = $recordingRecord->recordable;

            if (! $session instanceof BaseSession || ! $session->scheduled_at) {
                continue;
            }

            $durationMinutes = $session->duration_minutes ?? 60;
            $bufferMinutes = $this->settingsService->getBufferMinutes($session);
            $expectedEndTime = $session->scheduled_at->copy()->addMinutes($durationMinutes + $bufferMinutes);

            if (now()->lt($expectedEndTime)) {
                continue;
            }

            $minutesOverdue = (int) $expectedEndTime->diffInMinutes(now());

            Log::info('[RECORDINGS] Stopping recording for expired session', [
                'session_id' => $session->id,
                'scheduled_start' => $session->scheduled_at->toISOString(),
                'expected_end' => $expectedEndTime->toISOString(),
                'buffer_minutes' => $bufferMinutes,
                'minutes_overdue' => $minutesOverdue,
            ]);

            if (! $session instanceof RecordingCapable) {
                continue;
            }

            $stopped = $session->stopRecording();

            if ($stopped) {
                $stoppedCount++;
                Log::info('[RECORDINGS] Recording stopped successfully', [
                    'session_id' => $session->id,
                ]);
            } elseif ($minutesOverdue > self::STALE_RECORDING_MINUTES) {
                // stopRecording() returns false when LiveKit says "No active recording
                // found" (exception caught internally). Mark as failed to stop retrying.
                $recordingRecord->markAsFailed('Recording stop timed out — '.$minutesOverdue.' minutes overdue');
                Log::warning('[RECORDINGS] Marking stale recording as failed', [
                    'session_id' => $session->id,
                    'recording_id' => $recordingRecord->id,
                    'minutes_overdue' => $minutesOverdue,
                ]);
            } else {
                Log::warning('[RECORDINGS] No active recording to stop', [
                    'session_id' => $session->id,
                ]);
            }
        }

        Log::info('[RECORDINGS] Expired recordings check completed', [
            'stopped' => $stoppedCount,
        ]);

        $this->info("Stopped {$stoppedCount} recordings.");

        return Command::SUCCESS;
    }
}
