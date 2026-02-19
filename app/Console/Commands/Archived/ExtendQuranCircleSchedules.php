<?php

namespace App\Console\Commands\Archived;

use Exception;
use App\Models\QuranCircleSchedule;
use Illuminate\Console\Command;

class ExtendQuranCircleSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quran:extend-schedules 
                           {--days=180 : Number of days ahead to generate sessions}
                           {--force : Force regeneration even if already sufficient}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extend session generation for all active Quran circle schedules';

    public function isHidden(): bool
    {
        return true;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysAhead = (int) $this->option('days');
        $force = $this->option('force');

        $this->info("Extending Quran circle schedules to generate {$daysAhead} days ahead...");

        // Get all active schedules
        $schedulesQuery = QuranCircleSchedule::where('is_active', true);

        if (! $force) {
            // Only update schedules that need extension
            $schedulesQuery->where(function ($query) use ($daysAhead) {
                $query->where('generate_ahead_days', '<', $daysAhead)
                    ->orWhere('last_generated_at', '<', now()->subDays(30))
                    ->orWhereNull('last_generated_at');
            });
        }

        $schedules = $schedulesQuery->get();

        if ($schedules->isEmpty()) {
            $this->info('No schedules need extension.');

            return Command::SUCCESS;
        }

        $this->info("Found {$schedules->count()} schedules to extend.");

        $totalGenerated = 0;
        $progressBar = $this->output->createProgressBar($schedules->count());
        $progressBar->start();

        foreach ($schedules as $schedule) {
            try {
                // Update the generation period
                $schedule->update(['generate_ahead_days' => $daysAhead]);

                // Generate upcoming sessions
                $generated = $schedule->generateUpcomingSessions();
                $totalGenerated += $generated;

                $this->line("\n✓ Circle ID {$schedule->circle_id}: Generated {$generated} sessions");

            } catch (Exception $e) {
                $this->error("\n✗ Circle ID {$schedule->circle_id}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->newLine(2);
        $this->info('✅ Extension completed!');
        $this->info("📊 Total sessions generated: {$totalGenerated}");
        $this->info("🔄 Schedules updated: {$schedules->count()}");

        return Command::SUCCESS;
    }
}
