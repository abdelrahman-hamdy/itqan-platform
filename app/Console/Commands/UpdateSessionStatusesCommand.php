<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Services\AcademicSessionStatusService;
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
                          {--details : Show detailed output}
                          {--academic-only : Process only academic sessions}
                          {--quran-only : Process only Quran sessions}';

    /**
     * The console command description.
     */
    protected $description = 'Update session statuses based on current time and business rules';

    private SessionStatusService $sessionStatusService;

    private AcademicSessionStatusService $academicSessionStatusService;

    public function __construct(
        SessionStatusService $sessionStatusService,
        AcademicSessionStatusService $academicSessionStatusService
    ) {
        parent::__construct();
        $this->sessionStatusService = $sessionStatusService;
        $this->academicSessionStatusService = $academicSessionStatusService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isVerbose = $this->option('details') || $isDryRun;
        $academyId = $this->option('academy-id');
        $academicOnly = $this->option('academic-only');
        $quranOnly = $this->option('quran-only');

        // Start enhanced logging
        $executionData = CronJobLogger::logCronStart('sessions:update-statuses', [
            'dry_run' => $isDryRun,
            'verbose' => $isVerbose,
            'academy_id' => $academyId,
            'academic_only' => $academicOnly,
            'quran_only' => $quranOnly,
        ]);

        $now = now();

        if ($isVerbose) {
            $this->info('🕐 Starting enhanced session status update process...');
            $this->info("📅 Current time: {$now->format('Y-m-d H:i:s')}");
            if ($isDryRun) {
                $this->warn('🧪 DRY RUN MODE - No changes will be made');
            }
            if ($academicOnly) {
                $this->info('🎓 Processing only ACADEMIC sessions');
            } elseif ($quranOnly) {
                $this->info('📖 Processing only QURAN sessions');
            } else {
                $this->info('📚 Processing BOTH Quran and Academic sessions');
            }
        }

        try {
            $totalStats = [
                'quran' => ['processed' => 0, 'transitions' => []],
                'academic' => ['processed' => 0, 'transitions' => []],
            ];

            // Process Quran sessions
            if (! $academicOnly) {
                $quranStats = $this->processQuranSessions($academyId, $isDryRun, $isVerbose);
                $totalStats['quran'] = $quranStats;
            }

            // Process Academic sessions
            if (! $quranOnly) {
                $academicStats = $this->processAcademicSessions($academyId, $isDryRun, $isVerbose);
                $totalStats['academic'] = $academicStats;
            }

            // Display combined results
            $this->displayCombinedResults($totalStats, $isDryRun, $isVerbose);

            // Log completion
            CronJobLogger::logCronEnd('sessions:update-statuses', $executionData, [
                'total_processed' => $totalStats['quran']['processed'] + $totalStats['academic']['processed'],
                'quran_stats' => $totalStats['quran'],
                'academic_stats' => $totalStats['academic'],
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

    /**
     * Process Quran sessions
     */
    private function processQuranSessions(?int $academyId, bool $isDryRun, bool $isVerbose): array
    {
        // Get base query
        $query = QuranSession::query();

        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        // Get all sessions that might need status updates
        $sessionsToProcess = $query->whereIn('status', [
            SessionStatus::SCHEDULED,
            SessionStatus::READY,
            SessionStatus::ONGOING,
        ])->with(['academy', 'circle', 'individualCircle', 'meetingAttendances'])->get();

        if ($isVerbose) {
            $this->info("📖 Found {$sessionsToProcess->count()} Quran sessions to process");
        }

        if ($sessionsToProcess->isEmpty()) {
            if ($isVerbose) {
                $this->info('✅ No Quran sessions require status updates');
            }

            return ['processed' => 0, 'transitions' => []];
        }

        // Process status transitions using the enhanced service
        if ($isDryRun) {
            $stats = $this->simulateQuranStatusTransitions($sessionsToProcess, $isVerbose);
        } else {
            $rawStats = $this->sessionStatusService->processStatusTransitions($sessionsToProcess);
            $stats = $this->formatStats($rawStats, $isVerbose);
        }

        return ['processed' => $sessionsToProcess->count(), 'transitions' => $stats];
    }

    /**
     * Process Academic sessions
     */
    private function processAcademicSessions(?int $academyId, bool $isDryRun, bool $isVerbose): array
    {
        // Get base query
        $query = AcademicSession::query();

        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        // Get all sessions that might need status updates
        $sessionsToProcess = $query->whereIn('status', [
            SessionStatus::SCHEDULED,
            SessionStatus::READY,
            SessionStatus::ONGOING,
        ])->with(['academy', 'academicTeacher', 'student'])->get();

        if ($isVerbose) {
            $this->info("🎓 Found {$sessionsToProcess->count()} Academic sessions to process");
        }

        if ($sessionsToProcess->isEmpty()) {
            if ($isVerbose) {
                $this->info('✅ No Academic sessions require status updates');
            }

            return ['processed' => 0, 'transitions' => []];
        }

        // Process status transitions using the academic service
        if ($isDryRun) {
            $stats = $this->simulateAcademicStatusTransitions($sessionsToProcess, $isVerbose);
        } else {
            $rawStats = $this->academicSessionStatusService->processStatusTransitions($sessionsToProcess);
            $stats = $this->formatStats($rawStats, $isVerbose);
        }

        return ['processed' => $sessionsToProcess->count(), 'transitions' => $stats];
    }

    /**
     * Simulate Quran status transitions for dry run mode
     */
    private function simulateQuranStatusTransitions($sessions, bool $isVerbose): array
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
                    $stats['details'][] = "Would transition Quran session {$session->id} from SCHEDULED to READY";

                    if ($isVerbose) {
                        $this->info("🔄 Would transition Quran session {$session->id} to READY");
                    }
                }

                // Check for ABSENT transition (individual sessions only)
                if ($this->sessionStatusService->shouldTransitionToAbsent($session)) {
                    $stats['ready_to_absent']++;
                    $stats['details'][] = "Would transition Quran session {$session->id} from READY to ABSENT";

                    if ($isVerbose) {
                        $this->info("⏰ Would mark Quran session {$session->id} as ABSENT");
                    }
                }

                // Check for auto-completion
                if ($this->sessionStatusService->shouldAutoComplete($session)) {
                    $stats['ongoing_to_completed']++;
                    $stats['details'][] = "Would transition Quran session {$session->id} from ONGOING to COMPLETED";

                    if ($isVerbose) {
                        $this->info("✅ Would auto-complete Quran session {$session->id}");
                    }
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $stats['details'][] = "Error processing Quran session {$session->id}: {$e->getMessage()}";

                if ($isVerbose) {
                    $this->error("❌ Error simulating Quran session {$session->id}: {$e->getMessage()}");
                }
            }
        }

        return $stats;
    }

    /**
     * Simulate Academic status transitions for dry run mode
     */
    private function simulateAcademicStatusTransitions($sessions, bool $isVerbose): array
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
                if ($this->academicSessionStatusService->shouldTransitionToReady($session)) {
                    $stats['scheduled_to_ready']++;
                    $stats['details'][] = "Would transition Academic session {$session->id} from SCHEDULED to READY";

                    if ($isVerbose) {
                        $this->info("🔄 Would transition Academic session {$session->id} to READY");
                    }
                }

                // Check for ABSENT transition (individual sessions only)
                if ($this->academicSessionStatusService->shouldTransitionToAbsent($session)) {
                    $stats['ready_to_absent']++;
                    $stats['details'][] = "Would transition Academic session {$session->id} from READY to ABSENT";

                    if ($isVerbose) {
                        $this->info("⏰ Would mark Academic session {$session->id} as ABSENT");
                    }
                }

                // Check for auto-completion
                if ($this->academicSessionStatusService->shouldAutoComplete($session)) {
                    $stats['ongoing_to_completed']++;
                    $stats['details'][] = "Would transition Academic session {$session->id} from ONGOING to COMPLETED";

                    if ($isVerbose) {
                        $this->info("✅ Would auto-complete Academic session {$session->id}");
                    }
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $stats['details'][] = "Error processing Academic session {$session->id}: {$e->getMessage()}";

                if ($isVerbose) {
                    $this->error("❌ Error simulating Academic session {$session->id}: {$e->getMessage()}");
                }
            }
        }

        return $stats;
    }

    /**
     * Display combined results for both session types
     */
    private function displayCombinedResults(array $totalStats, bool $isDryRun, bool $isVerbose): void
    {
        $mode = $isDryRun ? 'Simulation' : 'Execution';
        $this->info("\n📊 {$mode} Results:");
        $this->info('═══════════════════════════════════════════════════');

        $quranStats = $totalStats['quran']['transitions'];
        $academicStats = $totalStats['academic']['transitions'];

        $quranTransitions = ($quranStats['scheduled_to_ready'] ?? 0) +
                           ($quranStats['ready_to_absent'] ?? 0) +
                           ($quranStats['ongoing_to_completed'] ?? 0);

        $academicTransitions = ($academicStats['scheduled_to_ready'] ?? 0) +
                              ($academicStats['ready_to_absent'] ?? 0) +
                              ($academicStats['ongoing_to_completed'] ?? 0);

        $totalTransitions = $quranTransitions + $academicTransitions;

        if ($totalTransitions === 0 && ($quranStats['errors'] ?? 0) === 0 && ($academicStats['errors'] ?? 0) === 0) {
            $this->info('✅ No status changes required - all sessions are in correct states');

            return;
        }

        // Quran session results
        if ($totalStats['quran']['processed'] > 0) {
            $this->info("\n📖 Quran Sessions ({$totalStats['quran']['processed']} processed):");
            $this->displaySessionTypeResults($quranStats, $isDryRun, $isVerbose, 'Quran');
        }

        // Academic session results
        if ($totalStats['academic']['processed'] > 0) {
            $this->info("\n🎓 Academic Sessions ({$totalStats['academic']['processed']} processed):");
            $this->displaySessionTypeResults($academicStats, $isDryRun, $isVerbose, 'Academic');
        }

        $this->info("\n📈 Total Summary: {$totalTransitions} status transitions processed");
    }

    /**
     * Display results for a specific session type
     */
    private function displaySessionTypeResults(array $stats, bool $isDryRun, bool $isVerbose, string $type): void
    {
        $totalTransitions = ($stats['scheduled_to_ready'] ?? 0) +
                           ($stats['ready_to_absent'] ?? 0) +
                           ($stats['ongoing_to_completed'] ?? 0);

        if ($totalTransitions === 0 && ($stats['errors'] ?? 0) === 0) {
            $this->info("  ✅ No {$type} sessions require status updates");

            return;
        }

        if (($stats['scheduled_to_ready'] ?? 0) > 0) {
            $verb = $isDryRun ? 'Would transition' : 'Transitioned';
            $this->info("  🔄 {$verb} {$stats['scheduled_to_ready']} {$type} sessions from SCHEDULED to READY");
        }

        if (($stats['ready_to_absent'] ?? 0) > 0) {
            $verb = $isDryRun ? 'Would mark' : 'Marked';
            $this->info("  ⏰ {$verb} {$stats['ready_to_absent']} {$type} individual sessions as ABSENT");
        }

        if (($stats['ongoing_to_completed'] ?? 0) > 0) {
            $verb = $isDryRun ? 'Would auto-complete' : 'Auto-completed';
            $this->info("  ✅ {$verb} {$stats['ongoing_to_completed']} ongoing {$type} sessions");
        }

        if (($stats['errors'] ?? 0) > 0) {
            $this->error("  ❌ Encountered {$stats['errors']} errors during {$type} processing");

            if ($isVerbose && ! empty($stats['details'])) {
                $this->error('  Error details:');
                foreach ($stats['details'] as $detail) {
                    if (strpos($detail, 'Error') !== false) {
                        $this->error("    • {$detail}");
                    }
                }
            }
        }
    }
}
