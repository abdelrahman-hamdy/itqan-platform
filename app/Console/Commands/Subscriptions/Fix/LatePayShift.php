<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * One-off backfill for cycles whose `starts_at` is in the past, payment
 * landed late, and no consumption was recorded yet — i.e. the student
 * paid for a window they couldn't use. Shifts cycle.starts_at to now
 * and ends_at by the same delta so they get the full window from the
 * payment moment forward.
 *
 * Forward-only protection lives in {@see \App\Services\Subscription\SubscriptionPayment::markCyclePaid()}.
 * Run this once after Phase 1 lands to compensate cycles that paid late
 * before the protection was in place.
 *
 * Eligibility:
 *   - cycle.payment_status = PAID
 *   - cycle.starts_at < cycle.payment.paid_at (or cycle.starts_at < now())
 *   - cycle has zero active SessionConsumption rows
 *
 * BackfillLog per cycle (column_name = 'starts_at_ends_at') with the JSON
 * original values for rollback.
 */
class LatePayShift extends Command
{
    protected $signature = 'subscriptions:fix-late-pay-shift
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--days=60 : Only consider cycles paid within the last N days}
                            {--cycle= : Single cycle id to process (skips the date filter)}';

    protected $description = 'Shift cycle.starts_at/ends_at forward for cycles whose payment landed after the window began and no sessions ran.';

    private const BUG_ID = 'late-pay-shift';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $days = (int) $this->option('days');
        $cycleId = $this->option('cycle');

        $query = SubscriptionCycle::query()
            ->where('payment_status', SubscriptionCycle::PAYMENT_PAID)
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at');

        if ($cycleId !== null) {
            $query->whereKey((int) $cycleId);
        } else {
            $query->where('updated_at', '>=', now()->subDays($days));
        }

        $scanned = 0;
        $eligible = 0;
        $touched = 0;
        $errors = 0;

        $query->chunkById(200, function ($cycles) use ($apply, &$scanned, &$eligible, &$touched, &$errors) {
            foreach ($cycles as $cycle) {
                $scanned++;
                try {
                    if (! $this->isEligible($cycle)) {
                        continue;
                    }
                    $eligible++;

                    $duration = $cycle->starts_at->diffInSeconds($cycle->ends_at);
                    $newStartsAt = Carbon::now();
                    $newEndsAt = $newStartsAt->copy()->addSeconds($duration);

                    $this->line(sprintf(
                        'cycle #%d (sub %s#%d): %s..%s → %s..%s',
                        $cycle->id,
                        $cycle->subscribable_type,
                        $cycle->subscribable_id,
                        $cycle->starts_at->toDateTimeString(),
                        $cycle->ends_at->toDateTimeString(),
                        $newStartsAt->toDateTimeString(),
                        $newEndsAt->toDateTimeString(),
                    ));

                    if (! $apply) {
                        continue;
                    }

                    BackfillLog::create([
                        'bug_id' => self::BUG_ID,
                        'table_name' => 'subscription_cycles',
                        'row_id' => $cycle->id,
                        'column_name' => 'starts_at_ends_at',
                        'original_value' => json_encode([
                            'starts_at' => $cycle->starts_at->toIso8601String(),
                            'ends_at' => $cycle->ends_at->toIso8601String(),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'new_value' => json_encode([
                            'starts_at' => $newStartsAt->toIso8601String(),
                            'ends_at' => $newEndsAt->toIso8601String(),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'backfill_command' => 'subscriptions:fix-late-pay-shift',
                        'ran_at' => now(),
                    ]);

                    $cycle->starts_at = $newStartsAt;
                    $cycle->ends_at = $newEndsAt;
                    $cycle->save();
                    $touched++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->warn(sprintf('cycle #%d ERROR: %s', $cycle->id, $e->getMessage()));
                }
            }
        });

        $this->newLine();
        $this->info(sprintf(
            '%s: scanned=%d eligible=%d touched=%d errors=%d',
            $apply ? 'APPLIED' : 'DRY-RUN —',
            $scanned,
            $eligible,
            $touched,
            $errors,
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the writes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isEligible(SubscriptionCycle $cycle): bool
    {
        // Window must already have begun.
        if (! $cycle->starts_at->isPast()) {
            return false;
        }

        // No consumption rows recorded on the cycle (active or reversed
        // — both count as "session occurred on this window").
        $hasConsumption = SessionConsumption::query()
            ->where('cycle_id', $cycle->getKey())
            ->exists();

        return ! $hasConsumption;
    }
}
