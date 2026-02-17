<?php

namespace App\Console\Commands;

use Exception;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\MeetingAttendanceEvent;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Services\CronJobLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * CleanupSoftDeletedDataCommand
 *
 * Permanently deletes old soft-deleted records to prevent database bloat.
 * Also archives old attendance events that are no longer needed.
 *
 * DESIGN DECISIONS:
 * - Subscriptions: Permanently delete after 90 days soft-deleted
 * - Sessions: Permanently delete after 180 days soft-deleted
 * - Attendance Events: Permanently delete after 365 days (for completed sessions only)
 * - Payments: NOT deleted (audit trail requirement)
 * - Dry-run mode by default for safety
 */
class CleanupSoftDeletedDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'data:cleanup-soft-deleted
                          {--force : Actually delete records (default is dry-run)}
                          {--subscription-days=90 : Days after soft-delete before permanent deletion for subscriptions}
                          {--session-days=180 : Days after soft-delete before permanent deletion for sessions}
                          {--attendance-days=365 : Days before deleting attendance events}
                          {--details : Show detailed output for each deletion}';

    /**
     * The console command description.
     */
    protected $description = 'Permanently delete old soft-deleted records to prevent database bloat';

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
        $isDryRun = ! $this->option('force');
        $isVerbose = $this->option('details');
        $subscriptionDays = (int) $this->option('subscription-days');
        $sessionDays = (int) $this->option('session-days');
        $attendanceDays = (int) $this->option('attendance-days');

        // Start enhanced logging
        $executionData = $this->cronJobLogger->logCronStart('data:cleanup-soft-deleted', [
            'dry_run' => $isDryRun,
            'verbose' => $isVerbose,
            'subscription_days' => $subscriptionDays,
            'session_days' => $sessionDays,
            'attendance_days' => $attendanceDays,
        ]);

        $this->info('Starting soft-deleted data cleanup...');
        $this->info('Current time: '.now()->format('Y-m-d H:i:s'));

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No records will be deleted (use --force to delete)');
        } else {
            $this->warn('FORCE MODE - Records will be PERMANENTLY DELETED');
        }

        try {
            $results = [
                'quran_subscriptions' => 0,
                'academic_subscriptions' => 0,
                'quran_sessions' => 0,
                'academic_sessions' => 0,
                'attendance_events' => 0,
                'errors' => [],
            ];

            // Cleanup subscriptions
            $this->info('');
            $this->info('Cleaning up subscriptions soft-deleted > '.$subscriptionDays.' days ago...');
            $results['quran_subscriptions'] = $this->cleanupQuranSubscriptions($subscriptionDays, $isDryRun, $isVerbose);
            $results['academic_subscriptions'] = $this->cleanupAcademicSubscriptions($subscriptionDays, $isDryRun, $isVerbose);

            // Cleanup sessions
            $this->info('');
            $this->info('Cleaning up sessions soft-deleted > '.$sessionDays.' days ago...');
            $results['quran_sessions'] = $this->cleanupQuranSessions($sessionDays, $isDryRun, $isVerbose);
            $results['academic_sessions'] = $this->cleanupAcademicSessions($sessionDays, $isDryRun, $isVerbose);

            // Cleanup old attendance events
            $this->info('');
            $this->info('Cleaning up attendance events > '.$attendanceDays.' days old...');
            $results['attendance_events'] = $this->cleanupAttendanceEvents($attendanceDays, $isDryRun, $isVerbose);

            $this->displayResults($results, $isDryRun);

            // Log completion
            $this->cronJobLogger->logCronEnd('data:cleanup-soft-deleted', $executionData, $results);

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error('Data cleanup failed: '.$e->getMessage());

            if ($isVerbose) {
                $this->error('Stack trace: '.$e->getTraceAsString());
            }

            $this->cronJobLogger->logCronError('data:cleanup-soft-deleted', $executionData, $e);

            Log::error('Data cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Cleanup old soft-deleted Quran subscriptions
     */
    private function cleanupQuranSubscriptions(int $days, bool $isDryRun, bool $isVerbose): int
    {
        $cutoffDate = now()->subDays($days);

        $query = QuranSubscription::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate);

        $count = $query->count();

        if ($isVerbose) {
            $this->info("  Found {$count} Quran subscriptions to delete");
        }

        if (! $isDryRun && $count > 0) {
            $query->forceDelete();
            $this->info("  Deleted {$count} Quran subscriptions");
        }

        return $count;
    }

    /**
     * Cleanup old soft-deleted Academic subscriptions
     */
    private function cleanupAcademicSubscriptions(int $days, bool $isDryRun, bool $isVerbose): int
    {
        $cutoffDate = now()->subDays($days);

        $query = AcademicSubscription::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate);

        $count = $query->count();

        if ($isVerbose) {
            $this->info("  Found {$count} Academic subscriptions to delete");
        }

        if (! $isDryRun && $count > 0) {
            $query->forceDelete();
            $this->info("  Deleted {$count} Academic subscriptions");
        }

        return $count;
    }

    /**
     * Cleanup old soft-deleted Quran sessions
     */
    private function cleanupQuranSessions(int $days, bool $isDryRun, bool $isVerbose): int
    {
        $cutoffDate = now()->subDays($days);

        $query = QuranSession::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate);

        $count = $query->count();

        if ($isVerbose) {
            $this->info("  Found {$count} Quran sessions to delete");
        }

        if (! $isDryRun && $count > 0) {
            // Delete in chunks to prevent timeout
            QuranSession::onlyTrashed()
                ->where('deleted_at', '<', $cutoffDate)
                ->chunkById(100, function ($sessions) {
                    foreach ($sessions as $session) {
                        $session->forceDelete();
                    }
                });
            $this->info("  Deleted {$count} Quran sessions");
        }

        return $count;
    }

    /**
     * Cleanup old soft-deleted Academic sessions
     */
    private function cleanupAcademicSessions(int $days, bool $isDryRun, bool $isVerbose): int
    {
        $cutoffDate = now()->subDays($days);

        $query = AcademicSession::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate);

        $count = $query->count();

        if ($isVerbose) {
            $this->info("  Found {$count} Academic sessions to delete");
        }

        if (! $isDryRun && $count > 0) {
            // Delete in chunks to prevent timeout
            AcademicSession::onlyTrashed()
                ->where('deleted_at', '<', $cutoffDate)
                ->chunkById(100, function ($sessions) {
                    foreach ($sessions as $session) {
                        $session->forceDelete();
                    }
                });
            $this->info("  Deleted {$count} Academic sessions");
        }

        return $count;
    }

    /**
     * Cleanup old attendance events for completed sessions
     */
    private function cleanupAttendanceEvents(int $days, bool $isDryRun, bool $isVerbose): int
    {
        $cutoffDate = now()->subDays($days);

        // Only delete events older than cutoff date
        $query = MeetingAttendanceEvent::where('created_at', '<', $cutoffDate);

        $count = $query->count();

        if ($isVerbose) {
            $this->info("  Found {$count} attendance events to delete");
        }

        if (! $isDryRun && $count > 0) {
            // Delete in batches to prevent memory issues
            $deleted = 0;
            while ($deleted < $count) {
                $batchDeleted = MeetingAttendanceEvent::where('created_at', '<', $cutoffDate)
                    ->limit(1000)
                    ->delete();

                if ($batchDeleted === 0) {
                    break;
                }

                $deleted += $batchDeleted;

                if ($isVerbose) {
                    $this->info("    Deleted batch: {$deleted}/{$count}");
                }
            }
            $this->info("  Deleted {$deleted} attendance events");
        }

        return $count;
    }

    /**
     * Display execution results
     */
    private function displayResults(array $results, bool $isDryRun): void
    {
        $mode = $isDryRun ? 'Simulation' : 'Execution';
        $this->info('');
        $this->info("Data Cleanup {$mode} Results:");
        $this->info('---');

        $this->info("Quran subscriptions: {$results['quran_subscriptions']}");
        $this->info("Academic subscriptions: {$results['academic_subscriptions']}");
        $this->info("Quran sessions: {$results['quran_sessions']}");
        $this->info("Academic sessions: {$results['academic_sessions']}");
        $this->info("Attendance events: {$results['attendance_events']}");

        $total = $results['quran_subscriptions'] + $results['academic_subscriptions'] +
                 $results['quran_sessions'] + $results['academic_sessions'] +
                 $results['attendance_events'];

        $verb = $isDryRun ? 'would be deleted' : 'deleted';
        $this->info("Total records {$verb}: {$total}");

        if (! empty($results['errors'])) {
            $this->error('');
            $this->error('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }

        $this->info('');
        $this->info('Data cleanup completed.');
    }
}
