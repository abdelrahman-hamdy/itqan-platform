<?php

namespace App\Console\Commands;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Suspend subscriptions whose admin-granted grace period has expired without payment.
 *
 * Finds ACTIVE subscriptions with metadata['grace_period_ends_at'] in the past,
 * indicating the grace period given by admin has expired without the student paying.
 * Sets their status to SUSPENDED, which cascades to education units via model observers.
 *
 * Usage:
 *   php artisan subscriptions:suspend-expired-grace
 *   php artisan subscriptions:suspend-expired-grace --dry-run
 */
class SuspendExpiredGraceSubscriptions extends Command
{
    protected $signature = 'subscriptions:suspend-expired-grace
                            {--dry-run : Preview changes without making them}';

    protected $description = 'Suspend subscriptions whose grace period has expired without payment';

    /**
     * Handles admin-granted grace periods (payment_status != FAILED).
     * Auto-renewal failure grace periods (payment_status=FAILED) are handled by
     * ExpireGracePeriodSubscriptions job instead.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $suspendedCount = 0;

        // Find Quran subscriptions with expired admin-granted grace period
        QuranSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('payment_status', '!=', SubscriptionPaymentStatus::FAILED)
            ->whereNotNull('metadata')
            ->chunkById(100, function ($subscriptions) use ($dryRun, &$suspendedCount) {
                foreach ($subscriptions as $sub) {
                    if (! $this->hasExpiredGracePeriod($sub)) {
                        continue;
                    }

                    if ($dryRun) {
                        $this->info("[DRY RUN] Would suspend QuranSubscription #{$sub->id} ({$sub->subscription_code})");
                    } else {
                        $this->suspendSubscription($sub, 'quran');
                        $this->info("Suspended QuranSubscription #{$sub->id} ({$sub->subscription_code})");
                    }
                    $suspendedCount++;
                }
            });

        // Find Academic subscriptions with expired admin-granted grace period
        AcademicSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('payment_status', '!=', SubscriptionPaymentStatus::FAILED)
            ->whereNotNull('metadata')
            ->chunkById(100, function ($subscriptions) use ($dryRun, &$suspendedCount) {
                foreach ($subscriptions as $sub) {
                    if (! $this->hasExpiredGracePeriod($sub)) {
                        continue;
                    }

                    if ($dryRun) {
                        $this->info("[DRY RUN] Would suspend AcademicSubscription #{$sub->id} ({$sub->subscription_code})");
                    } else {
                        $this->suspendSubscription($sub, 'academic');
                        $this->info("Suspended AcademicSubscription #{$sub->id} ({$sub->subscription_code})");
                    }
                    $suspendedCount++;
                }
            });

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Done. {$suspendedCount} subscription(s) ".($dryRun ? 'would be' : '').' suspended.');

        return self::SUCCESS;
    }

    /**
     * Check if a subscription has an expired grace period (checks both keys).
     */
    private function hasExpiredGracePeriod($subscription): bool
    {
        $metadata = $subscription->metadata ?? [];

        $key = isset($metadata['grace_period_ends_at']) ? 'grace_period_ends_at'
            : (isset($metadata['grace_period_expires_at']) ? 'grace_period_expires_at' : null);

        if (! $key) {
            return false;
        }

        return Carbon::parse($metadata[$key])->isPast();
    }

    /**
     * Suspend a subscription and clean up grace metadata.
     */
    private function suspendSubscription($sub, string $type): void
    {
        $metadata = $sub->metadata ?? [];
        unset(
            $metadata['grace_period_ends_at'],
            $metadata['grace_period_expires_at'],
            $metadata['grace_period_started_at'],
            $metadata['grace_notification_last_sent_at']
        );

        $sub->update([
            'status' => SessionSubscriptionStatus::SUSPENDED,
            'metadata' => $metadata ?: null,
        ]);

        Log::info('Grace period expired — subscription suspended', [
            'subscription_id' => $sub->id,
            'subscription_code' => $sub->subscription_code,
            'type' => $type,
            'ends_at' => $sub->ends_at?->toDateTimeString(),
        ]);
    }
}
