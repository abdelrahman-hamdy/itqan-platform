<?php

namespace App\Console\Commands;

use App\Contracts\RecordingCapable;
use App\Enums\RecordingStatus;
use App\Models\SessionRecording;
use Exception;
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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recordings:stop-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop recordings for sessions that have reached their scheduled end time';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Log::info('[RECORDINGS] Starting expired recordings check');

        $stoppedCount = 0;
        $errorCount = 0;

        // Find ALL active recordings (any session type) via SessionRecording directly
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
            // Use recording's actual start time (not session scheduled_at) + session duration + 15 min buffer.
            // Teachers often join 5-10 min after scheduled time, so using scheduled_at
            // would stop the recording before the session actually finishes.
            $bufferMinutes = 15;
            $durationMinutes = $session->duration_minutes ?? 60;
            $recordingStartTime = $recordingRecord->started_at ?? $session->scheduled_at;

            if (! $recordingStartTime) {
                continue;
            }

            $expectedEndTime = $recordingStartTime->copy()->addMinutes($durationMinutes + $bufferMinutes);

            if (now()->lt($expectedEndTime)) {
                continue;
            }

            Log::info('[RECORDINGS] Stopping recording for expired session', [
                'session_id' => $session->id,
                'recording_started' => $recordingStartTime->toISOString(),
                'expected_end' => $expectedEndTime->toISOString(),
                'minutes_overdue' => now()->diffInMinutes($expectedEndTime),
            ]);

            try {
                if ($session instanceof RecordingCapable) {
                    $stopped = $session->stopRecording();

                    if ($stopped) {
                        $stoppedCount++;
                        Log::info('[RECORDINGS] Recording stopped successfully', [
                            'session_id' => $session->id,
                        ]);
                    } else {
                        Log::warning('[RECORDINGS] No active recording to stop', [
                            'session_id' => $session->id,
                        ]);
                    }
                }
            } catch (Exception $e) {
                // "No active recording found" means egress already ended — mark as
                // failed so this record isn't retried every minute indefinitely.
                if (str_contains($e->getMessage(), 'No active recording found')) {
                    $recordingRecord->markAsFailed('Egress already ended: '.$e->getMessage());
                    Log::warning('[RECORDINGS] Marking stale recording as failed', [
                        'session_id' => $session->id,
                        'recording_id' => $recordingRecord->id,
                    ]);
                } else {
                    $errorCount++;
                    Log::error('[RECORDINGS] Failed to stop recording', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
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
