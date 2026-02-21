<?php

namespace App\Console\Commands;

use App\Enums\SessionSubscriptionStatus;
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

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $suspendedCount = 0;

        // Find Quran subscriptions with expired grace period
        $quranSubs = QuranSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->whereNotNull('metadata')
            ->get()
            ->filter(function ($subscription) {
                $metadata = $subscription->metadata ?? [];
                if (! isset($metadata['grace_period_ends_at'])) {
                    return false;
                }

                return Carbon::parse($metadata['grace_period_ends_at'])->isPast();
            });

        foreach ($quranSubs as $sub) {
            if ($dryRun) {
                $this->info("[DRY RUN] Would suspend QuranSubscription #{$sub->id} ({$sub->subscription_code})");
            } else {
                $metadata = $sub->metadata ?? [];
                unset($metadata['grace_period_ends_at']);

                $sub->update([
                    'status' => SessionSubscriptionStatus::SUSPENDED,
                    'metadata' => $metadata ?: null,
                ]);
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

        // Find Academic subscriptions with expired grace period
        $academicSubs = AcademicSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->whereNotNull('metadata')
            ->get()
            ->filter(function ($subscription) {
                $metadata = $subscription->metadata ?? [];
                if (! isset($metadata['grace_period_ends_at'])) {
                    return false;
                }

                return Carbon::parse($metadata['grace_period_ends_at'])->isPast();
            });

        foreach ($academicSubs as $sub) {
            if ($dryRun) {
                $this->info("[DRY RUN] Would suspend AcademicSubscription #{$sub->id} ({$sub->subscription_code})");
            } else {
                $metadata = $sub->metadata ?? [];
                unset($metadata['grace_period_ends_at']);

                $sub->update([
                    'status' => SessionSubscriptionStatus::SUSPENDED,
                    'metadata' => $metadata ?: null,
                ]);
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
