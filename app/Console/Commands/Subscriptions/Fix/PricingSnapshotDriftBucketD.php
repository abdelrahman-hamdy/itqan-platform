<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\BillingCycle;
use App\Models\AcademicPackage;
use App\Models\AcademicSubscription;
use App\Models\BackfillLog;
use App\Models\Payment;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\PricingResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * INV-D2 auto-fix — Bucket D-HIGH branch.
 *
 * Targets DRIFT_FROM_PACKAGE_PRICE cycles (NULL `package_snapshot`) that
 * satisfy ALL of the following triple-gate:
 *
 *   1. **Unique pkg match by price + sessions.** The cycle's `final_price +
 *      discount_amount` matches exactly one academy package's regular or
 *      sale price for the cycle's billing_cycle, AND that pkg's
 *      `sessions_per_month` equals the cycle's `total_sessions`.
 *
 *   2. **Payment-truth verification.** At least one COMPLETED payment row
 *      tied to the parent sub matches `cycle.final_price` exactly. Without
 *      this, we'd risk synthesizing a snapshot for a cycle whose
 *      `final_price` itself is wrong (the 21 cycles caught by this gate
 *      have cycle.final_price disagreeing with the actual paid amount).
 *
 *   3. **Same audit class as the snapshot-quality cycles already fixed in
 *      Bucket A/C.** Synthesizes a complete snapshot from the winning pkg.
 *      The snapshot becomes the new authoritative source per the checker's
 *      preference order; cycle.package_id stays untouched (separate
 *      concern). INV-D2 satisfied by snapshot prices matching final_price.
 *
 * Strict-by-default. Dry-run lists every gate decision per cycle so the
 * operator can read why each cycle is/isn't eligible.
 *
 * BackfillLog row per write under `bug_id='inv-d2-snapshot-bucket-d-high'`.
 */
class PricingSnapshotDriftBucketD extends Command
{
    protected $signature = 'subscriptions:fix-pricing-snapshot-drift-bucket-d
                            {--apply : Actually perform the writes (default is dry-run)}
                            {--academy= : Restrict to one academy id}';

    protected $description = 'INV-D2 auto-fix — synthesize snapshot for NULL-snapshot cycles where pkg match + payment-truth verify.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $academyId = $this->option('academy') !== null ? (int) $this->option('academy') : null;

        $query = SubscriptionCycle::query()
            ->withoutGlobalScopes()
            ->where('pricing_source', 'package');
        if ($academyId !== null) {
            $query->where('academy_id', $academyId);
        }

        // Phase 1: filter to NULL-snapshot cycles only.
        $cycles = $query->get()->filter(function (SubscriptionCycle $c) {
            $snap = $c->package_snapshot;
            return ! (is_array($snap) && ! empty($snap));
        });

        $eligible = [];
        $rejected = [];
        $alreadyClean = 0;

        foreach ($cycles as $cycle) {
            $result = $this->classifyDrift($cycle);
            if ($result === null) {
                $alreadyClean++;
                continue;
            }
            if ($result['eligible']) {
                $eligible[] = $result;
            } else {
                $rejected[] = $result;
            }
        }

        $this->info(sprintf('Examined: %d NULL-snapshot cycles', count($cycles)));
        $this->info(sprintf('  Already INV-D2 clean: %d', $alreadyClean));
        $this->info(sprintf('  Currently violating: %d', count($eligible) + count($rejected)));
        $this->info(sprintf('  Auto-fixable (passed all 3 gates): %d', count($eligible)));

        // Reject reasons
        $byReject = [];
        foreach ($rejected as $r) {
            $byReject[$r['reject_reason']] = ($byReject[$r['reject_reason']] ?? 0) + 1;
        }
        if ($byReject !== []) {
            $this->warn('Rejected (NOT auto-fixed):');
            $this->table(
                ['Reason', 'Count'],
                array_map(fn ($k, $v) => [$k, $v], array_keys($byReject), array_values($byReject)),
            );
        }

        if ($eligible === []) {
            $this->info('Nothing to apply.');
            return self::SUCCESS;
        }

        $this->line('');
        $this->line(sprintf('Eligible cycles (all %d):', count($eligible)));
        $this->table(
            ['cycle_id', 'sub_id', 'sub_type', 'state', 'final', 'sessions', 'bc', 'winner_pkg', 'paid_kind'],
            array_map(static fn ($r) => [
                $r['cycle_id'], $r['sub_id'], $r['sub_type'], $r['state'],
                $r['final_price'], $r['total_sessions'], $r['billing_cycle'],
                '#'.$r['winner_pkg_id'].' '.$r['winner_pkg_sessions'].'x'.$r['winner_pkg_duration'],
                $r['paid_kind'],
            ], $eligible),
        );

        if (! $apply) {
            $this->line('');
            $this->comment('DRY-RUN. Re-run with --apply to perform the writes.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($eligible));
        $bar->start();
        $touched = 0;
        $errors = 0;
        foreach ($eligible as $plan) {
            try {
                DB::transaction(fn () => $this->applyPlan($plan));
                $touched++;
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf("\ncycle #%d: %s", $plan['cycle_id'], $e->getMessage()));
            }
            $bar->advance();
        }
        $bar->finish();
        $this->line('');
        $this->info(sprintf('APPLIED: %d cycle(s); %d error(s).', $touched, $errors));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{eligible: bool, ...}|null
     */
    private function classifyDrift(SubscriptionCycle $cycle): ?array
    {
        $sub = match ($cycle->subscribable_type) {
            'quran_subscription' => QuranSubscription::withoutGlobalScopes()->with('package')->find($cycle->subscribable_id),
            'academic_subscription' => AcademicSubscription::withoutGlobalScopes()->with('package')->find($cycle->subscribable_id),
            default => null,
        };
        if ($sub === null || $sub->package === null) {
            return null;
        }

        $bc = BillingCycle::tryFrom((string) $cycle->billing_cycle);
        if ($bc === null) {
            return null;
        }

        $liveBase = PricingResolver::resolvePriceFromPackage($sub->package, $bc);
        $finalPrice = (float) $cycle->final_price;
        $discount = (float) $cycle->discount_amount;
        $expectedFromLive = (float) $liveBase - $discount;

        // INV-D2 holds via live fallback? Then no-op.
        if (abs($finalPrice - $expectedFromLive) < 0.01) {
            return null;
        }

        // Exclude FREE_NOT_OVERRIDE.
        if ($finalPrice === 0.0 && $liveBase > 0.0) {
            return ['eligible' => false, 'reject_reason' => 'free_not_override', 'cycle_id' => $cycle->id, 'sub_id' => $sub->id];
        }

        // GATE 1: unique pkg by price + sessions.
        $pkgs = $cycle->subscribable_type === 'quran_subscription'
            ? QuranPackage::withoutGlobalScopes()->where('academy_id', $sub->academy_id)->get()
            : AcademicPackage::withoutGlobalScopes()->where('academy_id', $sub->academy_id)->get();

        $target = $finalPrice + $discount;
        $priceMatches = [];
        foreach ($pkgs as $p) {
            $reg = (float) ($p->{$bc->value.'_price'} ?? 0);
            $sale = $p->{'sale_'.$bc->value.'_price'} !== null ? (float) $p->{'sale_'.$bc->value.'_price'} : null;
            if (abs($target - $reg) < 0.01) {
                $priceMatches[$p->id] = ['pkg' => $p, 'kind' => 'regular'];
                continue;
            }
            if ($sale !== null && abs($target - $sale) < 0.01) {
                $priceMatches[$p->id] = ['pkg' => $p, 'kind' => 'sale'];
            }
        }
        $sessionFiltered = array_filter($priceMatches, fn ($m) => (int) $m['pkg']->sessions_per_month === (int) $cycle->total_sessions);

        if (count($sessionFiltered) === 0) {
            return ['eligible' => false, 'reject_reason' => 'no_pkg_matches_price_and_sessions', 'cycle_id' => $cycle->id, 'sub_id' => $sub->id];
        }
        if (count($sessionFiltered) > 1) {
            return ['eligible' => false, 'reject_reason' => 'ambiguous_pkg_match', 'cycle_id' => $cycle->id, 'sub_id' => $sub->id];
        }

        $winner = reset($sessionFiltered);

        // GATE 2: payment-truth — a completed payment must match cycle.final_price.
        // Use BOTH morph alias AND FQCN for payable_type: the payments table
        // carries both formats (older payment writers used the FQCN, newer
        // use the morph alias). Without this, ~21 cycles with FQCN-typed
        // completed payments were falsely rejected as no_payment_matches.
        $morph = $cycle->subscribable_type;
        $fqcn = $cycle->subscribable_type === 'quran_subscription'
            ? 'App\\Models\\QuranSubscription'
            : 'App\\Models\\AcademicSubscription';
        $payments = Payment::where(function ($q) use ($sub, $morph, $fqcn) {
            $q->where('subscription_id', $sub->id)
                ->orWhere(function ($qq) use ($morph, $fqcn, $sub) {
                    $qq->whereIn('payable_type', [$morph, $fqcn])
                        ->where('payable_id', $sub->id);
                });
        })->where('status', 'completed')->get();

        $hasMatchingPayment = $payments->contains(fn ($p) => abs((float) $p->amount - $finalPrice) < 0.01);
        if (! $hasMatchingPayment) {
            return ['eligible' => false, 'reject_reason' => 'no_payment_matches_cycle_final_price', 'cycle_id' => $cycle->id, 'sub_id' => $sub->id];
        }

        // All three gates pass.
        return [
            'eligible' => true,
            'cycle_id' => $cycle->id,
            'sub_id' => $sub->id,
            'sub_type' => $cycle->subscribable_type,
            'state' => $cycle->cycle_state,
            'final_price' => $finalPrice,
            'discount' => $discount,
            'total_sessions' => (int) $cycle->total_sessions,
            'billing_cycle' => $bc->value,
            'winner_pkg_id' => $winner['pkg']->id,
            'winner_pkg_sessions' => (int) $winner['pkg']->sessions_per_month,
            'winner_pkg_duration' => (int) $winner['pkg']->session_duration_minutes,
            'paid_kind' => $winner['kind'],
            'snapshot_was' => is_array($cycle->package_snapshot) ? $cycle->package_snapshot : [],
            'new_snapshot' => $this->buildSnapshot($winner['pkg'], $cycle),
        ];
    }

    private function buildSnapshot(object $pkg, SubscriptionCycle $cycle): array
    {
        return [
            'id' => $pkg->id,
            'name' => (string) ($pkg->name ?? ''),
            'currency' => (string) ($cycle->currency ?? $pkg->currency ?? 'SAR'),
            'sessions_per_month' => (int) $pkg->sessions_per_month,
            'session_duration_minutes' => (int) $pkg->session_duration_minutes,
            'monthly_price' => $pkg->monthly_price !== null ? (float) $pkg->monthly_price : null,
            'quarterly_price' => $pkg->quarterly_price !== null ? (float) $pkg->quarterly_price : null,
            'yearly_price' => $pkg->yearly_price !== null ? (float) $pkg->yearly_price : null,
            'sale_monthly_price' => $pkg->sale_monthly_price !== null ? (float) $pkg->sale_monthly_price : null,
            'sale_quarterly_price' => $pkg->sale_quarterly_price !== null ? (float) $pkg->sale_quarterly_price : null,
            'sale_yearly_price' => $pkg->sale_yearly_price !== null ? (float) $pkg->sale_yearly_price : null,
        ];
    }

    private function applyPlan(array $plan): void
    {
        BackfillLog::create([
            'bug_id' => 'inv-d2-snapshot-bucket-d-high',
            'table_name' => 'subscription_cycles',
            'row_id' => $plan['cycle_id'],
            'column_name' => 'package_snapshot',
            'original_value' => json_encode($plan['snapshot_was'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'new_value' => json_encode($plan['new_snapshot'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'backfill_command' => 'subscriptions:fix-pricing-snapshot-drift-bucket-d',
            'ran_at' => Carbon::now(),
        ]);

        DB::table('subscription_cycles')
            ->where('id', $plan['cycle_id'])
            ->update([
                'package_snapshot' => json_encode($plan['new_snapshot'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => Carbon::now(),
            ]);
    }
}
