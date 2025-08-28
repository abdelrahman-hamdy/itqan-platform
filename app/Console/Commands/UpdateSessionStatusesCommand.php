<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Services\SessionStatusService;
use App\Services\CronJobLogger;
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
                          {--details : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Update session statuses based on current time and business rules';

    private SessionStatusService $sessionStatusService;

    public function __construct(SessionStatusService $sessionStatusService)
    {
        parent::__construct();
        $this->sessionStatusService = $sessionStatusService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isVerbose = $this->option('details') || $isDryRun;
        $academyId = $this->option('academy-id');
        
        // Start enhanced logging
        $executionData = CronJobLogger::logCronStart('sessions:update-statuses', [
            'dry_run' => $isDryRun,
            'verbose' => $isVerbose,
            'academy_id' => $academyId,
        ]);

        $now = now();

        if ($isVerbose) {
            $this->info('🕐 Starting enhanced session status update process...');
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

            // Get all sessions that might need status updates
            $sessionsToProcess = $query->whereIn('status', [
                SessionStatus::SCHEDULED,
                SessionStatus::READY,
                SessionStatus::ONGOING
            ])->with(['academy', 'circle', 'individualCircle', 'meetingAttendances'])->get();

            if ($isVerbose) {
                $this->info("📊 Found {$sessionsToProcess->count()} sessions to process");
            }

            if ($sessionsToProcess->isEmpty()) {
                if ($isVerbose) {
                    $this->info('✅ No sessions require status updates');
                }
                return self::SUCCESS;
            }

            // Process status transitions using the enhanced service
            if ($isDryRun) {
                $stats = $this->simulateStatusTransitions($sessionsToProcess, $isVerbose);
            } else {
                $rawStats = $this->sessionStatusService->processStatusTransitions($sessionsToProcess);
                // Convert to expected format for display
                $stats = [
                    'total_processed' => $sessionsToProcess->count(),
                    'marked_ready' => $rawStats['transitions_to_ready'],
                    'marked_absent' => $rawStats['transitions_to_absent'],
                    'marked_completed' => $rawStats['transitions_to_completed'],
                    'errors' => count($rawStats['errors']),
                    'error_details' => $rawStats['errors'],
                ];
            }

            // Final statistics
            $executionTime = round(microtime(true) - $executionData['start_time'], 2);
            
            if ($isVerbose) {
                $this->info('📊 Session Status Update Results:');
                $this->table(['Metric', 'Count'], [
                    ['Total sessions processed', $stats['total_processed']],
                    ['Marked as ready', $stats['marked_ready']],
                    ['Marked as absent', $stats['marked_absent']],
                    ['Auto-completed sessions', $stats['marked_completed']],
                    ['Errors encountered', $stats['errors']],
                    ['Execution time', "{$executionTime}s"],
                ]);

                // Display errors if any
                if ($stats['errors'] > 0 && isset($stats['error_details'])) {
                    $this->error('❌ Errors encountered:');
                    foreach ($stats['error_details'] as $error) {
                        $this->error("Session {$error['session_id']}: {$error['error']}");
                    }
                }
            }

            // Log summary
            Log::info('Enhanced session status update completed', [
                'execution_time' => $executionTime,
                'stats' => $stats,
                'academy_id' => $academyId,
                'dry_run' => $isDryRun,
            ]);

            // Log completion
            if ($stats['errors'] > 0) {
                CronJobLogger::logCronEnd('sessions:update-statuses', $executionData, $stats, 'warning');
                $this->warn("⚠️  Completed with {$stats['errors']} errors. Check logs for details.");
                return self::FAILURE;
            } else {
                CronJobLogger::logCronEnd('sessions:update-statuses', $executionData, $stats, 'success');
            }

            if ($isVerbose) {
                $this->info('✅ Enhanced session status update completed successfully');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            CronJobLogger::logCronError('sessions:update-statuses', $executionData, $e);
            $this->error('❌ Session status update failed: ' . $e->getMessage());
            Log::error('Enhanced session status update command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'academy_id' => $academyId,
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Simulate status transitions for dry-run mode
     */
    private function simulateStatusTransitions($sessions, bool $isVerbose): array
    {
        $stats = [
            'total_processed' => $sessions->count(),
            'marked_ready' => 0,
            'marked_absent' => 0,
            'marked_completed' => 0,
            'errors' => 0,
        ];

        foreach ($sessions as $session) {
            $currentStatus = $session->status;
            $shouldTransitionToReady = $this->sessionStatusService->shouldTransitionToReady($session);
            $shouldTransitionToAbsent = $this->sessionStatusService->shouldTransitionToAbsent($session);
            $shouldAutoComplete = $this->sessionStatusService->shouldAutoComplete($session);

            if ($shouldTransitionToReady) {
                $stats['marked_ready']++;
                if ($isVerbose) {
                    $circle = $session->session_type === 'individual' ? $session->individualCircle : $session->circle;
                    $prepMinutes = $circle?->preparation_minutes ?? 15;
                    $this->line("🟢 Would mark session {$session->id} as READY ({$prepMinutes}min before start: {$session->scheduled_at->format('H:i')})");
                }
            } elseif ($shouldTransitionToAbsent) {
                $stats['marked_absent']++;
                if ($isVerbose) {
                    $circle = $session->session_type === 'individual' ? $session->individualCircle : $session->circle;
                    $graceMinutes = $circle?->late_join_grace_period_minutes ?? 15;
                    $this->line("🔴 Would mark session {$session->id} as ABSENT (no attendance after {$graceMinutes}min grace period)");
                }
            } elseif ($shouldAutoComplete) {
                $stats['marked_completed']++;
                if ($isVerbose) {
                    $circle = $session->session_type === 'individual' ? $session->individualCircle : $session->circle;
                    $bufferMinutes = $circle?->ending_buffer_minutes ?? 5;
                    $this->line("⏰ Would auto-complete session {$session->id} (exceeded duration + {$bufferMinutes}min buffer)");
                }
            } elseif ($isVerbose) {
                $this->line("⚪ Session {$session->id} remains {$currentStatus->label()}");
            }
        }

        if ($isVerbose) {
            $this->info("\n🔍 Dry run summary:");
            $this->info("• Using circle-specific timing configurations");
            $this->info("• Enhanced business logic for individual vs group sessions");
            $this->info("• Improved absence detection for individual sessions");
        }

        return $stats;
    }
}
