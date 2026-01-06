<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Jobs\CalculateSessionEarningsJob;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Models\TeacherEarning;
use App\Services\CronJobLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * CalculateMissedEarningsCommand
 *
 * Backup command to calculate earnings for COMPLETED sessions that
 * were missed by the observer-based dispatch (e.g., due to queue failures).
 *
 * DESIGN DECISIONS:
 * - Only processes sessions completed in the last 30 days
 * - Skips sessions that already have TeacherEarning records
 * - Processes in chunks to prevent memory issues
 * - Supports dry-run mode for testing
 */
class CalculateMissedEarningsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'earnings:calculate-missed
                          {--days=30 : Number of days to look back for completed sessions}
                          {--dry-run : Show what would be done without actually processing}
                          {--details : Show detailed output for each session}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate earnings for completed sessions that were missed by the observer';

    public function __construct(
        private CronJobLogger $cronJobLogger
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isVerbose = $this->option('details') || $isDryRun;
        $days = (int) $this->option('days');

        // Start enhanced logging
        $executionData = $this->cronJobLogger->logCronStart('earnings:calculate-missed', [
            'dry_run' => $isDryRun,
            'verbose' => $isVerbose,
            'days' => $days,
        ]);

        $this->info('Starting missed earnings calculation...');
        $this->info('Looking back '.$days.' days');
        $this->info('Current time: '.now()->format('Y-m-d H:i:s'));

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No jobs will be dispatched');
        }

        try {
            $results = [
                'quran_checked' => 0,
                'quran_dispatched' => 0,
                'academic_checked' => 0,
                'academic_dispatched' => 0,
                'errors' => [],
            ];

            // Process Quran sessions
            $this->processQuranSessions($days, $isDryRun, $isVerbose, $results);

            // Process Academic sessions
            $this->processAcademicSessions($days, $isDryRun, $isVerbose, $results);

            $this->displayResults($results, $isDryRun);

            // Log completion
            $this->cronJobLogger->logCronEnd('earnings:calculate-missed', $executionData, $results);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Missed earnings calculation failed: '.$e->getMessage());

            if ($isVerbose) {
                $this->error('Stack trace: '.$e->getTraceAsString());
            }

            $this->cronJobLogger->logCronError('earnings:calculate-missed', $executionData, $e);

            Log::error('Missed earnings calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Process Quran sessions for missed earnings
     */
    private function processQuranSessions(int $days, bool $isDryRun, bool $isVerbose, array &$results): void
    {
        $cutoffDate = now()->subDays($days);

        QuranSession::where('status', SessionStatus::COMPLETED)
            ->where('ended_at', '>=', $cutoffDate)
            ->whereNotNull('ended_at')
            ->chunkById(100, function ($sessions) use ($isDryRun, $isVerbose, &$results) {
                foreach ($sessions as $session) {
                    $results['quran_checked']++;

                    // Check if earnings already exist
                    $hasEarning = TeacherEarning::forSession(
                        QuranSession::class,
                        $session->id
                    )->exists();

                    if ($hasEarning) {
                        if ($isVerbose) {
                            $this->line("  Quran session {$session->id}: Already has earnings");
                        }

                        continue;
                    }

                    if ($isVerbose) {
                        $this->info("  Quran session {$session->id}: Would dispatch earnings calculation");
                    }

                    if (! $isDryRun) {
                        try {
                            dispatch(new CalculateSessionEarningsJob($session));
                            $results['quran_dispatched']++;
                        } catch (\Exception $e) {
                            $results['errors'][] = [
                                'session_type' => 'quran',
                                'session_id' => $session->id,
                                'error' => $e->getMessage(),
                            ];
                        }
                    } else {
                        $results['quran_dispatched']++;
                    }
                }
            });
    }

    /**
     * Process Academic sessions for missed earnings
     */
    private function processAcademicSessions(int $days, bool $isDryRun, bool $isVerbose, array &$results): void
    {
        $cutoffDate = now()->subDays($days);

        AcademicSession::where('status', SessionStatus::COMPLETED)
            ->where('ended_at', '>=', $cutoffDate)
            ->whereNotNull('ended_at')
            ->chunkById(100, function ($sessions) use ($isDryRun, $isVerbose, &$results) {
                foreach ($sessions as $session) {
                    $results['academic_checked']++;

                    // Check if earnings already exist
                    $hasEarning = TeacherEarning::forSession(
                        AcademicSession::class,
                        $session->id
                    )->exists();

                    if ($hasEarning) {
                        if ($isVerbose) {
                            $this->line("  Academic session {$session->id}: Already has earnings");
                        }

                        continue;
                    }

                    if ($isVerbose) {
                        $this->info("  Academic session {$session->id}: Would dispatch earnings calculation");
                    }

                    if (! $isDryRun) {
                        try {
                            dispatch(new CalculateSessionEarningsJob($session));
                            $results['academic_dispatched']++;
                        } catch (\Exception $e) {
                            $results['errors'][] = [
                                'session_type' => 'academic',
                                'session_id' => $session->id,
                                'error' => $e->getMessage(),
                            ];
                        }
                    } else {
                        $results['academic_dispatched']++;
                    }
                }
            });
    }

    /**
     * Display execution results
     */
    private function displayResults(array $results, bool $isDryRun): void
    {
        $mode = $isDryRun ? 'Simulation' : 'Execution';
        $this->info('');
        $this->info("Missed Earnings Calculation {$mode} Results:");
        $this->info('---');

        $this->info("Quran sessions checked: {$results['quran_checked']}");
        $this->info("Quran sessions dispatched: {$results['quran_dispatched']}");
        $this->info("Academic sessions checked: {$results['academic_checked']}");
        $this->info("Academic sessions dispatched: {$results['academic_dispatched']}");

        $totalDispatched = $results['quran_dispatched'] + $results['academic_dispatched'];
        $this->info("Total earnings jobs dispatched: {$totalDispatched}");

        if (! empty($results['errors'])) {
            $this->error('');
            $this->error('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->error("  - {$error['session_type']} session {$error['session_id']}: {$error['error']}");
            }
        }

        $this->info('');
        $this->info('Missed earnings calculation completed.');
    }
}
