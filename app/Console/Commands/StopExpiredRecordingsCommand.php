<?php

namespace App\Console\Commands;

use App\Contracts\RecordingCapable;
use App\Enums\RecordingStatus;
use App\Models\SessionRecording;
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

    public function handle(): int
    {
        Log::info('[RECORDINGS] Starting expired recordings check');

        $stoppedCount = 0;
        $errorCount = 0;

        $activeRecordings = SessionRecording::where('status', RecordingStatus::RECORDING)
            ->with('recordable')
            ->get();

        Log::info('[RECORDINGS] Found active recordings', [
            'count' => $activeRecordings->count(),
        ]);

        foreach ($activeRecordings as $recordingRecord) {
            $session = $recordingRecord->recordable;

            if (! $session) {
                continue;
            }
            // Use recording's actual start time + session duration + 15 min buffer.
            // Teachers often join 5-10 min after scheduled time, so using scheduled_at
            // would stop the recording before the session actually finishes.
            $bufferMinutes = 15;
            $durationMinutes = $session->duration_minutes ?? 60;
            $recordingStartTime = $recordingRecord->started_at ?? $session->scheduled_at;

            if (! $recordingStartTime) {
                continue;
            }

            $expectedEndTime = $recordingStartTime->copy()->addMinutes($durationMinutes + $bufferMinutes);
            $minutesOverdue = (int) now()->diffInMinutes($expectedEndTime);

            if ($minutesOverdue <= 0) {
                continue;
            }

            Log::info('[RECORDINGS] Stopping recording for expired session', [
                'session_id' => $session->id,
                'recording_started' => $recordingStartTime->toISOString(),
                'expected_end' => $expectedEndTime->toISOString(),
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
            'errors' => $errorCount,
        ]);

        $this->info("Stopped {$stoppedCount} recordings, {$errorCount} errors.");

        return Command::SUCCESS;
    }
}
