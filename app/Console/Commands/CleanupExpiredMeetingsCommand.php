<?php

namespace App\Console\Commands;

use App\Services\AutoMeetingCreationService;
use App\Services\CronJobLogger;
use Illuminate\Console\Command;

class CleanupExpiredMeetingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'meetings:cleanup-expired
                          {--dry-run : Show what would be cleaned up without actually ending meetings}';

    /**
     * The console command description.
     */
    protected $description = 'End expired video meetings and cleanup resources';

    private AutoMeetingCreationService $autoMeetingService;

    private CronJobLogger $cronJobLogger;

    public function __construct(AutoMeetingCreationService $autoMeetingService, CronJobLogger $cronJobLogger)
    {
        parent::__construct();
        $this->autoMeetingService = $autoMeetingService;
        $this->cronJobLogger = $cronJobLogger;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isVerbose = $this->getOutput()->isVerbose();

        // Start enhanced logging
        $executionData = $this->cronJobLogger->logCronStart('meetings:cleanup-expired', [
            'dry_run' => $isDryRun,
        ]);

        $this->info('🧹 Starting expired meetings cleanup process...');
        $this->info('📅 Current time: '.now()->format('Y-m-d H:i:s'));

        try {
            if ($isDryRun) {
                $this->warn('🧪 DRY RUN MODE: No meetings will actually be ended');
            }

            $results = [];

            if (! $isDryRun) {
                // Perform actual cleanup
                $results = $this->autoMeetingService->cleanupExpiredMeetings();
            } else {
                // Simulate cleanup
                $results = $this->simulateCleanup();
            }

            // Display results
            $this->displayResults($results, $isVerbose);

            // Show statistics if not dry run
            if (! $isDryRun) {
                $this->displayStatistics();
            }

            // Determine exit code based on results
            if (isset($results['meetings_failed_to_end']) && $results['meetings_failed_to_end'] > 0) {
                $this->warn('⚠️  Some meetings failed to end. Check logs for details.');

                $this->cronJobLogger->logCronEnd('meetings:cleanup-expired', $executionData, $results, 'partial');

                return self::INVALID;
            }

            $this->info('✅ Meeting cleanup process completed successfully');

            // Log completion
            $this->cronJobLogger->logCronEnd('meetings:cleanup-expired', $executionData, $results, 'success');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('💥 Fatal error during cleanup: '.$e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }

            // Log error
            $this->cronJobLogger->logCronError('meetings:cleanup-expired', $executionData, $e);

            return self::FAILURE;
        }
    }

    /**
     * Display cleanup results
     */
    private function displayResults(array $results, bool $verbose): void
    {
        $this->info('📊 Cleanup Results:');
        $this->line("  • Sessions checked: {$results['sessions_checked']}");
        $this->line("  • Meetings ended: {$results['meetings_ended']}");

        if ($results['meetings_failed_to_end'] > 0) {
            $this->error("  • Meetings failed to end: {$results['meetings_failed_to_end']}");
        }

        if ($verbose && ! empty($results['errors'])) {
            $this->warn('  • Errors encountered:');
            foreach ($results['errors'] as $error) {
                if (is_array($error) && isset($error['session_id'])) {
                    $this->line("    - Session {$error['session_id']}: {$error['error']}");
                } else {
                    $this->line("    - {$error}");
                }
            }
        }
    }

    /**
     * Display current system statistics
     */
    private function displayStatistics(): void
    {
        $stats = $this->autoMeetingService->getStatistics();

        $this->info('📈 System Statistics After Cleanup:');
        $this->line("  • Total auto-generated meetings: {$stats['total_auto_generated_meetings']}");
        $this->line("  • Active meetings: {$stats['active_meetings']}");
        $this->line("  • Meetings created today: {$stats['meetings_created_today']}");
        $this->line("  • Meetings created this week: {$stats['meetings_created_this_week']}");
    }

    /**
     * Simulate cleanup for dry run mode
     */
    private function simulateCleanup(): array
    {
        $expiredCount = 0;
        $sessionDetails = [];

        // Process sessions in chunks to prevent memory issues
        \App\Models\QuranSession::whereNotNull('meeting_id')
            ->active()
            ->whereNotNull('scheduled_at')
            ->with('academy')
            ->chunkById(100, function ($sessions) use (&$expiredCount, &$sessionDetails) {
                foreach ($sessions as $session) {
                    $videoSettings = \App\Models\VideoSettings::forAcademy($session->academy);

                    if (! $videoSettings->auto_end_meetings) {
                        continue;
                    }

                    $scheduledEndTime = \Carbon\Carbon::parse($session->scheduled_at)
                        ->addMinutes($session->duration_minutes ?? 60);
                    $actualEndTime = $videoSettings->getMeetingEndTime($scheduledEndTime);

                    if (now()->gte($actualEndTime)) {
                        $expiredCount++;

                        // Only store details if verbose mode (limit to prevent memory issues)
                        if ($this->getOutput()->isVerbose() && count($sessionDetails) < 100) {
                            $sessionDetails[] = [
                                'id' => $session->id,
                                'scheduled_end' => $scheduledEndTime->format('Y-m-d H:i:s'),
                            ];
                        }
                    }
                }
            });

        $this->line("  📋 Would end {$expiredCount} expired meetings");

        if ($this->getOutput()->isVerbose() && count($sessionDetails) > 0) {
            $this->line('  📝 Sessions that would be ended (first 100):');
            foreach ($sessionDetails as $detail) {
                $this->line("    - Session {$detail['id']}: Scheduled to end at {$detail['scheduled_end']}");
            }
        }

        return [
            'sessions_checked' => $expiredCount,
            'meetings_ended' => $expiredCount, // In dry run, assume all would succeed
            'meetings_failed_to_end' => 0,
            'errors' => [],
        ];
    }
}
