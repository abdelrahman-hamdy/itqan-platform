<?php

namespace App\Console\Commands\Archived;

use Exception;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateQuranSystemData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quran:migrate-data 
                            {--dry-run : Show what would be migrated without actually migrating}
                            {--force : Skip confirmation prompts}
                            {--individual-only : Only migrate individual subscriptions}
                            {--groups-only : Only migrate group circle schedules}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing Quran subscription and circle data to the new system structure';

    /**
     * Hide this command in production - one-time migration only.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting Quran system data migration...');

        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be changed');
        }

        // Show migration plan
        $this->showMigrationPlan();

        if (! $force && ! $isDryRun) {
            if (! $this->confirm('Do you want to proceed with the migration?')) {
                $this->info('Migration cancelled');

                return self::SUCCESS;
            }
        }

        try {
            DB::beginTransaction();

            $results = [
                'individual_circles' => 0,
                'group_schedules' => 0,
                'sessions_updated' => 0,
            ];

            // Step 1: Migrate individual subscriptions to individual circles
            if (! $this->option('groups-only')) {
                $results['individual_circles'] = $this->migrateIndividualSubscriptions($isDryRun);
            }

            // Step 2: Create schedules for group circles that have schedule data
            if (! $this->option('individual-only')) {
                $results['group_schedules'] = $this->migrateGroupCircleSchedules($isDryRun);
            }

            // Step 3: Update existing sessions to use new structure
            $results['sessions_updated'] = $this->updateExistingSessions($isDryRun);

            if (! $isDryRun) {
                DB::commit();
                $this->info('✅ Migration completed successfully!');
            } else {
                DB::rollBack();
                $this->info('🔍 Dry run completed - no changes made');
            }

            $this->showResults($results, $isDryRun);

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('❌ Migration failed: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Show migration plan
     */
    private function showMigrationPlan(): void
    {
        $this->newLine();
        $this->line('📋 <comment>Migration Plan</comment>');

        // Count individual subscriptions without circles
        $individualCount = QuranSubscription::where('subscription_type', 'individual')
            ->whereDoesntHave('individualCircle')
            ->count();

        // Count group circles without schedules
        $groupCount = QuranCircle::whereDoesntHave('schedule')->count();

        $this->line("Individual subscriptions to migrate: <info>{$individualCount}</info>");
        $this->line("Group circles needing schedules: <info>{$groupCount}</info>");
    }

    /**
     * Migrate individual subscriptions
     */
    private function migrateIndividualSubscriptions(bool $isDryRun): int
    {
        $this->info('📝 Migrating individual subscriptions...');

        $query = QuranSubscription::where('subscription_type', 'individual')
            ->whereDoesntHave('individualCircle');

        $total = $query->count();

        if ($total === 0) {
            $this->line('   No subscriptions to migrate');

            return 0;
        }

        $migratedCount = 0;

        // Process in chunks to prevent memory issues
        $query->chunkById(100, function ($subscriptions) use ($isDryRun, &$migratedCount) {
            foreach ($subscriptions as $subscription) {
                if (! $isDryRun) {
                    $subscription->createIndividualCircle();
                }
                $migratedCount++;
            }
        });

        $this->line("   ✅ Migrated {$migratedCount} subscriptions");

        return $migratedCount;
    }

    /**
     * Migrate group circle schedules
     */
    private function migrateGroupCircleSchedules(bool $isDryRun): int
    {
        $this->info('📅 Migrating group circle schedules...');

        $query = QuranCircle::whereDoesntHave('schedule')
            ->whereNotNull('quran_teacher_id');

        $total = $query->count();

        if ($total === 0) {
            $this->line('   No circles to update');

            return 0;
        }

        $migratedCount = 0;

        // Process in chunks to prevent memory issues
        $query->chunkById(100, function ($circles) use ($isDryRun, &$migratedCount) {
            foreach ($circles as $circle) {
                if (! $isDryRun) {
                    // Update circle to inactive until teacher sets schedule
                    $circle->update(['status' => false]);
                }
                $migratedCount++;
            }
        });

        $this->line("   ✅ Updated {$migratedCount} circles");

        return $migratedCount;
    }

    /**
     * Update existing sessions
     */
    private function updateExistingSessions(bool $isDryRun): int
    {
        $this->info('🔄 Updating sessions...');

        $query = QuranSession::whereNull('is_template');

        $total = $query->count();

        if ($total === 0) {
            $this->line('   No sessions to update');

            return 0;
        }

        $updatedCount = 0;

        // Process in chunks to prevent memory issues
        $query->chunkById(200, function ($sessions) use ($isDryRun, &$updatedCount) {
            foreach ($sessions as $session) {
                if (! $isDryRun) {
                    $session->update([
                        'is_template' => false,
                        'is_scheduled' => true,
                        'session_type' => $session->circle_id ? 'group' : 'individual',
                    ]);
                }
                $updatedCount++;
            }
        });

        $this->line("   ✅ Updated {$updatedCount} sessions");

        return $updatedCount;
    }

    /**
     * Show results
     */
    private function showResults(array $results, bool $isDryRun): void
    {
        $this->newLine();
        $this->line('📊 <comment>Results</comment>');

        $prefix = $isDryRun ? 'Would migrate' : 'Migrated';
        $this->line("{$prefix} individual circles: {$results['individual_circles']}");
        $this->line("{$prefix} group schedules: {$results['group_schedules']}");
        $this->line("{$prefix} sessions: {$results['sessions_updated']}");
    }
}
