<?php

namespace App\Console\Commands\Backfill;

use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\BackfillLog;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * One-off remediation for the "lie-state" subscriptions surfaced by the
 * 2026-05-13 audit: rows where `subscriptions.payment_status = 'paid'`
 * while `currentCycle.payment_status = 'pending'`.
 *
 * The lie hides the supervisor's `confirmPayment` Filament action (visible
 * only when payment_status ∈ [pending, failed]) AND blocked the renew-flow
 * payment-route redirect introduced 2026-05-13 from settling on the right
 * sub. Flipping `sub.payment_status → pending` resolves both:
 *   - Student can self-pay via the Renew → payment-route → gateway flow.
 *   - Supervisor can also confirm cash via "تأكيد الدفع".
 *
 * Idempotent: skips rows whose `payment_status` has already been demoted to
 * `pending` (or anything other than `paid`) by a prior run or admin action.
 *
 * Usage:
 *   php artisan subscriptions:fix-lie-state-subs --dry-run
 *   php artisan subscriptions:fix-lie-state-subs --apply
 *   php artisan subscriptions:fix-lie-state-subs --rollback
 */
class FixLieStateSubsCommand extends BaseBackfillCommand
{
    protected $signature = 'subscriptions:fix-lie-state-subs
                            {--dry-run : Print what would change without mutating (default)}
                            {--apply : Flip sub.payment_status from paid → pending for the lie-state rows}
                            {--rollback : Reverse a prior --apply run via the backfill_log audit trail}';

    protected $description = 'Fix subs whose row says paid but whose current cycle is still pending — surfaces the supervisor confirmPayment action.';

    protected const BUG_ID = 'lie_state_sub_payment_paid_cycle_pending';

    protected const COMMAND_NAME = 'subscriptions:fix-lie-state-subs';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollbackLogged();
        }

        $dryRun = (bool) $this->option('dry-run') || ! $this->option('apply');

        if ($dryRun) {
            $this->warn('Dry-run mode (default). Pass --apply to mutate.');
        }

        $targets = $this->findLieStateSubs();

        if ($targets->isEmpty()) {
            $this->info('No lie-state subscriptions found. Nothing to do.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d lie-state subscription(s).', $targets->count()));

        $applied = 0;
        $skipped = 0;

        foreach ($targets as $sub) {
            $changed = $this->processSub($sub, $dryRun);
            if ($changed) {
                $applied++;
            } else {
                $skipped++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. Subs %s: %d. Skipped: %d.',
            $dryRun ? 'planned' : 'applied',
            $applied,
            $skipped,
        ));

        return self::SUCCESS;
    }

    /**
     * Find every Quran + Academic subscription where the parent row reports
     * paid but the current cycle is still in pending payment state.
     *
     * @return Collection<int, Model>
     */
    private function findLieStateSubs(): Collection
    {
        $matches = collect();

        foreach ([QuranSubscription::class, AcademicSubscription::class] as $modelClass) {
            $rows = $modelClass::query()
                ->where('payment_status', SubscriptionPaymentStatus::PAID)
                ->whereHas('currentCycle', function ($q) {
                    $q->where('cycle_state', SubscriptionCycle::STATE_ACTIVE)
                        ->where('payment_status', SubscriptionCycle::PAYMENT_PENDING);
                })
                ->get();

            $matches = $matches->concat($rows);
        }

        return $matches;
    }

    private function processSub(Model $sub, bool $dryRun): bool
    {
        // Idempotency guard: re-read in case state changed since the audit query
        $sub->refresh();

        if ($sub->payment_status !== SubscriptionPaymentStatus::PAID) {
            $this->line(sprintf(
                '  %s #%d: payment_status is %s (already remediated), skipping',
                class_basename($sub),
                $sub->id,
                $sub->payment_status->value ?? (string) $sub->payment_status,
            ));

            return false;
        }

        $cycle = $sub->currentCycle;
        if (! $cycle
            || $cycle->cycle_state !== SubscriptionCycle::STATE_ACTIVE
            || $cycle->payment_status !== SubscriptionCycle::PAYMENT_PENDING) {
            $this->line(sprintf(
                '  %s #%d: current cycle no longer in active+pending shape, skipping',
                class_basename($sub),
                $sub->id,
            ));

            return false;
        }

        $this->info(sprintf(
            '  %s #%d: flip payment_status paid → pending (current cycle %d still pending)',
            class_basename($sub),
            $sub->id,
            $cycle->id,
        ));

        if ($dryRun) {
            return true;
        }

        DB::transaction(function () use ($sub): void {
            $original = SubscriptionPaymentStatus::PAID->value;

            BackfillLog::record(
                static::BUG_ID,
                static::COMMAND_NAME,
                $sub,
                'payment_status',
                $original,
                SubscriptionPaymentStatus::PENDING->value,
            );

            $sub->update([
                'payment_status' => SubscriptionPaymentStatus::PENDING,
            ]);
        });

        return true;
    }
}
