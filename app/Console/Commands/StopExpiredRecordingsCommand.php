<?php

namespace App\Console\Commands;

use App\Contracts\RecordingCapable;
use App\Enums\RecordingStatus;
use App\Models\InteractiveCourseSession;
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
        Log::info('🎬 [RECORDINGS] Starting expired recordings check');

        $stoppedCount = 0;
        $errorCount = 0;

        // Find InteractiveCourseSession instances with active recordings
        $sessionsWithActiveRecordings = InteractiveCourseSession::query()
            ->whereHas('recordings', function ($query) {
                $query->where('status', RecordingStatus::RECORDING->value);
            })
            ->with(['recordings' => function ($query) {
                $query->where('status', RecordingStatus::RECORDING->value);
            }])
            ->get();

        Log::info('🎬 [RECORDINGS] Found sessions with active recordings', [
            'count' => $sessionsWithActiveRecordings->count(),
        ]);

        foreach ($sessionsWithActiveRecordings as $session) {
            // Calculate session end time
            $scheduledEndTime = $session->scheduled_at
                ? $session->scheduled_at->copy()->addMinutes($session->duration_minutes ?? 60)
                : null;

            // Skip if no scheduled_at or end time not reached yet
            if (!$scheduledEndTime || now()->lt($scheduledEndTime)) {
                Log::debug('🎬 [RECORDINGS] Session end time not yet reached', [
                    'session_id' => $session->id,
                    'scheduled_at' => $session->scheduled_at?->toISOString(),
                    'scheduled_end' => $scheduledEndTime?->toISOString(),
                    'current_time' => now()->toISOString(),
                ]);
                continue;
            }

            // Session has passed its scheduled end time - stop recording
            Log::info('🎬 [RECORDINGS] Stopping recording for expired session', [
                'session_id' => $session->id,
                'scheduled_at' => $session->scheduled_at->toISOString(),
                'scheduled_end' => $scheduledEndTime->toISOString(),
                'minutes_overdue' => now()->diffInMinutes($scheduledEndTime),
            ]);

            try {
                if ($session instanceof RecordingCapable) {
                    $stopped = $session->stopRecording();

                    if ($stopped) {
                        $stoppedCount++;
                        Log::info('✅ [RECORDINGS] Recording stopped successfully', [
                            'session_id' => $session->id,
                        ]);
                    } else {
                        Log::warning('⚠️ [RECORDINGS] No active recording to stop', [
                            'session_id' => $session->id,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('❌ [RECORDINGS] Failed to stop recording', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('🎬 [RECORDINGS] Expired recordings check completed', [
            'stopped' => $stoppedCount,
            'errors' => $errorCount,
        ]);

        $this->info("Stopped {$stoppedCount} recordings, {$errorCount} errors.");

        return Command::SUCCESS;
    }
}
