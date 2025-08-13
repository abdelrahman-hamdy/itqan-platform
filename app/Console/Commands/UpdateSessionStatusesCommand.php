<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateSessionStatusesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sessions:update-statuses 
                          {--academy-id= : Process only specific academy ID}
                          {--dry-run : Show what would be done without actually updating sessions}
                          {--verbose : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Update session statuses based on current time and business rules';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $now = now();
        $isDryRun = $this->option('dry-run');
        $isVerbose = $this->option('verbose') || $isDryRun;
        $academyId = $this->option('academy-id');

        if ($isVerbose) {
            $this->info('🕐 Starting session status update process...');
            $this->info("📅 Current time: {$now->format('Y-m-d H:i:s')}");
            if ($isDryRun) {
                $this->warn('🧪 DRY RUN MODE - No changes will be made');
            }
        }

        try {
            // Get base query
            $query = QuranSession::query();
            
            if ($academyId) {
                $query->where('academy_id', $academyId);
                if ($isVerbose) {
                    $this->info("🎯 Processing only academy ID: {$academyId}");
                }
            }

            // Track statistics
            $stats = [
                'total_processed' => 0,
                'marked_ready' => 0,
                'marked_missed' => 0,
                'marked_ended' => 0,
                'errors' => 0,
            ];

            // 1. Mark scheduled sessions as ready (within 30 minutes before start time)
            $this->updateScheduledToReady($query, $now, $isDryRun, $isVerbose, $stats);

            // 2. Mark sessions as missed (past start time + grace period with no attendance)
            $this->updateScheduledToMissed($query, $now, $isDryRun, $isVerbose, $stats);

            // 3. Auto-end ongoing sessions that have exceeded their duration
            $this->updateOngoingToCompleted($query, $now, $isDryRun, $isVerbose, $stats);

            // Final statistics
            $executionTime = round(microtime(true) - $startTime, 2);
            
            if ($isVerbose) {
                $this->info('📊 Session Status Update Results:');
                $this->table(['Metric', 'Count'], [
                    ['Total sessions processed', $stats['total_processed']],
                    ['Marked as ready', $stats['marked_ready']],
                    ['Marked as missed', $stats['marked_missed']],
                    ['Auto-ended sessions', $stats['marked_ended']],
                    ['Errors encountered', $stats['errors']],
                    ['Execution time', "{$executionTime}s"],
                ]);
            }

            // Log summary
            Log::info('Session status update completed', [
                'execution_time' => $executionTime,
                'stats' => $stats,
                'academy_id' => $academyId,
                'dry_run' => $isDryRun,
            ]);

            if ($stats['errors'] > 0) {
                $this->warn("⚠️  Completed with {$stats['errors']} errors. Check logs for details.");
                return self::FAILURE;
            }

            if ($isVerbose) {
                $this->info('✅ Session status update completed successfully');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Session status update failed: ' . $e->getMessage());
            Log::error('Session status update command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'academy_id' => $academyId,
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Update scheduled sessions to ready status
     */
    private function updateScheduledToReady($baseQuery, Carbon $now, bool $isDryRun, bool $isVerbose, array &$stats): void
    {
        // Sessions that should be marked as ready (within 30 minutes of start time)
        $readyThreshold = $now->copy()->addMinutes(30);
        
        $sessionsToMarkReady = (clone $baseQuery)
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '<=', $readyThreshold)
            ->where('scheduled_at', '>', $now->copy()->subMinutes(10)) // Not too far in the past
            ->whereNotNull('scheduled_at')
            ->get();

        foreach ($sessionsToMarkReady as $session) {
            $stats['total_processed']++;
            
            try {
                if ($isVerbose) {
                    $this->line("🟢 Marking session {$session->id} as ready (scheduled at {$session->scheduled_at->format('H:i')})");
                }

                if (!$isDryRun) {
                    $session->update(['status' => SessionStatus::READY->value]);
                }
                
                $stats['marked_ready']++;
                
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Failed to mark session as ready', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
                
                if ($isVerbose) {
                    $this->error("❌ Failed to update session {$session->id}: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Update scheduled sessions to missed status
     */
    private function updateScheduledToMissed($baseQuery, Carbon $now, bool $isDryRun, bool $isVerbose, array &$stats): void
    {
        // Sessions that should be marked as missed (15 minutes past start time with no attendance)
        $missedThreshold = $now->copy()->subMinutes(15);
        
        $sessionsToMarkMissed = (clone $baseQuery)
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::READY->value])
            ->where('scheduled_at', '<', $missedThreshold)
            ->whereNotNull('scheduled_at')
            ->whereDoesntHave('attendances', function($query) {
                $query->where('attendance_status', 'present');
            })
            ->get();

        foreach ($sessionsToMarkMissed as $session) {
            $stats['total_processed']++;
            
            try {
                if ($isVerbose) {
                    $this->line("🔴 Marking session {$session->id} as missed (was scheduled at {$session->scheduled_at->format('H:i')})");
                }

                if (!$isDryRun) {
                    $session->update([
                        'status' => SessionStatus::MISSED->value,
                        'attendance_status' => 'absent',
                    ]);

                    // Record attendance as missed for individual sessions
                    if ($session->session_type === 'individual' && $session->student_id) {
                        $session->recordSessionAttendance('absent');
                    }
                }
                
                $stats['marked_missed']++;
                
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Failed to mark session as missed', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
                
                if ($isVerbose) {
                    $this->error("❌ Failed to update session {$session->id}: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Auto-end ongoing sessions that have exceeded their duration
     */
    private function updateOngoingToCompleted($baseQuery, Carbon $now, bool $isDryRun, bool $isVerbose, array &$stats): void
    {
        $sessionsToEnd = (clone $baseQuery)
            ->where('status', SessionStatus::ONGOING->value)
            ->whereNotNull('started_at')
            ->get()
            ->filter(function ($session) use ($now) {
                // End if session has been running for longer than expected duration + 30 minute buffer
                $expectedEndTime = $session->started_at->copy()->addMinutes(($session->duration_minutes ?? 60) + 30);
                return $now->isAfter($expectedEndTime);
            });

        foreach ($sessionsToEnd as $session) {
            $stats['total_processed']++;
            
            try {
                $runningTime = $session->started_at->diffInMinutes($now);
                
                if ($isVerbose) {
                    $this->line("⏹️  Auto-ending session {$session->id} (running for {$runningTime} minutes)");
                }

                if (!$isDryRun) {
                    $session->update([
                        'status' => SessionStatus::COMPLETED->value,
                        'ended_at' => $now,
                        'actual_duration_minutes' => $runningTime,
                        'attendance_status' => 'attended', // Assume attended if it was ongoing
                    ]);

                    // Update circle progress if applicable
                    if ($session->individualCircle) {
                        $session->individualCircle->updateProgress();
                    }

                    // Record attendance as present (since session was ongoing)
                    $session->recordSessionAttendance('present');
                }
                
                $stats['marked_ended']++;
                
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Failed to auto-end ongoing session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
                
                if ($isVerbose) {
                    $this->error("❌ Failed to end session {$session->id}: {$e->getMessage()}");
                }
            }
        }
    }
}
