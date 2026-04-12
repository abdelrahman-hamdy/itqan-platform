<?php

namespace App\Console\Commands;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Bootstrap initial subscription_cycles rows from existing subscription data.
 *
 * For each ACTIVE/PAUSED subscription that has no current_cycle_id, creates
 * an initial cycle row mirroring the subscription's current billing window,
 * session counts, and pricing.
 *
 * Safe to re-run: skips subscriptions that already have a current_cycle_id.
 *
 * Usage:
 *   php artisan subscriptions:bootstrap-cycles --dry-run   # preview only
 *   php artisan subscriptions:bootstrap-cycles              # execute
 */
class BootstrapSubscriptionCycles extends Command
{
    protected $signature = 'subscriptions:bootstrap-cycles
        {--dry-run : Preview changes without writing}';

    protected $description = 'Create initial subscription_cycles from existing subscription data';

    private int $created = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '🔍 DRY RUN — no data will be modified' : '⚡ LIVE RUN — data will be updated');
        $this->newLine();

        if (! Schema::hasTable('subscription_cycles')) {
            $this->error('subscription_cycles table does not exist. Run migrations first.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('quran_subscriptions', 'current_cycle_id')) {
            $this->error('current_cycle_id column does not exist on quran_subscriptions. Run migrations first.');

            return self::FAILURE;
        }

        $this->info('Processing Quran Subscriptions...');
        $this->bootstrapForModel(QuranSubscription::class, $dryRun);

        $this->info('Processing Academic Subscriptions...');
        $this->bootstrapForModel(AcademicSubscription::class, $dryRun);

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Cycles created', $this->created],
                ['Skipped (already has cycle)', $this->skipped],
                ['Errors', $this->errors],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN complete. Run without --dry-run to apply changes.');
        } else {
            $this->info('Bootstrap complete.');
        }

        return self::SUCCESS;
    }

    private function bootstrapForModel(string $modelClass, bool $dryRun): void
    {
        $query = $modelClass::whereIn('status', [
            SessionSubscriptionStatus::ACTIVE->value,
            SessionSubscriptionStatus::PAUSED->value,
            SessionSubscriptionStatus::EXPIRED->value,
        ])->whereNull('current_cycle_id');

        $total = $query->count();
        $this->line("  Found {$total} subscriptions without a cycle row");

        if ($total === 0) {
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(50, function ($subscriptions) use ($dryRun, $bar) {
            foreach ($subscriptions as $subscription) {
                $bar->advance();

                try {
                    if (! $dryRun) {
                        DB::transaction(function () use ($subscription) {
                            $subscription = $subscription::lockForUpdate()->find($subscription->id);

                            // Double-check inside transaction
                            if ($subscription->current_cycle_id) {
                                $this->skipped++;

                                return;
                            }

                            $subscription->ensureCurrentCycle();
                            $this->created++;
                        });
                    } else {
                        $this->created++;
                    }
                } catch (\Exception $e) {
                    $this->errors++;
                    Log::warning('Failed to bootstrap cycle for subscription', [
                        'subscription_id' => $subscription->id,
                        'type' => get_class($subscription),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $bar->finish();
        $this->newLine();
    }
}
