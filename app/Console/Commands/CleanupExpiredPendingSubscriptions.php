<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

/**
 * CleanupExpiredPendingSubscriptions Command
 *
 * Cancels pending subscriptions that have not been paid within the configured time limit.
 * This helps keep the database clean and prevents users from accumulating stale pending subscriptions.
 *
 * Usage:
 *   php artisan subscriptions:cleanup-expired-pending
 *   php artisan subscriptions:cleanup-expired-pending --hours=24 --dry-run
 *   php artisan subscriptions:cleanup-expired-pending --force
 */
class CleanupExpiredPendingSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:cleanup-expired-pending
                            {--hours= : Hours after which pending subscriptions are considered expired (default: config value)}
                            {--dry-run : Preview changes without making them}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel pending subscriptions that have not been paid within the configured time limit';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionService $subscriptionService): int
    {
        $hours = $this->option('hours')
            ? (int) $this->option('hours')
            : config('subscriptions.pending.expires_after_hours', 48);
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Scanning for pending subscriptions older than {$hours} hours...");
        $this->newLine();

        // First, do a dry run to show what will be affected
        $preview = $subscriptionService->cleanupExpiredPending($hours, true);

        if ($preview['cancelled'] === 0) {
            $this->info('No expired pending subscriptions found.');

            return Command::SUCCESS;
        }

        // Display preview
        $this->table(
            ['Subscription Type', 'Count'],
            [
                ['Quran', $preview['by_type'][SubscriptionService::TYPE_QURAN]],
                ['Academic', $preview['by_type'][SubscriptionService::TYPE_ACADEMIC]],
                ['Course', $preview['by_type'][SubscriptionService::TYPE_COURSE]],
                ['Total', $preview['cancelled']],
            ]
        );

        if ($dryRun) {
            $this->warn('Dry run mode - no changes made.');

            return Command::SUCCESS;
        }

        // Confirm before proceeding
        if (! $force && ! $this->confirm("Cancel {$preview['cancelled']} expired pending subscription(s)?")) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        // Execute the cleanup
        $this->info('Processing...');
        $this->newLine();

        $result = $subscriptionService->cleanupExpiredPending($hours, false);

        // Show results
        $this->info('Cleanup completed!');
        $this->newLine();

        $this->table(
            ['Subscription Type', 'Cancelled'],
            [
                ['Quran', $result['by_type'][SubscriptionService::TYPE_QURAN]],
                ['Academic', $result['by_type'][SubscriptionService::TYPE_ACADEMIC]],
                ['Course', $result['by_type'][SubscriptionService::TYPE_COURSE]],
                ['Total', $result['cancelled']],
            ]
        );

        $this->newLine();
        $this->info("Successfully cancelled {$result['cancelled']} expired pending subscription(s).");

        return Command::SUCCESS;
    }
}
