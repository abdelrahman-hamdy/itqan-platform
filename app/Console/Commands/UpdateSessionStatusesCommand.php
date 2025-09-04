<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Services\CronJobLogger;
use App\Services\SessionStatusService;
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
                SessionStatus::ONGOING,
            ])->with(['academy', 'circle', 'individualCircle', 'meetingAttendances'])->get();

            if ($isVerbose) {
                $this->info("📊 Found {$sessionsToProcess->count()} sessions to process");
            }

            CronJobLogger::logCronProgress('sessions:update-statuses', $executionData['execution_id'],
                "Found {$sessionsToProcess->count()} sessions to process", [
                    'sessions_count' => $sessionsToProcess->count(),
                    'academy_id' => $academyId,
                ]);

            if ($sessionsToProcess->isEmpty()) {
                if ($isVerbose) {
                    $this->info('✅ No sessions require status updates');
                }

                CronJobLogger::logCronEnd('sessions:update-statuses', $executionData, [
                    'processed_sessions' => 0,
                    'transitions' => [],
                ]);

                return self::SUCCESS;
            }

            // Process status transitions using the enhanced service
            if ($isDryRun) {
                $stats = $this->simulateStatusTransitions($sessionsToProcess, $isVerbose);
            } else {
                $rawStats = $this->sessionStatusService->processStatusTransitions($sessionsToProcess);
                $stats = $this->formatStats($rawStats, $isVerbose);
            }

            // Display results
            $this->displayResults($stats, $isDryRun, $isVerbose);

            // Log completion
            CronJobLogger::logCronEnd('sessions:update-statuses', $executionData, [
                'processed_sessions' => $sessionsToProcess->count(),
                'transitions' => $stats,
                'academy_id' => $academyId,
                'dry_run' => $isDryRun,
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Session status update failed: '.$e->getMessage());

            if ($isVerbose) {
                $this->error('Stack trace: '.$e->getTraceAsString());
            }

            CronJobLogger::logCronError('sessions:update-statuses', $executionData, $e);

            return self::FAILURE;
        }
    }

    /**
     * Simulate status transitions for dry run mode
     */
    private function simulateStatusTransitions($sessions, bool $isVerbose): array
    {
        $stats = [
            'scheduled_to_ready' => 0,
            'ready_to_absent' => 0,
            'ongoing_to_completed' => 0,
            'errors' => 0,
            'details' => [],
        ];

        foreach ($sessions as $session) {
            try {
                // Check for READY transition
                if ($this->sessionStatusService->shouldTransitionToReady($session)) {
                    $stats['scheduled_to_ready']++;
                    $stats['details'][] = "Would transition session {$session->id} from SCHEDULED to READY";

                    if ($isVerbose) {
                        $this->info("🔄 Would transition session {$session->id} to READY");
                    }
                }

                // Check for ABSENT transition (individual sessions only)
                if ($this->sessionStatusService->shouldTransitionToAbsent($session)) {
                    $stats['ready_to_absent']++;
                    $stats['details'][] = "Would transition session {$session->id} from READY to ABSENT";

                    if ($isVerbose) {
                        $this->info("⏰ Would mark session {$session->id} as ABSENT");
                    }
                }

                // Check for auto-completion
                if ($this->sessionStatusService->shouldAutoComplete($session)) {
                    $stats['ongoing_to_completed']++;
                    $stats['details'][] = "Would transition session {$session->id} from ONGOING to COMPLETED";

                    if ($isVerbose) {
                        $this->info("✅ Would auto-complete session {$session->id}");
                    }
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $stats['details'][] = "Error processing session {$session->id}: {$e->getMessage()}";

                if ($isVerbose) {
                    $this->error("❌ Error simulating session {$session->id}: {$e->getMessage()}");
                }
            }
        }

        return $stats;
    }

    /**
     * Format raw stats from the service
     */
    private function formatStats(array $rawStats, bool $isVerbose): array
    {
        $stats = [
            'scheduled_to_ready' => $rawStats['transitions_to_ready'] ?? 0,
            'ready_to_absent' => $rawStats['transitions_to_absent'] ?? 0,
            'ongoing_to_completed' => $rawStats['transitions_to_completed'] ?? 0,
            'errors' => count($rawStats['errors'] ?? []),
            'details' => [],
        ];

        // Add error details
        foreach ($rawStats['errors'] ?? [] as $error) {
            $stats['details'][] = "Error processing session {$error['session_id']}: {$error['error']}";

            if ($isVerbose) {
                $this->error("❌ Session {$error['session_id']}: {$error['error']}");
            }
        }

        return $stats;
    }

    /**
     * Display execution results
     */
    private function displayResults(array $stats, bool $isDryRun, bool $isVerbose): void
    {
        $mode = $isDryRun ? 'Simulation' : 'Execution';
        $this->info("\n📊 {$mode} Results:");
        $this->info('═══════════════════════════════════════════════════');

        $totalTransitions = $stats['scheduled_to_ready'] + $stats['ready_to_absent'] + $stats['ongoing_to_completed'];

        if ($totalTransitions === 0 && $stats['errors'] === 0) {
            $this->info('✅ No status changes required - all sessions are in correct states');

            return;
        }

        if ($stats['scheduled_to_ready'] > 0) {
            $verb = $isDryRun ? 'Would transition' : 'Transitioned';
            $this->info("🔄 {$verb} {$stats['scheduled_to_ready']} sessions from SCHEDULED to READY");
        }

        if ($stats['ready_to_absent'] > 0) {
            $verb = $isDryRun ? 'Would mark' : 'Marked';
            $this->info("⏰ {$verb} {$stats['ready_to_absent']} individual sessions as ABSENT");
        }

        if ($stats['ongoing_to_completed'] > 0) {
            $verb = $isDryRun ? 'Would auto-complete' : 'Auto-completed';
            $this->info("✅ {$verb} {$stats['ongoing_to_completed']} ongoing sessions");
        }

        if ($stats['errors'] > 0) {
            $this->error("❌ Encountered {$stats['errors']} errors during processing");

            if ($isVerbose && ! empty($stats['details'])) {
                $this->error('Error details:');
                foreach ($stats['details'] as $detail) {
                    if (strpos($detail, 'Error') !== false) {
                        $this->error("  • {$detail}");
                    }
                }
            }
        }

        $this->info("\n📈 Summary: {$totalTransitions} status transitions processed");
    }
}
