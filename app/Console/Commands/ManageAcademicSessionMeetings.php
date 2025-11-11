<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Services\AcademicSessionMeetingService;
use App\Services\CronJobLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ManageAcademicSessionMeetings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'academic-sessions:manage-meetings 
                           {--dry-run : Show what would be done without making changes}
                           {--force : Force processing even during off hours}';

    /**
     * The console command description.
     */
    protected $description = 'Manage LiveKit meetings for scheduled academic sessions - auto-create, update status, and cleanup';

    private AcademicSessionMeetingService $academicSessionMeetingService;

    public function __construct(AcademicSessionMeetingService $academicSessionMeetingService)
    {
        parent::__construct();
        $this->academicSessionMeetingService = $academicSessionMeetingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        // Start enhanced logging
        $executionData = CronJobLogger::logCronStart('academic-sessions:manage-meetings', [
            'dry_run' => $isDryRun,
            'forced' => $isForced,
        ]);

        $this->info('🚀 Starting academic session meeting management...');

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
            $this->info('📊 Processing scheduled academic sessions...');

            if (!$isDryRun) {
                $results = $this->academicSessionMeetingService->processSessionMeetings();
            } else {
                $results = $this->simulateProcessing();
            }

            $this->displayResults($results);

            // Send summary to logs
            Log::info('Academic session meeting management completed', $results);

            $this->info('✅ Academic session meeting management completed successfully');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error during academic session meeting management: '.$e->getMessage());
            Log::error('Academic session meeting management failed', [
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
            $this->info('🔧 Running academic session maintenance mode...');

            // Only cleanup operations during off-hours
            if (!$isDryRun) {
                $terminateResults = $this->academicSessionMeetingService->terminateExpiredMeetings();
                $results = [
                    'meetings_created' => 0,
                    'meetings_terminated' => $terminateResults['meetings_terminated'],
                    'status_transitions' => 0,
                    'errors' => count($terminateResults['errors']),
                ];
            } else {
                $results = [
                    'meetings_created' => 0,
                    'meetings_terminated' => 2, // Simulated
                    'status_transitions' => 0,
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
     * Simulate processing for dry-run mode
     */
    private function simulateProcessing(): array
    {
        $this->info('🔍 Simulating academic session processing...');

        // This would normally call the actual service
        return [
            'meetings_created' => 3,  // Simulated numbers
            'meetings_terminated' => 1,
            'status_transitions' => 8,
            'errors' => [],
        ];
    }

    /**
     * Check if current time is off-hours (midnight to 6 AM)
     * DISABLED FOR DEBUGGING - academic sessions can be scheduled at any time
     */
    private function isOffHours(): bool
    {
        // TEMPORARY: Disable off-hours check for debugging
        // Academic sessions can be scheduled at any time, including early morning
        return false;

        // Original logic (commented out for debugging):
        // $hour = now()->hour;
        // return $hour >= 0 && $hour < 6;
    }

    /**
     * Display processing results
     */
    private function displayResults(array $results): void
    {
        $this->info('📈 Academic Session Processing Results:');

        // Handle different result formats (full vs maintenance mode)
        $meetingsCreated = $results['meetings_created'] ?? 0;
        $statusTransitions = $results['status_transitions'] ?? 0;
        $meetingsTerminated = $results['meetings_terminated'] ?? 0;
        $errors = $results['errors'] ?? 0;
        $errorCount = is_array($errors) ? count($errors) : $errors;

        $this->table(
            ['Action', 'Count', 'Status'],
            [
                ['Academic Meetings Created', $meetingsCreated, $meetingsCreated > 0 ? '✅' : '⚪'],
                ['Status Transitions', $statusTransitions, $statusTransitions > 0 ? '✅' : '⚪'],
                ['Academic Meetings Terminated', $meetingsTerminated, $meetingsTerminated > 0 ? '🧹' : '⚪'],
                ['Errors', $errorCount, $errorCount > 0 ? '❌' : '✅'],
            ]
        );

        // Show summary
        $total = $meetingsCreated + $statusTransitions + $meetingsTerminated;

        if ($total > 0) {
            $this->info("📊 Total academic session actions performed: {$total}");
        } else {
            $this->comment('ℹ️  No academic session actions needed at this time');
        }

        if ($errorCount > 0) {
            $this->warn("⚠️  {$errorCount} error(s) occurred. Check logs for details.");
        }
    }
}