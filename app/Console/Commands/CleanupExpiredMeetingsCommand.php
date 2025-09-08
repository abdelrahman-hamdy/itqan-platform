<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Services\AutoMeetingCreationService;
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

    public function __construct(AutoMeetingCreationService $autoMeetingService)
    {
        parent::__construct();
        $this->autoMeetingService = $autoMeetingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $this->info('🧹 Starting expired meetings cleanup process...');
        $this->info('📅 Current time: '.now()->format('Y-m-d H:i:s'));

        try {
            $isDryRun = $this->option('dry-run');
            $isVerbose = $this->getOutput()->isVerbose();

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

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->info("⚡ Cleanup completed in {$executionTime} seconds");

            // Determine exit code based on results
            if (isset($results['meetings_failed_to_end']) && $results['meetings_failed_to_end'] > 0) {
                $this->warn('⚠️  Some meetings failed to end. Check logs for details.');

                return self::INVALID;
            }

            $this->info('✅ Meeting cleanup process completed successfully');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('💥 Fatal error during cleanup: '.$e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }

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
        // Count sessions that would be cleaned up
        $expiredSessions = \App\Models\QuranSession::whereNotNull('meeting_id')
            ->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::ONGOING])
            ->whereNotNull('scheduled_at')
            ->with('academy')
            ->get()
            ->filter(function ($session) {
                $videoSettings = \App\Models\VideoSettings::forAcademy($session->academy);

                if (! $videoSettings->auto_end_meetings) {
                    return false;
                }

                $scheduledEndTime = \Carbon\Carbon::parse($session->scheduled_at)
                    ->addMinutes($session->duration_minutes ?? 60);
                $actualEndTime = $videoSettings->getMeetingEndTime($scheduledEndTime);

                return now()->gte($actualEndTime);
            });

        $this->line("  📋 Would end {$expiredSessions->count()} expired meetings");

        if ($this->getOutput()->isVerbose() && $expiredSessions->count() > 0) {
            $this->line('  📝 Sessions that would be ended:');
            foreach ($expiredSessions as $session) {
                $scheduledEnd = \Carbon\Carbon::parse($session->scheduled_at)
                    ->addMinutes($session->duration_minutes ?? 60);
                $this->line("    - Session {$session->id}: Scheduled to end at {$scheduledEnd->format('Y-m-d H:i:s')}");
            }
        }

        return [
            'sessions_checked' => $expiredSessions->count(),
            'meetings_ended' => $expiredSessions->count(), // In dry run, assume all would succeed
            'meetings_failed_to_end' => 0,
            'errors' => [],
        ];
    }
}
