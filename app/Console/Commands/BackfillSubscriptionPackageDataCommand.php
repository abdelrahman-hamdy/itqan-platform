<?php

namespace App\Console\Commands;

use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BackfillSubscriptionPackageDataCommand
 *
 * Populates package snapshot fields on existing subscriptions.
 * Run this ONCE after the migration to populate historical data.
 *
 * This ensures subscriptions are self-contained and don't depend
 * on package tables for historical pricing accuracy.
 */
class BackfillSubscriptionPackageDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:backfill-package-data
                          {--dry-run : Show what would be done without making changes}
                          {--type= : Process only specific type (quran, academic, course)}
                          {--limit= : Limit number of records to process}';

    /**
     * The console command description.
     */
    protected $description = 'Backfill package snapshot data for existing subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $type = $this->option('type');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info('Starting package data backfill...');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $results = [
            'quran' => ['processed' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
            'academic' => ['processed' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
            'course' => ['processed' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
        ];

        try {
            if (!$type || $type === 'quran') {
                $results['quran'] = $this->backfillQuranSubscriptions($isDryRun, $limit);
            }

            if (!$type || $type === 'academic') {
                $results['academic'] = $this->backfillAcademicSubscriptions($isDryRun, $limit);
            }

            if (!$type || $type === 'course') {
                $results['course'] = $this->backfillCourseSubscriptions($isDryRun, $limit);
            }

            $this->displayResults($results, $isDryRun);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Backfill failed: ' . $e->getMessage());
            Log::error('Package data backfill failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Backfill Quran subscription package data
     */
    private function backfillQuranSubscriptions(bool $isDryRun, ?int $limit): array
    {
        $this->info('Processing Quran subscriptions...');

        $results = ['processed' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        $query = QuranSubscription::whereNull('package_name_ar')
            ->whereNotNull('package_id');

        if ($limit) {
            $query->limit($limit);
        }

        $total = $query->count();
        $this->info("Found {$total} Quran subscriptions to process");

        if ($total === 0) {
            return $results;
        }

        $progressBar = $this->output->createProgressBar($total);

        // Process in chunks to prevent memory issues
        $query->with('package')->chunkById(100, function ($subscriptions) use (&$results, $isDryRun, $progressBar) {
            foreach ($subscriptions as $subscription) {
                $results['processed']++;

                try {
                    $package = $subscription->package;

                    if (!$package) {
                        $results['skipped']++;
                        $progressBar->advance();
                        continue;
                    }

                    $updateData = [
                        'package_name_ar' => $package->name_ar ?? $package->name ?? null,
                        'package_name_en' => $package->name_en ?? $package->name ?? null,
                        'package_price_monthly' => $package->price_monthly ?? $package->monthly_price ?? null,
                        'package_price_quarterly' => $package->price_quarterly ?? $package->quarterly_price ?? null,
                        'package_price_yearly' => $package->price_yearly ?? $package->yearly_price ?? null,
                        'package_sessions_per_week' => $package->sessions_per_week ?? null,
                        'package_session_duration_minutes' => $package->session_duration_minutes ?? $package->session_duration ?? null,
                    ];

                    if (!$isDryRun) {
                        $subscription->update($updateData);
                    }

                    $results['updated']++;

                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::warning("Failed to backfill Quran subscription {$subscription->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();

        return $results;
    }

    /**
     * Backfill Academic subscription package data
     */
    private function backfillAcademicSubscriptions(bool $isDryRun, ?int $limit): array
    {
        $this->info('Processing Academic subscriptions...');

        $results = ['processed' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        $query = AcademicSubscription::whereNull('package_name_ar')
            ->whereNotNull('academic_package_id');

        if ($limit) {
            $query->limit($limit);
        }

        $total = $query->count();
        $this->info("Found {$total} Academic subscriptions to process");

        if ($total === 0) {
            return $results;
        }

        $progressBar = $this->output->createProgressBar($total);

        // Process in chunks to prevent memory issues
        $query->with('package')->chunkById(100, function ($subscriptions) use (&$results, $isDryRun, $progressBar) {
            foreach ($subscriptions as $subscription) {
                $results['processed']++;

                try {
                    $package = $subscription->package;

                    if (!$package) {
                        $results['skipped']++;
                        $progressBar->advance();
                        continue;
                    }

                    $updateData = [
                        'package_name_ar' => $package->name_ar ?? $package->name ?? null,
                        'package_name_en' => $package->name_en ?? $package->name ?? null,
                        'package_price_monthly' => $package->price_monthly ?? $package->monthly_price ?? null,
                        'package_price_quarterly' => $package->price_quarterly ?? $package->quarterly_price ?? null,
                        'package_price_yearly' => $package->price_yearly ?? $package->yearly_price ?? null,
                        'package_sessions_per_week' => $package->sessions_per_week ?? null,
                        'package_session_duration_minutes' => $package->session_duration_minutes ?? $package->session_duration ?? null,
                    ];

                    if (!$isDryRun) {
                        $subscription->update($updateData);
                    }

                    $results['updated']++;

                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::warning("Failed to backfill Academic subscription {$subscription->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();

        return $results;
    }

    /**
     * Backfill Course subscription package data
     */
    private function backfillCourseSubscriptions(bool $isDryRun, ?int $limit): array
    {
        $this->info('Processing Course subscriptions...');

        $results = ['processed' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        $query = CourseSubscription::whereNull('package_name_ar');

        if ($limit) {
            $query->limit($limit);
        }

        $total = $query->count();
        $this->info("Found {$total} Course subscriptions to process");

        if ($total === 0) {
            return $results;
        }

        $progressBar = $this->output->createProgressBar($total);

        // Process in chunks to prevent memory issues
        $query->with(['recordedCourse', 'interactiveCourse'])->chunkById(100, function ($subscriptions) use (&$results, $isDryRun, $progressBar) {
            foreach ($subscriptions as $subscription) {
                $results['processed']++;

                try {
                    $course = $subscription->recordedCourse ?? $subscription->interactiveCourse;

                    if (!$course) {
                        $results['skipped']++;
                        $progressBar->advance();
                        continue;
                    }

                    // Determine course type
                    $courseType = $subscription->recorded_course_id ? 'recorded' : 'interactive';

                    $updateData = [
                        'course_type' => $courseType,
                        'package_name_ar' => $course->title_ar ?? $course->title ?? null,
                        'package_name_en' => $course->title_en ?? $course->title ?? null,
                    ];

                    if (!$isDryRun) {
                        $subscription->update($updateData);
                    }

                    $results['updated']++;

                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::warning("Failed to backfill Course subscription {$subscription->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();

        return $results;
    }

    /**
     * Display execution results
     */
    private function displayResults(array $results, bool $isDryRun): void
    {
        $mode = $isDryRun ? 'Simulation' : 'Execution';
        $this->info('');
        $this->info("Package Data Backfill {$mode} Results:");
        $this->info('═══════════════════════════════════════════════════');

        $totalProcessed = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($results as $type => $stats) {
            $this->info('');
            $this->info(ucfirst($type) . ' Subscriptions:');
            $this->info("  Processed: {$stats['processed']}");
            $this->info("  Updated: {$stats['updated']}");
            $this->info("  Skipped (no package): {$stats['skipped']}");
            if ($stats['errors'] > 0) {
                $this->error("  Errors: {$stats['errors']}");
            }

            $totalProcessed += $stats['processed'];
            $totalUpdated += $stats['updated'];
            $totalSkipped += $stats['skipped'];
            $totalErrors += $stats['errors'];
        }

        $this->info('');
        $this->info('Total Summary:');
        $this->info("  Processed: {$totalProcessed}");
        $this->info("  Updated: {$totalUpdated}");
        $this->info("  Skipped: {$totalSkipped}");
        if ($totalErrors > 0) {
            $this->error("  Errors: {$totalErrors}");
        }

        $this->info('');
        $this->info('Package data backfill completed.');
    }
}
