<?php

namespace App\Console\Commands;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POST-RECOVERY DATA FIX: After the April 7 2026 database loss, a March 31
 * backup was restored with partial binlog recovery. This left ~35 subscriptions
 * in status=active + payment_status=pending because payment webhook confirmations
 * were lost. This command fixes those subscriptions by setting payment_status=paid.
 */
class FixPostRecoveryPaymentStatus extends Command
{
    protected $signature = 'subscriptions:fix-post-recovery-payment-status
                          {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix subscriptions left with active+pending payment after April 7 data recovery';

    /** Date range for subscriptions affected by the recovery gap */
    private const RECOVERY_WINDOW_START = '2026-03-31 00:00:00';

    private const RECOVERY_WINDOW_END = '2026-04-06 23:59:59';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Scanning for subscriptions affected by April 7 data recovery...');
        $this->newLine();

        // Find subscriptions in the recovery window
        $recoveryTargets = QuranSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING)
            ->whereBetween('starts_at', [self::RECOVERY_WINDOW_START, self::RECOVERY_WINDOW_END])
            ->get();

        // Find ALL active+pending (for reporting purposes)
        $allActivePending = QuranSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING)
            ->get();

        $outsideWindow = $allActivePending->filter(function ($sub) use ($recoveryTargets) {
            return ! $recoveryTargets->contains('id', $sub->id);
        });

        // Report targets
        $this->info("Found {$recoveryTargets->count()} subscriptions in recovery window (March 31 – April 5):");
        $headers = ['ID', 'Student ID', 'Starts At', 'Ends At', 'Grace Period'];
        $rows = $recoveryTargets->map(fn ($sub) => [
            $sub->id,
            $sub->student_id,
            $sub->starts_at?->format('Y-m-d H:i'),
            $sub->ends_at?->format('Y-m-d H:i'),
            $sub->isInGracePeriod() ? 'Yes' : 'No',
        ])->toArray();
        $this->table($headers, $rows);

        if ($outsideWindow->isNotEmpty()) {
            $this->newLine();
            $this->warn("Found {$outsideWindow->count()} additional active+pending subscriptions OUTSIDE the recovery window (manual review needed):");
            $outsideRows = $outsideWindow->map(fn ($sub) => [
                $sub->id,
                $sub->student_id,
                $sub->starts_at?->format('Y-m-d H:i') ?? 'NULL',
                $sub->ends_at?->format('Y-m-d H:i') ?? 'NULL',
                $sub->isInGracePeriod() ? 'Yes' : 'No',
            ])->toArray();
            $this->table($headers, $outsideRows);
        }

        if ($recoveryTargets->isEmpty()) {
            $this->info('No subscriptions to fix in the recovery window.');

            return self::SUCCESS;
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info("Would fix {$recoveryTargets->count()} subscriptions. Run without --dry-run to apply.");

            return self::SUCCESS;
        }

        // Apply fixes
        $fixed = 0;
        $errors = 0;
        $timestamp = now()->format('Y-m-d H:i');

        foreach ($recoveryTargets as $subscription) {
            try {
                DB::transaction(function () use ($subscription, $timestamp) {
                    $subscription = QuranSubscription::withoutGlobalScopes()
                        ->lockForUpdate()
                        ->find($subscription->id);

                    $note = sprintf(
                        "[%s] payment_status fixed from 'pending' to 'paid' by post-recovery command. Original state: status=%s, payment=%s, starts=%s",
                        $timestamp,
                        $subscription->status->value,
                        $subscription->payment_status->value,
                        $subscription->starts_at,
                    );

                    $subscription->update([
                        'payment_status' => SubscriptionPaymentStatus::PAID,
                        'last_payment_date' => $subscription->last_payment_date ?? $subscription->starts_at ?? now(),
                        'admin_notes' => $subscription->admin_notes
                            ? $subscription->admin_notes."\n\n".$note
                            : $note,
                    ]);

                    // Also settle the current cycle if one exists
                    $subscription->ensureCurrentCycle();
                    if ($subscription->current_cycle_id) {
                        $cycle = SubscriptionCycle::find($subscription->current_cycle_id);
                        $cycle?->update([
                            'payment_status' => SubscriptionCycle::PAYMENT_PAID,
                        ]);
                    }

                    Log::info('Post-recovery payment status fixed', [
                        'subscription_id' => $subscription->id,
                        'student_id' => $subscription->student_id,
                        'starts_at' => $subscription->starts_at,
                    ]);
                });

                $fixed++;
                $this->line("  Fixed subscription #{$subscription->id} (student {$subscription->student_id})");
            } catch (\Throwable $e) {
                $errors++;
                $this->error("  Failed to fix subscription #{$subscription->id}: {$e->getMessage()}");
                Log::error('Post-recovery fix failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Done. Fixed: {$fixed}, Errors: {$errors}");

        if ($outsideWindow->isNotEmpty()) {
            $this->warn("Reminder: {$outsideWindow->count()} subscriptions outside the recovery window need manual review.");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
