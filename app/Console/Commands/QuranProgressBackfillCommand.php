<?php

namespace App\Console\Commands;

use App\Models\QuranIndividualCircle;
use App\Models\StudentSessionReport;
use App\Services\QuranProgressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill Quran Progress Command
 *
 * Populates QuranProgress table with data from existing StudentSessionReport records.
 * This is a one-time migration command to retroactively create progress tracking
 * for sessions that were completed before the QuranProgress system was implemented.
 */
class QuranProgressBackfillCommand extends Command
{
    protected $signature = 'quran:backfill-progress
                            {--dry-run : Run without making changes}
                            {--limit= : Limit number of records to process}';

    protected $description = 'Backfill QuranProgress records from existing StudentSessionReport data';

    protected QuranProgressService $progressService;

    public function __construct(QuranProgressService $progressService)
    {
        parent::__construct();
        $this->progressService = $progressService;
    }

    public function handle(): int
    {
        $this->info('Starting QuranProgress backfill process...');
        $isDryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get all StudentSessionReport records where student attended
        $query = StudentSessionReport::query()
            ->where('attendance_status', 'present')
            ->whereNotNull('new_memorization_degree')
            ->orWhereNotNull('reservation_degree')
            ->with(['session', 'student']);

        if ($limit) {
            $query->limit($limit);
        }

        $reports = $query->get();
        $totalReports = $reports->count();

        if ($totalReports === 0) {
            $this->info('No reports found to process.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalReports} reports to process");

        // Progress bar
        $bar = $this->output->createProgressBar($totalReports);
        $bar->start();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($reports as $report) {
            try {
                // Check if QuranProgress already exists
                $exists = DB::table('quran_progress')
                    ->where('session_id', $report->session_id)
                    ->where('student_id', $report->student_id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if (!$isDryRun) {
                    // Create QuranProgress record
                    $progress = $this->progressService->createOrUpdateSessionProgress($report);

                    if ($progress && $progress->wasRecentlyCreated) {
                        $created++;
                    } elseif ($progress) {
                        $updated++;
                    }
                }

                $bar->advance();
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error processing report {$report->id}: {$e->getMessage()}");
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Update circle statistics
        if (!$isDryRun) {
            $this->info('Updating circle statistics...');
            $this->updateCircleStatistics();
        }

        // Summary
        $this->info('Backfill completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Errors', $errors],
                ['Total', $totalReports],
            ]
        );

        if ($isDryRun) {
            $this->warn('This was a DRY RUN - no changes were made');
            $this->info('Run without --dry-run to apply changes');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Update circle-level statistics after backfill
     */
    protected function updateCircleStatistics(): void
    {
        $circles = QuranIndividualCircle::all();
        $circleBar = $this->output->createProgressBar($circles->count());

        $this->info("Updating {$circles->count()} individual circles...");
        $circleBar->start();

        foreach ($circles as $circle) {
            try {
                $this->progressService->updateCircleProgress($circle);
                $circleBar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error updating circle {$circle->id}: {$e->getMessage()}");
                $circleBar->advance();
            }
        }

        $circleBar->finish();
        $this->newLine();
    }
}
