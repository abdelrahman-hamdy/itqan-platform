<?php

namespace App\Console\Commands;

use App\Models\QuranSession;
use App\Services\UnifiedAttendanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigrateLegacyAttendanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attendance:migrate-legacy 
                          {--academy-id= : Process sessions for specific academy only}
                          {--session-id= : Process specific session only}
                          {--dry-run : Preview migration without making changes}
                          {--force : Force migration even if session reports exist}';

    /**
     * The console command description.
     */
    protected $description = 'Migrate legacy attendance data (QuranSessionAttendance) to unified StudentSessionReport system';

    private UnifiedAttendanceService $unifiedAttendanceService;

    public function __construct(UnifiedAttendanceService $unifiedAttendanceService)
    {
        parent::__construct();
        $this->unifiedAttendanceService = $unifiedAttendanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');
        $academyId = $this->option('academy-id');
        $sessionId = $this->option('session-id');

        $this->info('🚀 Starting legacy attendance data migration...');

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No actual changes will be made');
        }

        if ($isForced) {
            $this->warn('⚠️  FORCE MODE - Will update existing session reports');
        }

        try {
            // Build query for sessions to process
            $query = QuranSession::with(['attendances', 'quranTeacher', 'academy']);

            if ($sessionId) {
                $query->where('id', $sessionId);
            } elseif ($academyId) {
                $query->where('academy_id', $academyId);
            } else {
                // Only process sessions with legacy attendance data
                $query->whereHas('attendances');
            }

            // Get sessions that have legacy attendance data
            $sessions = $query->get();

            $this->info("📊 Found {$sessions->count()} sessions with legacy attendance data");

            if ($sessions->isEmpty()) {
                $this->info('✅ No sessions need migration');

                return self::SUCCESS;
            }

            if ($isDryRun) {
                return $this->simulateMigration($sessions);
            } else {
                return $this->performMigration($sessions, $isForced);
            }

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: '.$e->getMessage());
            Log::error('Legacy attendance migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Simulate migration for dry run mode
     */
    private function simulateMigration($sessions): int
    {
        $this->info('🔍 Simulating migration...');

        $totalReportsToCreate = 0;
        $totalReportsToUpdate = 0;

        foreach ($sessions as $session) {
            $attendances = $session->attendances ?? collect();

            foreach ($attendances as $attendance) {
                $existingReport = $session->studentSessionReports()
                    ->where('student_id', $attendance->student_id)
                    ->exists();

                if ($existingReport) {
                    $totalReportsToUpdate++;
                } else {
                    $totalReportsToCreate++;
                }
            }
        }

        $this->table(
            ['Action', 'Count'],
            [
                ['Sessions to Process', $sessions->count()],
                ['Reports to Create', $totalReportsToCreate],
                ['Reports to Update', $totalReportsToUpdate],
                ['Total Operations', $totalReportsToCreate + $totalReportsToUpdate],
            ]
        );

        $this->info('✅ Simulation completed');

        return self::SUCCESS;
    }

    /**
     * Perform actual migration
     */
    private function performMigration($sessions, bool $isForced): int
    {
        $this->info('📋 Starting migration...');

        // Perform migration in batches
        $batches = $sessions->chunk(50);
        $totalResults = [
            'sessions_processed' => 0,
            'reports_created' => 0,
            'reports_updated' => 0,
            'errors' => [],
        ];

        foreach ($batches as $batchIndex => $sessionBatch) {
            $this->info('Processing batch '.($batchIndex + 1).' of '.$batches->count());

            $batchResults = $this->unifiedAttendanceService->migrateLegacyAttendanceData($sessionBatch);

            // Merge results
            $totalResults['sessions_processed'] += $batchResults['sessions_processed'];
            $totalResults['reports_created'] += $batchResults['reports_created'];
            $totalResults['reports_updated'] += $batchResults['reports_updated'];
            $totalResults['errors'] = array_merge($totalResults['errors'], $batchResults['errors']);

            // Show progress
            $progressBar = $this->output->createProgressBar($sessionBatch->count());
            $progressBar->advance($sessionBatch->count());
            $progressBar->finish();
            $this->newLine();
        }

        // Display final results
        $this->displayResults($totalResults);

        // Clean up legacy data if migration was successful
        if (empty($totalResults['errors']) && ! $this->option('dry-run')) {
            if ($this->confirm('Migration completed successfully. Delete legacy attendance records?', false)) {
                $this->cleanupLegacyData($sessions);
            }
        }

        $this->info('✅ Migration completed successfully');

        return self::SUCCESS;
    }

    /**
     * Display migration results
     */
    private function displayResults(array $results): void
    {
        $this->info('📈 Migration Results:');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Sessions Processed', $results['sessions_processed']],
                ['Reports Created', $results['reports_created']],
                ['Reports Updated', $results['reports_updated']],
                ['Errors', count($results['errors'])],
            ]
        );

        if (! empty($results['errors'])) {
            $this->error('❌ Errors occurred during migration:');
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $sessionInfo = isset($error['session_id']) ? "Session {$error['session_id']}" : 'General';
                $studentInfo = isset($error['student_id']) ? " Student {$error['student_id']}" : '';
                $this->error("  • {$sessionInfo}{$studentInfo}: {$error['error']}");
            }

            if (count($results['errors']) > 10) {
                $remaining = count($results['errors']) - 10;
                $this->error("  • ... and {$remaining} more errors");
            }
        }
    }

    /**
     * Clean up legacy attendance data
     */
    private function cleanupLegacyData($sessions): void
    {
        $this->info('🧹 Cleaning up legacy attendance data...');

        $deletedCount = 0;

        foreach ($sessions as $session) {
            try {
                $attendances = $session->attendances ?? collect();
                foreach ($attendances as $attendance) {
                    $attendance->delete();
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to delete legacy data for session {$session->id}: {$e->getMessage()}");
            }
        }

        $this->info("✅ Deleted {$deletedCount} legacy attendance records");
    }

    /**
     * Show migration overview
     */
    private function showMigrationOverview(): void
    {
        $this->info('📋 Legacy Attendance Migration Overview:');
        $this->info('═══════════════════════════════════════════════════');
        $this->info('');
        $this->info('This command will:');
        $this->info('• Migrate QuranSessionAttendance → StudentSessionReport');
        $this->info('• Preserve all existing attendance data');
        $this->info('• Maintain teacher notes and session details');
        $this->info('• Create unified reports for comprehensive tracking');
        $this->info('');
        $this->info('Benefits:');
        $this->info('• Single source of truth for attendance + session reviews');
        $this->info('• Real-time attendance tracking integration');
        $this->info('• Better reporting and analytics');
        $this->info('• Simplified teacher interface');
        $this->info('');
    }
}
