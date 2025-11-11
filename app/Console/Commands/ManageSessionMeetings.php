<?php

namespace App\Console\Commands;

use App\Services\CronJobLogger;
use App\Services\SessionMeetingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ManageSessionMeetings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sessions:manage-meetings 
                           {--dry-run : Show what would be done without making changes}
                           {--force : Force processing even during off hours}';

    /**
     * The console command description.
     */
    protected $description = 'Manage LiveKit meetings for scheduled sessions - auto-create, update status, and cleanup';

    private SessionMeetingService $sessionMeetingService;

    public function __construct(SessionMeetingService $sessionMeetingService)
    {
        parent::__construct();
        $this->sessionMeetingService = $sessionMeetingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        // Start enhanced logging
        $executionData = CronJobLogger::logCronStart('sessions:manage-meetings', [
            'dry_run' => $isDryRun,
            'forced' => $isForced,
        ]);

        $this->info('🚀 Starting session meeting management...');

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No actual changes will be made');
        }

        // Check if we should run during off-hours
        if (! $isForced && $this->isOffHours()) {
            $this->info('⏰ Off hours detected, running in maintenance mode only');

            return $this->runMaintenanceMode($isDryRun);
        }

        // Run full processing
        return $this->runFullProcessing($isDryRun);
    }

    /**
     * Run full processing during business hours
     */
    private function runFullProcessing(bool $isDryRun): int
    {
        try {
            $this->info('📊 Processing scheduled sessions...');

            if (! $isDryRun) {
                $results = $this->sessionMeetingService->processSessionMeetings();
            } else {
                $results = $this->simulateProcessing();
            }

            $this->displayResults($results);

            // Send summary to logs
            Log::info('Session meeting management completed', $results);

            $this->info('✅ Session meeting management completed successfully');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error during session meeting management: '.$e->getMessage());
            Log::error('Session meeting management failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Run maintenance mode during off-hours
     */
    private function runMaintenanceMode(bool $isDryRun): int
    {
        try {
            $this->info('🔧 Running maintenance mode...');

            // Only cleanup and status updates during off-hours
            if (! $isDryRun) {
                $results = [
                    'started' => 0,
                    'updated' => 0,
                    'cleaned' => $this->cleanupExpiredSessions(),
                    'errors' => 0,
                ];
            } else {
                $results = [
                    'started' => 0,
                    'updated' => 0,
                    'cleaned' => 3, // Simulated
                    'errors' => 0,
                ];
            }

            $this->displayResults($results);

            $this->info('✅ Maintenance mode completed');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error during maintenance: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Check if current time is off-hours (midnight to 6 AM)
     * DISABLED FOR DEBUGGING - sessions can be scheduled at any time
     */
    private function isOffHours(): bool
    {
        // TEMPORARY: Disable off-hours check for debugging
        // Sessions can be scheduled at any time, including early morning
        return false;

        // Original logic (commented out for debugging):
        // $hour = now()->hour;
        // return $hour >= 0 && $hour < 6;
    }

    /**
     * Simulate processing for dry-run mode
     */
    private function simulateProcessing(): array
    {
        $this->info('🔍 Simulating session processing...');

        // This would normally call the actual service
        return [
            'meetings_created' => 5,  // Simulated numbers
            'meetings_terminated' => 2,
            'status_transitions' => 12,
            'errors' => [],
        ];
    }

    /**
     * Clean up expired sessions (can run during off-hours)
     */
    private function cleanupExpiredSessions(): int
    {
        // Implementation would be similar to the service method
        // but focused only on cleanup
        return 0; // Placeholder
    }

    /**
     * Display processing results
     */
    private function displayResults(array $results): void
    {
        $this->info('📈 Processing Results:');

        // Handle different result formats (full vs maintenance mode)
        $meetingsCreated = $results['meetings_created'] ?? ($results['started'] ?? 0);
        $statusTransitions = $results['status_transitions'] ?? ($results['updated'] ?? 0);
        $meetingsTerminated = $results['meetings_terminated'] ?? ($results['cleaned'] ?? 0);
        $errors = $results['errors'] ?? 0;
        $errorCount = is_array($errors) ? count($errors) : $errors;

        $this->table(
            ['Action', 'Count', 'Status'],
            [
                ['Meetings Created', $meetingsCreated, $meetingsCreated > 0 ? '✅' : '⚪'],
                ['Status Transitions', $statusTransitions, $statusTransitions > 0 ? '✅' : '⚪'],
                ['Meetings Terminated', $meetingsTerminated, $meetingsTerminated > 0 ? '🧹' : '⚪'],
                ['Errors', $errorCount, $errorCount > 0 ? '❌' : '✅'],
            ]
        );

        // Show summary
        $total = $meetingsCreated + $statusTransitions + $meetingsTerminated;

        if ($total > 0) {
            $this->info("📊 Total actions performed: {$total}");
        } else {
            $this->comment('ℹ️  No actions needed at this time');
        }

        if ($errorCount > 0) {
            $this->warn("⚠️  {$errorCount} error(s) occurred. Check logs for details.");
        }
    }
}
