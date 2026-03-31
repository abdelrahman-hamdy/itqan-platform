<?php

namespace App\Console\Commands;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ExpireActiveSubscriptions Command
 *
 * Transitions ACTIVE subscriptions to EXPIRED when their ends_at date has passed.
 * Respects grace periods — subscriptions with an active grace period are skipped.
 *
 * Usage:
 *   php artisan subscriptions:expire-active
 *   php artisan subscriptions:expire-active --dry-run
 *   php artisan subscriptions:expire-active --force
 */
class ExpireActiveSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire-active
                            {--dry-run : Preview changes without making them}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Expire active subscriptions past their end date';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Scanning for active subscriptions past their end date...');
        $this->newLine();

        $stats = [
            'quran' => 0,
            'academic' => 0,
            'skipped_grace' => 0,
            'errors' => 0,
        ];

        $now = now();

        // Process both subscription types with shared logic
        $types = [
            'quran' => QuranSubscription::class,
            'academic' => AcademicSubscription::class,
        ];

        foreach ($types as $type => $modelClass) {
            // Pre-fetch IDs that have a pending renewal (single query instead of N+1)
            $idsWithPendingRenewal = $modelClass::withoutGlobalScopes()
                ->where('status', SessionSubscriptionStatus::PENDING)
                ->whereNotNull('previous_subscription_id')
                ->pluck('previous_subscription_id')
                ->flip();

            $modelClass::withoutGlobalScopes()
                ->where('status', SessionSubscriptionStatus::ACTIVE)
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', $now)
                ->chunkById(100, function ($subscriptions) use ($dryRun, $now, &$stats, $type, $idsWithPendingRenewal) {
                    foreach ($subscriptions as $subscription) {
                        if ($this->isInGracePeriod($subscription, $now)) {
                            $stats['skipped_grace']++;

                            continue;
                        }

                        if ($idsWithPendingRenewal->has($subscription->id)) {
                            $stats['skipped_grace']++;

                            continue;
                        }

                        if ($dryRun) {
                            $this->line("  [DRY RUN] Would expire {$type} subscription #{$subscription->id} (ends_at: {$subscription->ends_at})");
                            $stats[$type]++;

                            continue;
                        }

                        try {
                            $subscription->update(['status' => SessionSubscriptionStatus::EXPIRED]);
                            $stats[$type]++;

                            Log::info('Subscription auto-expired', [
                                'subscription_id' => $subscription->id,
                                'type' => $type,
                                'ends_at' => $subscription->ends_at->toDateTimeString(),
                            ]);
                        } catch (\Exception $e) {
                            $stats['errors']++;
                            Log::error('Failed to expire subscription', [
                                'subscription_id' => $subscription->id,
                                'type' => $type,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });
        }

        $total = $stats['quran'] + $stats['academic'];

        if ($total === 0 && $stats['skipped_grace'] === 0) {
            $this->info('No subscriptions to expire.');

            return Command::SUCCESS;
        }

        // Display results
        $this->table(
            ['Type', 'Expired'],
            [
                ['Quran', $stats['quran']],
                ['Academic', $stats['academic']],
                ['Total', $total],
            ]
        );

        if ($stats['skipped_grace'] > 0) {
            $this->line("  Skipped (grace period): {$stats['skipped_grace']}");
        }

        if ($stats['errors'] > 0) {
            $this->warn("  Errors: {$stats['errors']}");
        }

        if ($dryRun) {
            $this->warn('Dry run mode — no changes made.');
        } else {
            $this->info("Expired {$total} subscription(s).");
        }

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Check if a subscription is currently in an active grace period.
     */
    private function isInGracePeriod($subscription, Carbon $now): bool
    {
        $metadata = $subscription->metadata ?? [];

        $key = isset($metadata['grace_period_ends_at']) ? 'grace_period_ends_at'
            : (isset($metadata['grace_period_expires_at']) ? 'grace_period_expires_at' : null);

        if (! $key) {
            return false;
        }

        return Carbon::parse($metadata[$key])->isAfter($now);
    }
}
