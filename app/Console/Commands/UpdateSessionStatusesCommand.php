<?php

namespace App\Console\Commands;

use Exception;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\CronJobLogger;
use App\Services\UnifiedSessionStatusService;
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
                          {--quran-only : Process only Quran sessions}
                          {--interactive-only : Process only interactive course sessions}';

    /**
     * The console command description.
     */
    protected $description = 'Update session statuses based on current time and business rules';

    private UnifiedSessionStatusService $statusService;

    private CronJobLogger $cronJobLogger;

    public function __construct(UnifiedSessionStatusService $statusService, CronJobLogger $cronJobLogger)
    {
        parent::__construct();
        $this->statusService = $statusService;
        $this->cronJobLogger = $cronJobLogger;
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
        $interactiveOnly = $this->option('interactive-only');

        // Start enhanced logging
        $executionData = $this->cronJobLogger->logCronStart('sessions:update-statuses', [
            'dry_run' => $isDryRun,
            'verbose' => $isVerbose,
            'academy_id' => $academyId,
            'academic_only' => $academicOnly,
            'quran_only' => $quranOnly,
            'interactive_only' => $interactiveOnly,
        ]);

        $now = now();

        if ($isVerbose) {
            $this->info('Starting session status update process (Unified Service)...');
            $this->info("Current time: {$now->format('Y-m-d H:i:s')}");
            if ($isDryRun) {
                $this->warn('DRY RUN MODE - No changes will be made');
            }
            if ($academicOnly) {
                $this->info('Processing only ACADEMIC sessions');
            } elseif ($quranOnly) {
                $this->info('Processing only QURAN sessions');
            } elseif ($interactiveOnly) {
                $this->info('Processing only INTERACTIVE COURSE sessions');
            } else {
                $this->info('Processing ALL session types (Quran, Academic, Interactive)');
            }
        }

        try {
            $totalStats = [
                'quran' => ['processed' => 0, 'transitions' => []],
                'academic' => ['processed' => 0, 'transitions' => []],
                'interactive' => ['processed' => 0, 'transitions' => []],
            ];

            // Process Quran sessions
            if (! $academicOnly && ! $interactiveOnly) {
                $quranStats = $this->processQuranSessions($academyId, $isDryRun, $isVerbose);
                $totalStats['quran'] = $quranStats;
            }

            // Process Academic sessions
            if (! $quranOnly && ! $interactiveOnly) {
                $academicStats = $this->processAcademicSessions($academyId, $isDryRun, $isVerbose);
                $totalStats['academic'] = $academicStats;
            }

            // Process Interactive course sessions
            if (! $quranOnly && ! $academicOnly) {
                $interactiveStats = $this->processInteractiveSessions($academyId, $isDryRun, $isVerbose);
                $totalStats['interactive'] = $interactiveStats;
            }

            // Display combined results
            $this->displayCombinedResults($totalStats, $isDryRun, $isVerbose);

            // Log completion
            $totalProcessed = $totalStats['quran']['processed'] +
                             $totalStats['academic']['processed'] +
                             $totalStats['interactive']['processed'];

            $this->cronJobLogger->logCronEnd('sessions:update-statuses', $executionData, [
                'total_processed' => $totalProcessed,
                'quran_stats' => $totalStats['quran'],
                'academic_stats' => $totalStats['academic'],
                'interactive_stats' => $totalStats['interactive'],
                'academy_id' => $academyId,
                'dry_run' => $isDryRun,
            ]);

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error('Session status update failed: '.$e->getMessage());

            if ($isVerbose) {
                $this->error('Stack trace: '.$e->getTraceAsString());
            }

            $this->cronJobLogger->logCronError('sessions:update-statuses', $executionData, $e);

            return self::FAILURE;
        }
    }

    /**
     * Simulate status transitions for dry run mode (generic method)
     */
    private function simulateStatusTransitions($sessions, bool $isVerbose, string $sessionType = 'session'): array
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
                if ($this->statusService->shouldTransitionToReady($session)) {
                    $stats['scheduled_to_ready']++;
                    $stats['details'][] = "Would transition {$sessionType} {$session->id} from SCHEDULED to READY";

                    if ($isVerbose) {
                        $this->info("🔄 Would transition {$sessionType} {$session->id} to READY");
                    }
                }

                // Check for ABSENT transition (individual sessions only)
                if ($this->statusService->shouldTransitionToAbsent($session)) {
                    $stats['ready_to_absent']++;
                    $stats['details'][] = "Would transition {$sessionType} {$session->id} from READY to ABSENT";

                    if ($isVerbose) {
                        $this->info("⏰ Would mark {$sessionType} {$session->id} as ABSENT");
                    }
                }

                // Check for auto-completion
                if ($this->statusService->shouldAutoComplete($session)) {
                    $stats['ongoing_to_completed']++;
                    $stats['details'][] = "Would transition {$sessionType} {$session->id} from ONGOING to COMPLETED";

                    if ($isVerbose) {
                        $this->info("✅ Would auto-complete {$sessionType} {$session->id}");
                    }
                }

            } catch (Exception $e) {
                $stats['errors']++;
                $stats['details'][] = "Error processing {$sessionType} {$session->id}: {$e->getMessage()}";

                if ($isVerbose) {
                    $this->error("❌ Error simulating {$sessionType} {$session->id}: {$e->getMessage()}");
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

        $query->active();

        // Count total sessions for progress tracking
        $totalCount = $query->count();

        if ($isVerbose) {
            $this->info("📖 Found {$totalCount} Quran sessions to process");
        }

        if ($totalCount === 0) {
            if ($isVerbose) {
                $this->info('✅ No Quran sessions require status updates');
            }

            return ['processed' => 0, 'transitions' => []];
        }

        // Initialize stats
        $stats = [
            'scheduled_to_ready' => 0,
            'ready_to_absent' => 0,
            'ongoing_to_completed' => 0,
            'errors' => 0,
            'details' => [],
        ];
        $processedCount = 0;

        // Process in chunks to prevent memory issues
        $query->with(['academy', 'circle', 'individualCircle', 'meetingAttendances'])
            ->chunkById(200, function ($sessions) use (&$stats, &$processedCount, $isDryRun, $isVerbose) {
                // Process status transitions using the unified service
                if ($isDryRun) {
                    $chunkStats = $this->simulateStatusTransitions($sessions, $isVerbose, 'Quran session');
                } else {
                    $rawStats = $this->statusService->processStatusTransitions($sessions);
                    $chunkStats = $this->formatStats($rawStats, $isVerbose);
                }

                // Merge chunk stats into overall stats
                $stats['scheduled_to_ready'] += $chunkStats['scheduled_to_ready'];
                $stats['ready_to_absent'] += $chunkStats['ready_to_absent'];
                $stats['ongoing_to_completed'] += $chunkStats['ongoing_to_completed'];
                $stats['errors'] += $chunkStats['errors'];
                $stats['details'] = array_merge($stats['details'], $chunkStats['details']);

                $processedCount += $sessions->count();
            });

        return ['processed' => $processedCount, 'transitions' => $stats];
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

        $query->active();

        // Count total sessions for progress tracking
        $totalCount = $query->count();

        if ($isVerbose) {
            $this->info("🎓 Found {$totalCount} Academic sessions to process");
        }

        if ($totalCount === 0) {
            if ($isVerbose) {
                $this->info('✅ No Academic sessions require status updates');
            }

            return ['processed' => 0, 'transitions' => []];
        }

        // Initialize stats
        $stats = [
            'scheduled_to_ready' => 0,
            'ready_to_absent' => 0,
            'ongoing_to_completed' => 0,
            'errors' => 0,
            'details' => [],
        ];
        $processedCount = 0;

        // Process in chunks to prevent memory issues
        $query->with(['academy', 'academicTeacher', 'student'])
            ->chunkById(200, function ($sessions) use (&$stats, &$processedCount, $isDryRun, $isVerbose) {
                // Process status transitions using the unified service
                if ($isDryRun) {
                    $chunkStats = $this->simulateStatusTransitions($sessions, $isVerbose, 'Academic session');
                } else {
                    $rawStats = $this->statusService->processStatusTransitions($sessions);
                    $chunkStats = $this->formatStats($rawStats, $isVerbose);
                }

                // Merge chunk stats into overall stats
                $stats['scheduled_to_ready'] += $chunkStats['scheduled_to_ready'];
                $stats['ready_to_absent'] += $chunkStats['ready_to_absent'];
                $stats['ongoing_to_completed'] += $chunkStats['ongoing_to_completed'];
                $stats['errors'] += $chunkStats['errors'];
                $stats['details'] = array_merge($stats['details'], $chunkStats['details']);

                $processedCount += $sessions->count();
            });

        return ['processed' => $processedCount, 'transitions' => $stats];
    }

    /**
     * Process Interactive Course sessions
     */
    private function processInteractiveSessions(?int $academyId, bool $isDryRun, bool $isVerbose): array
    {
        // Get base query
        $query = InteractiveCourseSession::query();

        // InteractiveCourseSession doesn't have academy_id directly, it gets it through course
        if ($academyId) {
            $query->whereHas('course', function ($q) use ($academyId) {
                $q->where('academy_id', $academyId);
            });
        }

        $query->active();

        // Count total sessions for progress tracking
        $totalCount = $query->count();

        if ($isVerbose) {
            $this->info("🎬 Found {$totalCount} Interactive Course sessions to process");
        }

        if ($totalCount === 0) {
            if ($isVerbose) {
                $this->info('✅ No Interactive Course sessions require status updates');
            }

            return ['processed' => 0, 'transitions' => []];
        }

        // Initialize stats
        $stats = [
            'scheduled_to_ready' => 0,
            'ready_to_absent' => 0,
            'ongoing_to_completed' => 0,
            'errors' => 0,
            'details' => [],
        ];
        $processedCount = 0;

        // Process in chunks to prevent memory issues
        $query->with(['course.academy', 'course.assignedTeacher.user'])
            ->chunkById(200, function ($sessions) use (&$stats, &$processedCount, $isDryRun, $isVerbose) {
                // Process status transitions using the unified service
                if ($isDryRun) {
                    $chunkStats = $this->simulateStatusTransitions($sessions, $isVerbose, 'Interactive session');
                } else {
                    $rawStats = $this->statusService->processStatusTransitions($sessions);
                    $chunkStats = $this->formatStats($rawStats, $isVerbose);
                }

                // Merge chunk stats into overall stats
                $stats['scheduled_to_ready'] += $chunkStats['scheduled_to_ready'];
                $stats['ready_to_absent'] += $chunkStats['ready_to_absent'];
                $stats['ongoing_to_completed'] += $chunkStats['ongoing_to_completed'];
                $stats['errors'] += $chunkStats['errors'];
                $stats['details'] = array_merge($stats['details'], $chunkStats['details']);

                $processedCount += $sessions->count();
            });

        return ['processed' => $processedCount, 'transitions' => $stats];
    }

    /**
     * Display combined results for all session types
     */
    private function displayCombinedResults(array $totalStats, bool $isDryRun, bool $isVerbose): void
    {
        $mode = $isDryRun ? 'Simulation' : 'Execution';
        $this->info("\n📊 {$mode} Results:");
        $this->info('═══════════════════════════════════════════════════');

        $quranStats = $totalStats['quran']['transitions'] ?? [];
        $academicStats = $totalStats['academic']['transitions'] ?? [];
        $interactiveStats = $totalStats['interactive']['transitions'] ?? [];

        $quranTransitions = ($quranStats['scheduled_to_ready'] ?? 0) +
                           ($quranStats['ready_to_absent'] ?? 0) +
                           ($quranStats['ongoing_to_completed'] ?? 0);

        $academicTransitions = ($academicStats['scheduled_to_ready'] ?? 0) +
                              ($academicStats['ready_to_absent'] ?? 0) +
                              ($academicStats['ongoing_to_completed'] ?? 0);

        $interactiveTransitions = ($interactiveStats['scheduled_to_ready'] ?? 0) +
                                 ($interactiveStats['ready_to_absent'] ?? 0) +
                                 ($interactiveStats['ongoing_to_completed'] ?? 0);

        $totalTransitions = $quranTransitions + $academicTransitions + $interactiveTransitions;
        $totalErrors = ($quranStats['errors'] ?? 0) + ($academicStats['errors'] ?? 0) + ($interactiveStats['errors'] ?? 0);

        if ($totalTransitions === 0 && $totalErrors === 0) {
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

        // Interactive course session results
        if ($totalStats['interactive']['processed'] > 0) {
            $this->info("\n🎬 Interactive Course Sessions ({$totalStats['interactive']['processed']} processed):");
            $this->displaySessionTypeResults($interactiveStats, $isDryRun, $isVerbose, 'Interactive');
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
