<?php

namespace App\Console\Commands;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Suspend subscriptions whose grace period has expired without payment.
 *
 * Finds ACTIVE subscriptions with PENDING payment where ends_at has passed,
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

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $suspendedCount = 0;

        // Find Quran subscriptions in grace period that have expired
        $quranSubs = QuranSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING)
            ->where('ends_at', '<', now())
            ->get();

        foreach ($quranSubs as $sub) {
            if ($dryRun) {
                $this->info("[DRY RUN] Would suspend QuranSubscription #{$sub->id} ({$sub->subscription_code})");
            } else {
                $sub->update(['status' => SessionSubscriptionStatus::SUSPENDED]);
                Log::info('Grace period expired — subscription suspended', [
                    'subscription_id' => $sub->id,
                    'subscription_code' => $sub->subscription_code,
                    'type' => 'quran',
                    'ends_at' => $sub->ends_at?->toDateTimeString(),
                ]);
                $this->info("Suspended QuranSubscription #{$sub->id} ({$sub->subscription_code})");
            }
            $suspendedCount++;
        }

        // Find Academic subscriptions in grace period that have expired
        $academicSubs = AcademicSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING)
            ->where('ends_at', '<', now())
            ->get();

        foreach ($academicSubs as $sub) {
            if ($dryRun) {
                $this->info("[DRY RUN] Would suspend AcademicSubscription #{$sub->id} ({$sub->subscription_code})");
            } else {
                $sub->update(['status' => SessionSubscriptionStatus::SUSPENDED]);
                Log::info('Grace period expired — subscription suspended', [
                    'subscription_id' => $sub->id,
                    'subscription_code' => $sub->subscription_code,
                    'type' => 'academic',
                    'ends_at' => $sub->ends_at?->toDateTimeString(),
                ]);
                $this->info("Suspended AcademicSubscription #{$sub->id} ({$sub->subscription_code})");
            }
            $suspendedCount++;
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Done. {$suspendedCount} subscription(s) ".($dryRun ? 'would be' : '').' suspended.');

        return self::SUCCESS;
    }
}
